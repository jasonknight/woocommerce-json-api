<?php
/**
  A Product class to insulate the API from the details of the
  database representation
*/
require_once(dirname(__FILE__) . "/class-rede-base-record.php");
require_once(dirname(__FILE__) . "/class-wc-json-api-category.php");
class WC_JSON_API_Product extends RedEBaseRecord {
  // Products are split off into two
  // datasources, posteta, and the posts
  // themselves.
  private $_meta_attributes;
  // This is static because we really only need to
  // do this mapping once, not for each object we create.
  // We need to keep it small and fast
  public static $_meta_attributes_table; // a mapping of wich product attribs
                                   // go to the meta table
  private $_post_attributes;
  public static $_post_attributes_table;

  // A the id for the actual product, used for queries.
  private $_actual_product_id;

    
  /**
    Here we normalize the attributes, giving them a consistent name scheme and obvious
    meaning, as well as making them easier to type so that we have a nice, user
    friendly interface into WooCom.
    
    We also need to be able to validate the inputs from outside, so we have to 
    attach information to each key about what it can contain, and how we should
    cast things to and from the DB.
    
    When we say bool, we mean a WP Bool, which is `yes` or `no`. I actually prefer this
    idea, because of the way PHP and many languages handle boolean values. It's just
    so much more clear.

    The fundamental idea for this class is that there doesn't seem to be a single entry
    point into and out of the database for WooCom which provides a mixture of classes
    and functions that get, process, display, and save products to the database and that
    depend on things like $_POST and various Defines. 

    We want to abstract away the naughty bits of the database representation of the product
    in question.
  */
  public static function setupMetaAttributes() {
    if ( self::$_meta_attributes_table ) {
      return;
    }
    // We only accept these attributes.
    self::$_meta_attributes_table = array(
      'sku'               => array('name' => '_sku',              'type' => 'string'),
      'downloadable'      => array('name' => '_downloadable',     'type' => 'bool'),
      'virtual'           => array('name' => '_virtual',          'type' => 'bool'),
      'manage_stock'      => array('name' => '_manage_stock',     'type' => 'bool'),
      'sold_individually' => array('name' => '_sold_individually','type' => 'bool'),
      'featured'          => array('name' => '_featured',         'type' => 'bool'),
      'allow_backorders'  => array(
                              'name' => '_backorders',       
                              'type' => 'string', 
                              'values' => array('yes','no','notify')
                             ),
      'quantity'          => array('name' => '_stock',            'type' => 'number', 'filters' => array('woocommerce_stock_amount') ),
      'height'            => array('name' => '_height',           'type' => 'number'),
      'weight'            => array('name' => '_weight',           'type' => 'number'),
      'length'            => array('name' => '_length',           'type' => 'number'),
      'price'             => array('name' => '_price',            'type' => 'number'),
      'regular_price'     => array('name' => '_regular_price',    'type' => 'number'),
      'sale_price'        => array('name' => '_sale_price',       'type' => 'number'),
      'sale_from'         => array('name' => '_sale_price_dates_from', 'type' => 'timestamp'),
      'sale_to'           => array('name' => '_sale_price_dates_to',   'type' => 'timestamp'),
      // 'download_paths'    => array('name' => '_file_paths',            
      //                              'type' => 'array', 
      //                              'filters' => array('woocommerce_file_download_paths') 
      //                         ),
      'status'            => array(
                               'name' => '_stock_status',          
                               'type' => 'string', 
                               'values' => array(
                                'instock',
                                'outofstock',
                               ),
                             ),
      'attributes'        => array(
                              'name' => '_attributes',   
                              'type' => 'object', 
                              'class' => 'WC_JSON_API_ProductAttributes',
                             ), 
      'tax_class'         => array('name' => '_tax_class',        'type' => 'string'),
      'tax_status'        => array(
                               'name' => '_tax_status',           
                               'type' => 'string',
                               'values' => array(
                                'taxable',
                                'shipping',
                                'none',
                               ),
                             ),
    );
    /*
      With this filter, plugins can extend this ones handling of meta attributes for a product,
      this helps to facilitate interoperability with other plugins that may be making arcane
      magic with a product, or want to expose their product extensions via the api.
    */
    self::$_meta_attributes_table = apply_filters( 'woocommerce_json_api_product_meta_attributes_table', self::$_meta_attributes_table );
  } // end setupMetaAttributes
  
  public static function setupPostAttributes() {
    if ( self::$_post_attributes_table ) {
      return;
    }
    self::$_post_attributes_table = array(
      'name'            => array('name' => 'post_title',             'type' => 'string'),
      'slug'            => array('name' => 'post_name',              'type' => 'string'),
      'type'            => array('name' => 'post_type',              'type' => 'string'),
      'description'     => array('name' => 'post_content',           'type' => 'string'),
      'status'          => array(
                                  'name' => 'post_status',            
                                  'type' => 'string',
                                  'values' => array(
                                    'publish',
                                    'inherit',
                                    'pending',
                                    'private',
                                    'future',
                                    'draft',
                                    'trash',
                                  ),
                          ),
    );
    self::$_post_attributes_table = apply_filters( 'woocommerce_json_api_product_post_attributes_table', self::$_post_attributes_table );
  }
  
  public function asApiArray() {
    global $wpdb;
    $category_objs = woocommerce_get_product_terms($this->_actual_product_id, 'product_cat', 'all');
    $categories = array();

    foreach ( $category_objs as $cobj ) {
      // This looks scary if you've never used Javascript () evaluates the
      // the contents and returns the value, in the same way that (3+4) * 8 
      // works. Because we define the class with a Fluid API, most functions
      // that modify state of the object, return the object.
      try {
        $_cat = new WC_JSON_API_Category();
        $categories[] = $_cat->setCategory( $cobj )->asApiArray();
      } catch (Exception $e) {
        // we should put some logging here soon!
        RedEHelpers::error( $e->getMessage() );
      }
      
    }
    $attributes = array_merge(self::$_post_attributes_table, self::$_meta_attributes_table);
    $attributes_to_send['id'] = $this->getProductId();
    foreach ( $attributes as $name => $desc ) {

      $attributes_to_send[$name] = $this->dynamic_get( $name, $desc, $this->getProductId());
    }
    $attributes_to_send['categories'] = $categories;
    return $attributes_to_send;
  }
  public function fromApiArray( $attrs ) {
    $attributes = array_merge(self::$_post_attributes_table, self::$_meta_attributes_table);
    foreach ( $attrs as $name => $value ) {
      if ( isset($attributes[$name]) ) {
        $desc = $attributes[$name];
        $this->dynamic_set( $name, $desc, $value, $this->getProductId());
      }
    }
    return $this;
  }
  /**
    From here we have a dynamic getter. We return a special REDENOTSET variable.
  */
  public function __get( $name ) {
    if ( isset( self::$_meta_attributes_table[$name] ) ) {
      if ( isset(self::$_meta_attributes_table[$name]['getter'])) {
        return $this->{self::$_meta_attributes_table[$name]['getter']}();
      }
      if ( isset ( $this->_meta_attributes[$name] ) ) {
        return $this->_meta_attributes[$name];
      } else {
        return '';
      }
    } else if ( isset( self::$_post_attributes_table[$name] ) ) {
      if ( isset( $this->_post_attributes[$name] ) ) {
        return $this->_post_attributes[$name];
      } else {
        return '';
      }
    }
  } // end __get
  
  // Dynamic setter
  public function __set( $name, $value ) {
    if ( isset( self::$_meta_attributes_table[$name] ) ) {
      if ( isset(self::$_meta_attributes_table[$name]['setter'])) {
        $this->{self::$_meta_attributes_table[$name]['setter']}( $value );
      }
      $this->_meta_attributes[$name] = $value;
    } else if ( isset( self::$_post_attributes_table[$name] ) ) {
      $this->_post_attributes[$name] = $value;
    } else {
      throw new Exception( __('That attribute does not exist to be set.','woocommerce_json_api') . " `$name`");
    }
  } 
  public function setProductId( $id ) {
    $this->_actual_product_id = $id;
  }
  
  
  public function getProductId() {
    return $this->_actual_product_id;
  }
  // How do we find Products?
  
  public static function find( $id ) {
    global $wpdb;
    self::setupPostAttributes();
    self::setupMetaAttributes();
    $product = new WC_JSON_API_Product();
    $product->setValid( false );
    $post = get_post( $id, 'ARRAY_A' );
    if ( $post ) {
      $product->setProductId( $id );
      foreach ( self::$_post_attributes_table as $name => $desc ) {
        $product->dynamic_set( $name, $desc,$post[$desc['name']] );
        //$product->{$name} = $post[$desc['name']];
      }
      foreach ( self::$_meta_attributes_table as $name => $desc ) {
        $value = get_post_meta( $id, $desc['name'], true );
        // We may want to do some "funny stuff" with setters and getters.
        // I know, I know, "no funny stuff" is generally the rule.
        // But WooCom or WP could change stuff that would break a lot
        // of code if we try to be explicity about each attribute.
        // Also, we may want other people to extend the objects via
        // filters.
        $product->dynamic_set( $name, $desc, $value, $product->getProductId() );
      }
      $product->setValid( true );
      $product->setNewRecord( false );
    }
    return $product;
  }
  public function setNewRecord( $bool ) {
    $this->_new_record = $bool;
  }
  public function isNewRecord() {
    return $this->_new_record;
  }
  public static function find_by_sku( $sku ) {
    global $wpdb;
    $product = new WC_JSON_API_Product();
    $product->setValid( false );
    $pid = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1",$sku) );
    if ( $pid ) {
      $product = WC_JSON_API_Product::find( $pid );
    }
    return $product;
  }
  
  // How do we update products?
  public function update() {
    global $wpdb;
    $meta_sql = "
UPDATE {$wpdb->postmeta}
  SET meta_value = CASE `meta_key`
    ";
    foreach (self::$_meta_attributes_table as $attr => $desc) {
      if ( isset( $this->_meta_attributes[$attr] ) ) {
        $value = $this->_meta_attributes[$attr];
        if ( ! empty($value) ) {
          $meta_sql .= $wpdb->prepare( "\tWHEN '{$desc['name']}' THEN %s\n ", $value);
        }
      } 
    }
    $meta_sql .= "
  END 
WHERE post_id = '{$this->_actual_product_id}'
    ";
    $key = md5($meta_sql);
    $this->_queries_to_run[$key] = $meta_sql; 
    $values = array();
    foreach (self::$_post_attributes_table as $attr => $desc) {
      $value = $this->dynamic_get( $attr, $desc, $this->getProductId());
      $values[] = $wpdb->prepare("`{$desc['name']}` = %s", $value );
    }
    $post_sql = "UPDATE {$wpdb->posts} SET " . join(',',$values) . " WHERE ID = '{$this->_actual_product_id}'";
    $key = md5($post_sql);
    $this->_queries_to_run[$key] = $post_sql;
    return $this;
  }

  public function create( $attrs = null ) {
    global $wpdb, $user_ID;
    // We should setup attrib tables if it hasn't
    // already been done
    self::setupPostAttributes();
    self::setupMetaAttributes();
    // Maybe we want to set attribs and create in one go.
    if ( $attrs ) {
      foreach ( $attrs as $name=>$value ) {
        $this->{ $name } = $value;
      }
    }
    $post = array( 'post_author' => $user_ID, 'post_type' => 'product');
    foreach (self::$_post_attributes_table as $attr => $desc) {
      $value = $this->dynamic_get( $attr, $desc, null);
      $post[ $desc['name'] ] = $value;
    }
    $post['post_type'] = 'product';
    $id = wp_insert_post( $post, true);
    if ( is_wp_error( $id )) {
      // we  should handle errors
      $this->setValid(false);
      $this->_result->addError( __('Failed to create product'), WCAPI_CANNOT_INSERT_RECORD );
    } else {
      $this->setValid(true);
      foreach (self::$_meta_attributes_table as $attr => $desc) {
        if ( isset( $this->_meta_attributes[$attr] ) ) {
          $value = $this->_meta_attributes[$attr];
          if ( ! empty($value) ) {
            update_post_meta($id,$desc['name'],$value);
          }
        } 
      }  
      $this->_actual_product_id = $id;
    }
    return $this;
  }

  /**
    Similar in function to Model.all in Rails, it's just here for convenience.
  */
  public static function all($fields = 'id') {
    global $wpdb;
    $sql = "SELECT $fields from {$wpdb->posts} WHERE post_type IN ('product')";
    $product = new WC_JSON_API_Product();
    $product->addQuery($sql);
    return $product;
  }
  /**
    Sometimes we want to act directly on the result to be sent to the user.
    This allows us to add errors and warnings.
  */
  public function setResult ( $result ) {
    $this->_result = $result;
    return $this;
  }
  
  
}
