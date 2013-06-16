<?php
class RedEBaseRecord {
  // We want to be able to update the product in one go, as quickly
  // as possible because it is not unrealistic for us to want to
  // update hundres of products in one API call. We don't want to
  // impose an arbitrary limit, instead leaving that up to the
  // host user and as a configuration variable
  protected $_queries_to_run;
  // We need to know if this record exists in the database?
  // if not, then update should fail.
  protected $_new_record;
  protected $_invalid;
  protected $_page;
  protected $_per_page;
  protected $_result; // so we can add errors
  
  /**
    We want to establish a "fluid" API for the objects.
    which is why most of these methods return $this.
    
    ( new Object() )->setup()->doCalculation()->update()->done();
  */
  
  

  protected function setInvalid() {
    $this->_invalid = true;
    return $this;
  }
  protected function setValid() {
    $this->_invalid = false;
    return $this;
  }
  public function show_sql() {
    $sql = "";
    foreach ($this->_queries_to_run as $key => $query) {
      $sql .= "$key => [[[ $query ]]]\n";
    }
    echo $sql; 
  }
  /**
    You will need to define an all and where method on the child model.
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
    if ( $num == 0 ) {
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
      foreach ( $results as &$result ) {
        $result = call_user_func($callback,$result);
      }
      return $results;
    } else {
      return null;
    }
  }
}
