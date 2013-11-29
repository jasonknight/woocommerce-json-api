<?php
namespace WCAPI;
/**
 * A Customer class to insulate the API from the details of the
 * database representation
*/
require_once(dirname(__FILE__) . "/Base.php");
require_once(dirname(__FILE__) . "/Category.php");
class Customer extends Base {
  public static function getModelSettings() {
    include WCAPIDIR."/_globals.php";
    $table = array_merge( static::getDefaultModelSettings(), array(
        'model_table' => $wpdb->users,
        'model_table_id' => 'id',
        'meta_table' => $wpdb->usermeta,
        'meta_table_foreign_key' => 'user_id',
        'meta_function' => 'get_user_meta',
      )
    );
    $table = apply_filters('WCAPI_customer_model_settings',$table);
    return $table;
  }

  public static function getModelAttributes() {
    $table = array(
      'name'            => array('name' => 'display_name',           'type' => 'string'),
      'username'        => array('name' => 'user_login',             'type' => 'string'),
      'slug'            => array('name' => 'user_nicename',          'type' => 'string'),
      'email'           => array('name' => 'user_email',             'type' => 'string'),
      'status'          => array('name' => 'user_status',            'type' => 'number'),
      'date_registered' => array('name' => 'user_registered',        'type' => 'datetime'),
    );
    $table = apply_filters( 'WCAPI_model_attributes_table', $table );
    return $table;
  }
  public static function getMetaAttributes() {
    $table = array(
      'order_count'               => array('name' => '_order_count',      'type' => 'number'),
      'paying_customer'           => array('name' => 'paying_customer',   'type' => 'number'),
    );
    /*
      With this filter, plugins can extend this ones handling of meta attributes for a customer,
      this helps to facilitate interoperability with other plugins that may be making arcane
      magic with a customer, or want to expose their customer extensions via the api.
    */
    $table = apply_filters( 'WCAPI_user_meta_attributes_table', $table );
    return $table;
  }
  public static function setupMetaAttributes() {
    // We only accept these attributes.
    static::$_meta_attributes_table = self::getMetaAttributes();
  } // end setupMetaAttributes
  public static function setupModelAttributes() {

    static::$_model_settings = self::getModelSettings();

    static::$_model_attributes_table = self::getModelAttributes();
  }
  public function asApiArray($args = array()) {
    $attributes = array_merge(static::$_model_attributes_table, static::$_meta_attributes_table);
    $attributes_to_send = array();
    $attributes_to_send['id'] = $this->getModelId();
    foreach ( $attributes as $name => $desc ) {
      $attributes_to_send[$name] = $this->dynamic_get( $name, $desc, $this->getModelId());
    }
    return $attributes_to_send;
  }
  public function setPassword($pass) {
    wp_set_password($pass,$this->_actual_model_id);
  }



}