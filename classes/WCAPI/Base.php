<?php
namespace WCAPI;
require_once dirname( __FILE__ ) . '/BaseHelpers.php';
class Base extends Helpers {
  // We want to be able to update the product in one go, as quickly
  // as possible because it is not unrealistic for us to want to
  // update hundres of products in one API call. We don't want to
  // impose an arbitrary limit, instead leaving that up to the
  // host user and as a configuration variable
  public $_queries_to_run;
  public $_actual_model_id;
  // We need to know if this record exists in the database?
  // if not, then update should fail.
  public $_new_record;
  public $_valid;
  public $_page;
  public $_per_page;

  public $_result; // so we can add errors
  
  public static $_meta_attributes_table; 
  public static $_model_attributes_table;
  
  public $_meta_attributes;
  public $_model_attributes;
  public static $_model_settings;
  public static $adapter;
  public static $blog_id;
  
  /**
  * We want to establish a "fluid" API for the objects.
  * which is why most of these methods return $this.
  * 
  * ( new Object() )->setup()->doCalculation()->update()->done();
  */
  public function __construct() {
    static::setupMetaAttributes();
    static::setupModelAttributes();
  }
  public static function setAdapter( $a ) {
    static::$adapter = $a;
  }
  public static function setBlogId( $id ) {
    static::$blog_id = $id;;
  }
  public static function getModelSettings() {
    // This is kind of important, late static binding
    // can be really wonky sometimes. especially
    // with values that exist on the base model.
    static::setupModelAttributes();
    static::setupMetaAttributes();
    return static::$_model_settings;
  }
  public static function getDefaultModelSettings() {
    $wpdb = static::$adapter;
    // Here we have all the default settings
    // for a model.
    return array(
      'model_table'               => $wpdb->posts,
      'meta_table'                => $wpdb->postmeta,
      'model_table_id'            => 'id',
      'meta_table_foreign_key'    => 'post_id',
      'meta_function'             => 'get_post_meta',
      'update_meta_function'      => 'update_post_meta',
      'load_meta_function'        => null,
      'save_meta_function'        => null,
      'trigger_actions'           => true, // i.e should we trigger woocommerce actions?
      'trigger_filters'           => true, // i.e should we trigger woocommerce filters when loading/setting values.
      );
  }
  public function setNewRecord( $bool ) {
    $this->_new_record = $bool;
  }
  public function isNewRecord() {
    return $this->_new_record;
  }
  public function isValid() {
    return $this->_valid;
  }
  public function setValid( $bool ) {
    $this->_valid = $bool;
    return $this;
  }
  public function showSQL() {
    $sql = "";
    foreach ($this->_queries_to_run as $key => $query) {
      $sql .= "$key => [[[ $query ]]]\n";
    }
    echo $sql; 
  }
  public function getModelId() {
    return $this->_actual_model_id;
  }
  public function setModelId( $id ) {
    $this->_actual_model_id = $id;
  }

  /**
  *  You will need to define an all and where method on the child model.
  */
  public function done() {
    $wpdb = static::$adapter;
    foreach ( $this->_queries_to_run as $key=>$query ) {
      $wpdb->query($query);
      unset($this->_queries_to_run[$key]);
    }
  }

  public function addQuery($sql) {
    $this->_queries_to_run[] = $sql;
  }

  public function page( $num = 0 ) {
    $tnum = $num - 1;
    if ( $tnum <= 0 ) {
      $this->_page = $num;
    } else {
      $num = ($num * $this->_per_page) - 1;
      $this->_page = $num;
    }
    return $this;
  }

  public function per( $num = 25 ) {
    $this->_per_page = $num;
    return $this;
  }
  public function getAdapter() { return static::$adapter;}
  public function remapAttributes() {
    $attrs = array();
    foreach ( static::$_meta_attributes_table as $name => $desc ) {
      $attrs[ $desc['name'] ] = $this->dynamic_get($name, $desc);
    }
    return $attrs;
  }
  public function loadMetaAttributes() {
    static::setupMetaAttributes();
    static::setupModelAttributes();
    $s = static::getModelSettings();
    $meta_function = $s['meta_function'];
    $load_meta_function = $s['load_meta_function'];
    $id = $this->_actual_model_id;
    if ( $load_meta_function !== null ) {
      $attrs = call_user_func($load_meta_function, $this);
      foreach ( static::$_meta_attributes_table as $name => $desc ) {
        $value = $attrs[ $desc['name'] ];
        $this->dynamic_set( $name, $desc, $value, $this->getModelId() );
      }
    } else {
      foreach ( static::$_meta_attributes_table as $name => $desc ) {
        $value = call_user_func($meta_function, $id, $desc['name'], true );
        $this->dynamic_set( $name, $desc, $value, $this->getModelId() );
      }
    }
    return $this;
  }
  public function saveMetaAttributes() {
    $meta_table              = $this->orEq( static::$_model_settings, 'meta_table', $wpdb->postmeta ); 
    $meta_table_foreign_key  = $this->orEq( static::$_model_settings, 'meta_table_foreign_key', 'post_id' );
    $save_meta_function = static::$_model_settings['save_meta_function'];
    if ( $save_meta_function) {
      call_user_func($save_meta_function, $this);
    } else {
      $meta_sql = "
        UPDATE {$meta_table}
          SET `meta_value` = CASE `meta_key`
            ";
            foreach (static::$_meta_attributes_table as $attr => $desc) {
              if ( isset( $this->_meta_attributes[$attr] ) ) {
                $value = $this->_meta_attributes[$attr];
                if ( ! empty($value) ) {
                  if ( isset( $desc['updater'] ) ) {
                    $this->{ $desc['updater'] }( $value );
                  } else {
                    $meta_sql .= $wpdb->prepare( "\tWHEN '{$desc['name']}' THEN %s\n ", $value);
                  }
                }
              }
            }
            $meta_sql .= "
          END 
        WHERE `{$meta_table_foreign_key}` = '{$this->_actual_model_id}'
      ";
    }
    if ( gettype($meta_sql) ) {
      $key = md5($meta_sql);
      $this->_queries_to_run[$key] = $meta_sql;
    } else if ( gettype( $meta_sql) == 'array') {
      foreach ( $meta_sql as $sql ) {
        $key = md5($sql);
        $this->_queries_to_run[$key] = $sql;
      }
    }
  }
  // We need an easier interface to fetching items
  public function fetch( $callback = null ) {
    $wpdb = static::$adapter;
    $sql = $this->_queries_to_run[count($this->_queries_to_run) - 1];
    if ( ! empty($sql) ) {
      if ( $this->_per_page && $this->_page) {
        $sql .= " LIMIT {$this->_page},{$this->_per_page}";
      }
      $results = $wpdb->get_results($sql,'ARRAY_A');
      Helpers::debug("in function fetch: WPDB returned " . count($results) . " results");
      if ($callback) {
        foreach ( $results as &$result ) {
          if ( $callback ) {
            $result = call_user_func($callback,$result);
          }
        }
      } else {
        $klass = get_called_class();
        $models = array();
        $s = static::getModelSettings();
        $meta_function = $s['meta_function'];
        foreach ( $results as $record ) {
          $model = new $klass();
          if ( isset( $record['id']) ) {
            $id = $record['id'];
          } else if ( isset( $record['ID']) ) {
            $id = $record['ID'];
          } else if ( isset( $record[ $s['model_table_id'] ] ) ) {
            $id = $record[ $s['model_table_id'] ];
          }
          $model->setModelId( $id );
          foreach ( static::$_model_attributes_table as $name => $desc ) {
            $model->dynamic_set( $name, $desc,$record[ $desc['name'] ] );
            //$model->{$name} = $record[$desc['name']];
          }
          $model->loadMetaAttributes();
          $model->setValid( true );
          $model->setNewRecord( false );
          $models[] = $model;
        }
        return $models;
      }
      if (count($results) < 1) {
        Helpers::debug("in function fetch, empty result set using: $sql");
      } else {
        Helpers::debug("in function fetch: " . count($results) . " were returned from: " . $sql);
      }
      return $results;
    } else {
      Helpers::debug("in function fetch, sql was empty.");
      return null;
    }
  }
  public function update() {
    $wpdb = static::$adapter;
    $model_table             = $this->orEq( static::$_model_settings, 'model_table', $wpdb->posts );  
    $model_table_id          = $this->orEq( static::$_model_settings, 'model_table_id', 'ID' );  
        
    $values = array();
    foreach (static::$_model_attributes_table as $attr => $desc) {
      $value = $this->dynamic_get( $attr, $desc, $this->getModelId());
      $values[] = $wpdb->prepare("`{$desc['name']}` = %s", $value );
    }
    $post_sql = "UPDATE `{$model_table}` SET " . join(',',$values) . " WHERE `{$model_table_id}` = '{$this->_actual_model_id}'";
    $key = md5($post_sql);
    $this->_queries_to_run[$key] = $post_sql;
    return $this;
  }

  public function loadHasManyAssociation( $name ) {
    $wpdb = static::$adapter;
    $hm =  static::$_model_settings['has_many'];
    $models = array();
    if ( isset( $hm[$name] ) ) {
      $klass = 'WCAPI\\' . $hm[$name]['class_name'];
      $fkey = $this->orEq($hm[$name],'foreign_key', false);
      $s = $klass::getModelSettings();
      $sql = $wpdb->prepare("SELECT {$s['model_table_id']} FROM {$s['model_table']} WHERE {$fkey} = %d",$this->_actual_model_id);
      $ids = $wpdb->get_col($sql);
      foreach ( $ids as $id ) {
        $model = $klass::find($id);
        $models[] = $model->asApiArray();
      }
    }
    return $models;
  }
  public function loadBelongsToAssociation( $name ) {
    $wpdb = static::$adapter;
    $hm =  static::$_model_settings['belongs_to'];
    $model = null;
    if ( isset( $hm[$name] ) ) {
      $klass = "WCAPI\\" . $hm[$name]['class_name'];
      $fattr = $this->orEq($hm[$name],'meta_attribute', false);
      if ( !$fattr )
        $fattr = $this->orEq($hm[$name],'foreign_key', false);
      $s = $klass::getModelSettings();
      if ( $fattr ) {
        $model = $klass::find( $this->{$fattr});
      }
    }
    return $model;
  }
  /**
  *  From here we have a dynamic getter. We return a special REDENOTSET variable.
  */
  public function __get( $name ) {
    if ( isset( static::$_meta_attributes_table[$name] ) ) {
      if ( isset(static::$_meta_attributes_table[$name]['getter'])) {
        return $this->{static::$_meta_attributes_table[$name]['getter']}();
      }
      if ( isset ( $this->_meta_attributes[$name] ) ) {
        return $this->_meta_attributes[$name];
      } else {
        return '';
      }
    } else if ( isset( static::$_model_attributes_table[$name] ) ) {
      if ( isset( $this->_model_attributes[$name] ) ) {
        return $this->_model_attributes[$name];
      } else {
        return '';
      }
    } else if ( isset( static::$_model_settings['has_many'] ) && $this->inArray( $name, array_keys(static::$_model_settings['has_many']) ) ) {
      return $this->loadHasManyAssociation($name);
    } else if ( isset( static::$_model_settings['belongs_to'] ) && $this->inArray( $name, array_keys(static::$_model_settings['belongs_to']) ) ) {
      return $this->loadBelongsToAssociation($name);
    }
  } // end __get
  
  // Dynamic setter
  public function __set( $name, $value ) {
    if ( isset( static::$_meta_attributes_table[$name] ) ) {
      if ( isset(static::$_meta_attributes_table[$name]['setter'])) {
        $this->{static::$_meta_attributes_table[$name]['setter']}( $value );
      }
      $this->_meta_attributes[$name] = $value;
    } else if ( isset( static::$_model_attributes_table[$name] ) ) {
      $this->_model_attributes[$name] = $value;
    } else {
      throw new Exception( __('That attribute does not exist to be set.','woocommerce_json_api') . " `$name`");
    }
  }

  public function dynamic_set( $name, $desc, $value, $filter_value = null ) {

    if ( $desc['type'] == 'array') {
      $value = serialize( $value );
    }
    if ( isset($desc['filters']) ) {
      foreach ( $desc['filters'] as $filter ) {
        $value = apply_filters( $filter, $value, $filter_value );
      }
    }
    if ( isset($desc['setter']) ) {
      $this->{ $desc['setter'] }( $value );
    } else {
      $this->{ $name } = $value;
    }
  }

  public function dynamic_get( $name, $desc, $filter_value = null ) {
    if ( isset($desc['getter']) ) {
      $value = $this->{ $desc['getter'] }();
    } else {
      $value = $this->{ $name };
    }
    if ( isset($desc['type']) && $desc['type'] == 'array') {
      $value = maybe_unserialize( $value );
    }
    if ( isset($desc['type']) && $desc['type'] == 'array' && empty($value) ) {
      $value = array();
    }
    return $value;
  }
  /**
  * Sometimes we want to act directly on the result to be sent to the user.
  * This allows us to add errors and warnings.
  */
  public function setResult ( $result ) {
    $this->_result = $result;
    return $this;
  }

  public static function find( $id ) {

    $wpdb = static::$adapter;

    static::setupModelAttributes();
    static::setupMetaAttributes();

    $model = new static();
    $model->setValid( false );

    if ( isset( static::$_model_settings ) ) {

      $model_table             = $model->orEq( static::$_model_settings, 'model_table', $wpdb->posts );  
      $meta_table              = $model->orEq( static::$_model_settings, 'meta_table', $wpdb->postmeta );
      $model_table_id          = $model->orEq( static::$_model_settings, 'model_table_id', 'ID' );   
      $meta_table_foreign_key  = $model->orEq( static::$_model_settings, 'meta_table_foreign_key', 'post_id' );
      $meta_function           = $model->orEq( static::$_model_settings, 'meta_function', 'get_post_meta' );

    } else {

      $model_table             = $wpdb->posts;  
      $meta_table             = $wpdb->postmeta;
      $model_table_id          = 'ID';   
      $meta_table_foreign_key = 'post_id';
      $meta_function          = 'get_post_meta';

    }

    $record = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$model_table} WHERE {$model_table_id} = %d", (int) $id), 'ARRAY_A' );
    
    if ( $record ) {
      $model->setModelId( $id );
      
      foreach ( static::$_model_attributes_table as $name => $desc ) {
        $model->dynamic_set( $name, $desc,$record[ $desc['name'] ] );
        //$model->{$name} = $record[$desc['name']];
      }
      $model->loadMetaAttributes();
      
      $model->setValid( true );
      $model->setNewRecord( false );
    
    } else {
      $model = null;
    }
    return $model;
  }
  public function fromApiArray( $attrs ) {
    static::setupModelAttributes();
    static::setupMetaAttributes();
    $attributes = array_merge(static::$_model_attributes_table, static::$_meta_attributes_table);
    foreach ( $attrs as $name => $value ) {
      if ( isset($attributes[$name]) ) {
        $desc = $attributes[$name];
        $this->dynamic_set( $name, $desc, $value, $this->getModelId());
      } 
    }
    return $this;
  }
  public function asApiArray() {
    $wpdb = static::$adapter;
    static::setupModelAttributes();
    static::setupMetaAttributes();
    $attributes = array_merge(static::$_model_attributes_table, static::$_meta_attributes_table);
    $attributes_to_send['id'] = $this->getModelId();

    foreach ( $attributes as $name => $desc ) {
      $attributes_to_send[$name] = $this->dynamic_get( $name, $desc, $this->getModelId());
    }
    return $attributes_to_send;
  }

    /**
  *  Similar in function to Model.all in Rails, it's just here for convenience.
  */
  public static function all($fields = 'id', $conditions = null) {
    $wpdb = static::$adapter;
    static::setupModelAttributes();
    static::setupMetaAttributes();
    $model = new static();
    if ( isset( static::$_model_settings ) ) {
      $model_table             = $model->orEq( static::$_model_settings, 'model_table', $wpdb->posts );  
      $model_table_id          = $model->orEq( static::$_model_settings, 'model_table_id', 'ID' );   
      $model_conditions        = $model->orEq( static::$_model_settings, 'model_conditions', '' );  
    } else {
      $model_table             = $wpdb->posts;  
      $model_table_id          = 'ID'; 
      $model_conditions        = '';  
    }
    if ( ! empty( $model_conditions) && $conditions && ! empty( $conditions )) {
      $model_conditions .= " AND ($conditions)";
    } else if ( empty( $model_conditions) && $conditions && ! empty( $conditions )) {
      $model_conditions = $conditions;
    }
    $sql = "SELECT $fields FROM {$model_table} {$model_conditions}";
    $model->addQuery($sql);
    return $model;
  }

  public function create( $attrs = null ) {

    $wpdb = static::$adapter;
    $user_ID = $GLOBALS['user_ID'];

    // We should setup attrib tables if it hasn't
    // already been done
    static::setupModelAttributes();
    static::setupMetaAttributes();
    // Maybe we want to set attribs and create in one go.
    if ( $attrs ) {
      foreach ( $attrs as $name=>$value ) {
        $this->{ $name } = $value;
      }
    }
    $post = array();
    $s = static::getModelSettings();

    $update_meta_function = $s['update_meta_function'];
    
    if ( $s['model_table'] == $wpdb->posts)
      $post['post_author'] = $user_ID;

    foreach (static::$_model_attributes_table as $attr => $desc) {

      $value = $this->dynamic_get( $attr, $desc, null);
      $post[ $desc['name'] ] = $value;

    }
    if ( $s['model_table'] == $wpdb->posts) {
      $id = wp_insert_post( $post, true);
    } else {
      if ( $wpdb->insert($s['model_table'],$post) ) {
        $id = $wpdb->insert_id;
      } else {
        $this->_result->addError( 
          __('Failed to create ' . get_called_class() ), 
          WCAPI_CANNOT_INSERT_RECORD 
        );
        return $this;
      }
    }

    if ( is_wp_error( $id )) {
      // we  should handle errors
      $this->setValid(false);
      $this->_result->addError( 
        __('Failed to create ' . get_called_class() ), 
        WCAPI_CANNOT_INSERT_RECORD 
      );
    } else {
      $this->setValid(true);

      foreach ( static::$_meta_attributes_table as $attr => $desc ) {
        
        if ( isset( $this->_meta_attributes[$attr] ) ) {
          $value = $this->_meta_attributes[$attr];

          if ( ! empty($value) ) {
            if ( $update_meta_function ) {
              call_user_func($update_meta_function, $id, $desc['name'], $value );
            }
          }

        } 

      }  

      $this->_actual_model_id = $id;
    }
    return $this;
  }
  
}

