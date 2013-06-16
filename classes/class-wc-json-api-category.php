<?php
require_once(dirname(__FILE__) . "/class-rede-base-record.php");
class WC_JSON_API_Category extends RedEBaseRecord {
  private $_attributes;
  public static $_attributes_table;
  public function __construct() {
    $this->_attributes = array();
    WC_JSON_API_Category::setupAttributesTable();
  }
  public static function setupAttributesTable() {
    if ( self::$_attributes_table ) {
      return;
    }
    self::$_attributes_table = array(
      'id'            => array( 'name' => 'term_id'),
      'name'          => array( 'name' => 'name'),
      'taxonomy_id'   => array( 'name' => 'term_taxonomy_id'),
      'taxonomy'      => array( 'name' => 'taxonomy'),
      'description'   => array( 'name' => 'description'),
      'parent_id'     => array( 'name' => 'parent')
    );
  }
  public function setCategory( $category_object ) {
    foreach (self::$_attributes_table as $name=>$attrs) {
      $this->{$name} = $category_object->{$attrs['name']};
    }
    return $this;
  }
  public function asApiArray() {
    return $this->_attributes;
  }
  public function __get( $name ) {
    if (isset(self::$_attributes_table[$name])) {
      return $this->$_attributes[$name];
    } else {
      throw new Exception( __('That attribute does not exist!', 'woocommerce_json_api') );
    }
  }

  public function __set( $name, $value ) {
    if ( isset(self::$_attributes_table[$name])) {
      $this->_attributes[$name] = $value;
    } else {
      throw new Exception( __('That attribute does not exist to be set.','woocommerce_json_api'));
    }
  }
}