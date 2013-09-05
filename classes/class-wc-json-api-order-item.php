<?php
/**
 * An Order class to insulate the API from the details of the
 * database representation
*/
require_once(dirname(__FILE__) . "/class-rede-base-record.php");

class WC_JSON_API_Order extends JSONAPIBaseRecord {

  public static $_meta_attributes_table; 
  public static $_model_attributes_table;

  public static $_model_settings;
  
  public $_meta_attributes;
  public $_model_attributes;

  public function __construct() {
    
  }
  public static function setupMetaAttributes() {
    global $wpdb;
    self::$_model_settings = array(
      'post_table'                => $wpdb->prefix . 'woocommerce_order_items',
      'meta_table'                => $wpdb->prefix . 'woocommerce_order_itemmeta',
      'post_table_id'             => 'order_item_id',
      'meta_table_foreign_key'    => 'order_item_id',
    );
    if ( self::$_meta_attributes_table ) {
      return;
    }
    
    // We only accept these attributes.
    self::$_meta_attributes_table = array(
      'order_key'             => array('name' => '_order_key',                  'type' => 'string'), 
    );
    /*
      With this filter, plugins can extend this ones handling of meta attributes for a product,
      this helps to facilitate interoperability with other plugins that may be making arcane
      magic with a product, or want to expose their product extensions via the api.
    */
    self::$_meta_attributes_table = apply_filters( 'woocommerce_json_api_order_meta_attributes_table', self::$_meta_attributes_table );
  } // end setupMetaAttributes

}