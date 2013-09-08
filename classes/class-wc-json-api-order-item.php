<?php
/**
 * An Order class to insulate the API from the details of the
 * database representation
*/
require_once(dirname(__FILE__) . "/class-rede-base-record.php");
require_once(dirname(__FILE__) . "/class-wc-json-api-order.php");
class WC_JSON_API_OrderItem extends JSONAPIBaseRecord {

  public function __construct() {
    
  }
  public static function setupMetaAttributes() {
    // We only accept these attributes.
    self::$_meta_attributes_table = array(
      'quantity'          => array('name' => '_qty',           'type' => 'number'), 
      'tax_class'         => array('name' => '_tax_class',    'type' => 'number'), 
      'product_id'        => array('name' => '_product_id',    'type' => 'number'), 
      'variation_id'      => array('name' => '_variation_id',    'type' => 'number'), 
      'subtotal'          => array('name' => '_line_subtotal',    'type' => 'number'),
      'total'             => array('name' => '_line_total',    'type' => 'number'),  
      'tax'               => array('name' => '_line_tax',    'type' => 'number'),  
      'subtotal_tax'      => array('name' => '_line_subtotal_tax',    'type' => 'number'), 
    );
    self::$_meta_attributes_table = apply_filters( 'woocommerce_json_api_order_item_meta_attributes_table', self::$_meta_attributes_table );
  } // end setupMetaAttributes
  public static function setupModelAttributes() {
    global $wpdb;
    self::$_model_settings = array(
      'model_table'                => $wpdb->prefix . 'woocommerce_order_items',
      'meta_table'                => $wpdb->prefix . 'woocommerce_order_itemmeta',
      'model_table_id'             => 'order_item_id',
      'meta_table_foreign_key'    => 'order_item_id',
      'meta_function' => 'woocommerce_get_order_item_meta',
      'belongs_to' => array(
        'order' => array('class_name' => 'WC_JSON_API_Order', 'foreign_key' => 'order_id'),
        'product' => array('class_name' => 'WC_JSON_API_Product', 'meta_attribute' => 'product_id'),
      ),
    );
    self::$_model_attributes_table = array(
      'name'            => array('name' => 'order_item_name',  'type' => 'string'),
      'type'            => array('name' => 'order_item_type',  'type' => 'string'),
      'order_id'            => array('name' => 'order_id',     'type' => 'number'),

    );
    self::$_model_attributes_table = apply_filters( 'woocommerce_json_api_order_item_model_attributes_table', self::$_model_attributes_table );
  }
  public function asApiArray() {
    $attrs = parent::asApiArray();
    return $attrs;
  }
}