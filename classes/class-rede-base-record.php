<?php
class JSONAPIBaseRecord {
  // We want to be able to update the product in one go, as quickly
  // as possible because it is not unrealistic for us to want to
  // update hundres of products in one API call. We don't want to
  // impose an arbitrary limit, instead leaving that up to the
  // host user and as a configuration variable
  protected $_queries_to_run;
  protected $_actual_model_id;
  // We need to know if this record exists in the database?
  // if not, then update should fail.
  protected $_new_record;
  protected $_valid;
  protected $_page;
  protected $_per_page;
  protected $_result; // so we can add errors
  
  /**
  * We want to establish a "fluid" API for the objects.
  * which is why most of these methods return $this.
  * 
  * ( new Object() )->setup()->doCalculation()->update()->done();
  */
  
  public function setNewRecord( $bool ) {
    $this->_new_record = $bool;
  }
  public function isNewRecord() {
    return $this->_new_record;
  }
  public function isValid() {
    return $this->_valid;
  }
  protected function setValid( $bool ) {
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
    global $wpdb;
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

  // We need an easier interface to fetching items
  public function fetch( $callback ) {
    global $wpdb;
    $sql = $this->_queries_to_run[count($this->_queries_to_run) - 1];
    if ( ! empty($sql) ) {
      if ( $this->_per_page && $this->_page) {
        $sql .= " LIMIT {$this->_page},{$this->_per_page}";
      }
      $results = $wpdb->get_results($sql,'ARRAY_A');
      JSONAPIHelpers::debug("in function fetch: WPDB returned " . count($results) . " results");
      foreach ( $results as &$result ) {
        if ( $callback ) {
          $result = call_user_func($callback,$result);
        }
      }
      if (count($results) < 1) {
        JSONAPIHelpers::debug("in function fetch, empty result set using: $sql");
      } else {
        JSONAPIHelpers::debug("in function fetch: " . count($results) . " were returned from: " . $sql);
      }
      return $results;
    } else {
      JSONAPIHelpers::debug("in function fetch, sql was empty.");
      return null;
    }
  }
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
      WHERE post_id = '{$this->_actual_model_id}'
    ";
    $key = md5($meta_sql);
    $this->_queries_to_run[$key] = $meta_sql;
    $values = array();
    foreach (self::$_post_attributes_table as $attr => $desc) {
      $value = $this->dynamic_get( $attr, $desc, $this->getModelId());
      $values[] = $wpdb->prepare("`{$desc['name']}` = %s", $value );
    }
    $post_sql = "UPDATE {$wpdb->posts} SET " . join(',',$values) . " WHERE ID = '{$this->_actual_model_id}'";
    $key = md5($post_sql);
    $this->_queries_to_run[$key] = $post_sql;
    return $this;
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
}

