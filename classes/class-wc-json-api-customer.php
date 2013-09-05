<?php
/**
 * A Customer class to insulate the API from the details of the
 * database representation
*/
require_once(dirname(__FILE__) . "/class-rede-base-record.php");
require_once(dirname(__FILE__) . "/class-wc-json-api-category.php");
class WC_JSON_API_Customer extends JSONAPIBaseRecord {
   public static function setupMetaAttributes() {

    if ( self::$_meta_attributes_table ) {
      return;
    }
    // We only accept these attributes.
    self::$_meta_attributes_table = array(
      'order_count'               => array('name' => '_order_count',      'type' => 'number'),
    );
    /*
      With this filter, plugins can extend this ones handling of meta attributes for a customer,
      this helps to facilitate interoperability with other plugins that may be making arcane
      magic with a customer, or want to expose their customer extensions via the api.
    */
    self::$_meta_attributes_table = apply_filters( 'woocommerce_json_api_user_meta_attributes_table', self::$_meta_attributes_table );
  } // end setupMetaAttributes
  public static function setupModelAttributes() {
    global $wpdb;
    self::$_model_settings = array(
      'model_table' => $wpdb->users,
      'meta_function' => 'get_user_meta',
    );
    if ( self::$_model_attributes_table ) {
      return;
    }
    self::$_model_attributes_table = array(
      'name'            => array('name' => 'display_name',           'type' => 'string'),
      'username'        => array('name' => 'user_login',             'type' => 'string'),
      'slug'            => array('name' => 'user_nicename',          'type' => 'string'),
      'email'           => array('name' => 'user_email',             'type' => 'string'),
      'status'          => array('name' => 'user_status',            'type' => 'number'),
      'date_registered' => array('name' => 'user_registered',        'type' => 'datetime'),
    );
    self::$_model_attributes_table = apply_filters( 'woocommerce_json_api_model_attributes_table', self::$_model_attributes_table );
  }
  public function asApiArray() {
    $attributes = array_merge(self::$_model_attributes_table, self::$_meta_attributes_table);
    $attributes_to_send['id'] = $this->getModelId();
    $attributes_to_send = array();
    foreach ( $attributes as $name => $desc ) {
      $attributes_to_send[$name] = $this->dynamic_get( $name, $desc, $this->getModelId());
    }
    return $attributes_to_send;
  }
 
  
}