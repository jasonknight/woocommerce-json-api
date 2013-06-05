<?php
/**

*/
class WooCommerce_JSON_API_Result {
  private $params;
  public function setParams( $params ) {
    $this->params = $params;
    $this->params['status'] = true;
    $this->params['errors'] = array();
    $this->params['warnings'] = array();
    $this->params['payload'] = array();
    return $this;
  }
  public function setPayload( $collection ) {
    $this->params['payload'] = $collection;
    return $this;
  }
  public function addPayload( $hash ) {
    $this->params['payload'][] = $hash;
  }
  public function asJSON() {
    return json_encode($this->params, JSON_PRETTY_PRINT);
  }
  public function addError( $text, $code ) {
    $this->params['status'] = false;
    $this->params['errors'][] = array( 'text' => $text, 'code' => $code);
  }
  public function addWarning( $text, $code ) {
    $this->params['warnings'][] = array( 'text' => $text, 'code' => $code);
  }
  
}
