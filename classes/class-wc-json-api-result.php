<?php
class WooCommerce_JSON_API_Result {
  public $params;
  public function status() {
    return $this->params['status'];
  }
  public function setStatus($bool) {
    $this->params['status'] = $bool;
  }
  public function setParams( $params ) {
    if ( isset($params['arguments']['password']) ) {
      // We shouldn't pass back the password.
      $params['arguments']['password'] = '[FILTERED]';
    }
    unset($params['uploads']); // we don't need this in the result
    $this->params = $params;
    $this->params['status'] = true;
    $this->params['errors'] = array();
    $this->params['warnings'] = array();
    $this->params['notifications'] = array();
    $this->params['payload'] = array();
    $this->params['arguments']['token'] = "";

    return $this;
  }
  public function getParams() {
    return $this->params;
  }
  public function setPayload( $collection ) {
    $this->params['payload'] = $collection;
    return $this;
  }
  public function setToken( $token ){
    $this->params['arguments']['token'] = $token;
  }
  
  /**
  * This is useful when we are looping and grabbing bits, but don't
  * want to create our own array
  */
  public function addPayload( $hash ) {
    $this->params['payload'][] = $hash;
  }
  public function asJSON() {
    $this->params['payload_length'] = count($this->params['payload']);
    if (PHP_MINOR_VERSION < 4) {
      JSONAPIHelpers::warn("PHP 5.4 and above recommended for the API.");
      $text = json_encode($this->params);
    } else {
      $text = json_encode($this->params, JSON_PRETTY_PRINT);
    }
    $jsonp = false;
    if ( isset($this->params['callback']) ) {
      $jsonp = $this->params['callback'];
    }
    if ( isset($this->params['jsonp']) ) {
      $jsonp = $this->params['jsonp'];
    }
    if ( $jsonp ) {
      return "{$jsonp}({$text});";
    } else {
      return $text;
    }
    
  }
  public function addError( $text, $code, $merge = array() ) {
    $this->params['status'] = false;
    $error = array( 'text' => $text, 'code' => $code);
    foreach ($merge as $k=>$v) {
      $error[$k] = $v;
    }
    $this->params['errors'][] = $error;
    
  }
  public function addWarning( $text, $code , $merge = array()) {
    $warn = array( 'text' => $text, 'code' => $code);
    foreach ($merge as $k=>$v) {
      $warn[$k] = $v;
    }
    $this->params['warnings'][] = $warn;
  }
  
}
