<?php
namespace WCAPI;
/**
 * An Order class to insulate the API from the details of the
 * database representation
*/
require_once(dirname(__FILE__) . "/Base.php");
require_once(dirname(__FILE__) . "/OrderItem.php");

class Order extends Base {

  
  public $_status;
  

  public static function setupMetaAttributes() {
    // We only accept these attributes.
    static::$_meta_attributes_table = array(
      'order_key'				      => array('name' => '_order_key',                  'type' => 'string'), 
      'billing_first_name'	  => array('name' => '_billing_first_name',         'type' => 'string'), 
      'billing_last_name' 	  => array('name' => '_billing_last_name',          'type' => 'string'), 
      'billing_company'		    => array('name' => '_billing_company' ,           'type' => 'string'), 
      'billing_address_1'		  => array('name' => '_billing_address_1',          'type' => 'string'), 
      'billing_address_2'		  => array('name' => '_billing_address_2',          'type' => 'string'), 
      'billing_city'			    => array('name' => '_billing_city',               'type' => 'string'), 
      'billing_postcode'		  => array('name' => '_billing_postcode',           'type' => 'string'), 
      'billing_country'		    => array('name' => '_billing_country',            'type' => 'string'), 
      'billing_state' 		    => array('name' => '_billing_state',              'type' => 'string'), 
      'billing_email'			    => array('name' => '_billing_email',              'type' => 'string'), 
      'billing_phone'			    => array('name' => '_billing_phone',              'type' => 'string'), 
      'shipping_first_name'	  => array('name' => '_shipping_first_name',        'type' => 'string'), 
      'shipping_last_name'	  => array('name' => '_shipping_last_name' ,        'type' => 'string'), 
      'shipping_company'		  => array('name' => '_shipping_company',           'type' => 'string'), 
      'shipping_address_1'	  => array('name' => '_shipping_address_1' ,        'type' => 'string'), 
      'shipping_address_2'	  => array('name' => '_shipping_address_2',         'type' => 'string'), 
      'shipping_city'			    => array('name' => '_shipping_city',              'type' => 'string'), 
      'shipping_postcode'		  => array('name' => '_shipping_postcode',          'type' => 'string'), 
      'shipping_country'		  => array('name' => '_shipping_country',           'type' => 'string'), 
      'shipping_state'		    => array('name' => '_shipping_state',             'type' => 'string'), 
      'shipping_method'		    => array('name' => '_shipping_method' ,           'type' => 'string'), 
      'shipping_method_title'	=> array('name' => '_shipping_method_title',      'type' => 'string'), 
      'payment_method'		    => array('name' => '_payment_method',             'type' => 'string'), 
      'payment_method_title' 	=> array('name' => '_payment_method_title',       'type' => 'string'), 
      'order_discount'		    => array('name' => '_order_discount',             'type' => 'number'), 
      'cart_discount'			    => array('name' => '_cart_discount',              'type' => 'number'), 
      'order_tax'				      => array('name' => '_order_tax' ,                 'type' => 'number'), 
      'order_shipping'		    => array('name' => '_order_shipping' ,            'type' => 'number'), 
      'order_shipping_tax'	  => array('name' => '_order_shipping_tax' ,        'type' => 'number'), 
      'order_total'			      => array('name' => '_order_total',                'type' => 'number'), 
      'customer_user'			    => array('name' => '_customer_user',              'type' => 'number'), 
      'completed_date'		    => array('name' => '_completed_date',             'type' => 'datetime'), 
      'status'                => array(
                                        'name' => 'status', 
                                        'type' => 'string', 
                                        'getter' => 'getStatus',
                                        'setter' => 'setStatus',
                                        'updater' => 'updateStatus'
                                ),
      
    );
    /*
      With this filter, plugins can extend this ones handling of meta attributes for a product,
      this helps to facilitate interoperability with other plugins that may be making arcane
      magic with a product, or want to expose their product extensions via the api.
    */
    static::$_meta_attributes_table = apply_filters( 'WCAPI_order_meta_attributes_table', static::$_meta_attributes_table );
  } // end setupMetaAttributes
  public static function setupModelAttributes() {
    static::$_model_settings = array_merge( Base::getDefaultModelSettings(), array(
        'model_conditions' => "WHERE post_type IN ('shop_order')",
        'has_many' => array(
          'order_items' => array('class_name' => 'OrderItem', 'foreign_key' => 'order_id'),
        ),
      ) 
    );

    static::$_model_attributes_table = array(
      'name'            => array('name' => 'post_title',  'type' => 'string'),
      'guid'            => array('name' => 'guid',        'type' => 'string'),

    );
    static::$_model_attributes_table = apply_filters( 'WCAPI_order_model_attributes_table', static::$_model_attributes_table );
  }
  public function getStatus() {
    if ( $this->_status ) {
      return $this->_status;
    }
    $terms = wp_get_object_terms( $this->id, 'shop_order_status', array('fields' => 'slugs') );
    $this->_status = (isset($terms[0])) ? $terms[0] : 'pending';
    return $this->_status;
  }

  public function setStatus( $s ) {
    $this->_status = $s;
  }
  public function asApiArray() {
    $attrs = parent::asApiArray();
    $attrs['order_items'] = $this->order_items;
    return $attrs;
  }
}