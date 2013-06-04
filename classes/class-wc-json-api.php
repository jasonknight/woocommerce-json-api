<?php
/**
  Core JSON API
*/
define('WCAPI_EXPECTED_ARGUMENT',             -1);
define('WCAPI_NOT_IMPLEMENTED',               -2);
define('WCAPI_UNEXPECTED_ERROR',              -3);
define('WCAPI_INVALID_CREDENTIALS',           -4);
require_once( plugin_dir_path(__FILE__) . '/class-rede-helpers.php' );
require_once( plugin_dir_path(__FILE__) . '/class-wc-json-api-result.php' );
class WooCommerce_JSON_API {
    // Call this function to setup a new response
  private $helpers;
  private $result;
  
  public function __construct() {
    $this->helpers = new RedEHelpers();
    $this->result = null;
  }
  
  public function route( $params ) {
    if ( $this->isImplemented( $params ) ) {
      try {
        $this->createNewResult( $params );
        if ( ! $this->isValidAPIUser( $params ) ) {
          $this->result->addError( __('Not a valid API User', $this->helpers->getPluginTextDomain() ), WCAPI_INVALID_CREDENTIALS );
          $this->done();
        }
        $this->{ $params['proc'] }($params);
      } catch ( Exception $e ) {
        $this->unexpectedError( $params, $e);
      }
    } else {
      $this->notImplemented( $params );
    }
  }
  
  private function isImplemented( $params ) {
    $implemented_methods = array(
      'get_system_time',
      'get_products',
    );
    if (isset($params['proc']) &&  $this->helpers->inArray( $params['proc'], $implemented_methods) ) {
      return true;
    } else {
      return false;
    }
  }
  
  private function notImplemented( $params ) {
    $this->createNewResult( $params );
    if ( !isset($params['proc']) ) {
      $this->result->addError( 
          __('Expected argument "', $this->helpers->getPluginTextDomain()) . 'proc' . __('" was not present', $this->helpers->getPluginTextDomain()),
           WCAPI_EXPECTED_ARGUMENT );
    }
    $this->result->addError( __('That API method has not been implemented', $this->helpers->getPluginTextDomain() ), WCAPI_NOT_IMPLEMENTED );
    $this->done();
  }
  
  
  private function unexpectedError( $params, $error ) {
    $this->createNewResult( $params );
    $this->result->addError( __('An unexpected error [[',$this->helpers->getPluginTextDomain()) . $error->getMessage() . __(']] has occured', $this->helpers->getPluginTextDomain() ), WCAPI_UNEXPECTED_ERROR );
    $this->done();
  }
  
  
  private function createNewResult($params) {
    if ( ! $this->result ) {
      $this->result = new WooCommerce_JSON_API_Result();
      $this->result->setParams( $params );
    }
  }
  
  private function done() {
    header("Content-type: application/json");
    echo( $this->result->asJSON() );
    die;
  }
  
  private function isValidAPIUser( $params ) {
    if ( ! isset($params['arguments']) ) {
      $this->result->addError( __( 'Missing `arguments` key',$this->helpers->getPluginTextDomain() ),WCAPI_EXPECTED_ARGUMENT );
      return false;
    }
    if ( ! isset( $params['arguments']['token'] ) ) {
      $this->result->addError( __( 'Missing `token` in `arguments`',$this->helpers->getPluginTextDomain() ),WCAPI_EXPECTED_ARGUMENT );
      return false;
    }
    $key = $this->helpers->getPluginPrefix() . '_settings';
    $args = array(
      'blog_id' => $GLOBALS['blog_id'],
      'meta_key' => $key
    );
    $users = get_users( $args );
    foreach ($users as $user) {
      $meta = unserialize( get_user_meta( $user->ID, $key, true ) );
      if (isset( $meta['token']) &&  $params['arguments']['token'] == $meta['token']) {
        $this->logUserIn($user);
        return true;
      }
    }
    return false;
  }
  private function logUserIn( $user ) {
    wp_set_current_user($user->ID);
    wp_set_auth_cookie( $user->ID, false, is_ssl() );
  }
  private function translateProductAttributes($product) {
    global $wpdb;
    $meta = array();
    $post = $product->get_post_data();
    //$meta['query'] = "SELECT `meta_key`, `meta_value` from {$wpdb->postmeta} where `post_id` = '{$post->ID}'";
    $result = $wpdb->query( "SELECT `meta_key`, `meta_value` from {$wpdb->postmeta} where `post_id` = '{$post->ID}'");
    foreach ($wpdb->last_result as $k => $v) {
      $meta[ $v->meta_key] = $v->meta_value;
    }
    
    $attrs = array(
      'name' => $product->get_title(),
      'description' => $product->get_post_data()->post_content,
      'price' => array( 
        'amount' => $product->get_price(),
        'currency' => get_woocommerce_currency(),
        'symbol' => get_woocommerce_currency_symbol(),
        'taxable' => $product->is_taxable(),
      ),
      'sku' => $product->get_sku(),
      'stock' => array(
        'managed' => $product->managing_stock(),
        'for_sale' => $product->get_stock_quantity(),
        'in_stock' => $product->get_total_stock(),
        'downloadable' => $product->is_downloadable(),
        'virtual' => $product->is_virtual(),
        'sold_individually' => $product->is_sold_individually(),
        'download_paths' => isset($meta['_file_paths']) ? maybe_unserialize($meta['_file_paths']) : array(),
      ),
    );
    
    
    
    return $attrs;
  }
  
  /*******************************************************************
                            Core API Functions
  ********************************************************************/
  
  private function get_system_time( $params ) {
    
    $data = array(
      'timezone'  => date_default_timezone_get(),
      'date'      => date("Y-m-d"),
      'time'      => date("h:i:s",time())
    );
    $this->result->addPayload($data);
    $this->done();
  }
  
  private function get_products( $params ) {
    $posts_per_page = $this->helpers->orEq( $params['arguments'], 'per_page', 15 ); 
    $paged          = $this->helpers->orEq( $params['arguments'], 'page', 0 );
    $order_by       = $this->helpers->orEq( $params['arguments'], 'order_by', 'post_date');
    $order          = $this->helpers->orEq( $params['arguments'], 'order', 'DESC');
    $posts = get_posts( array(
		    'post_type'      => array( 'product', 'product_variation' ),
		    'posts_per_page' => $posts_per_page,
		    'post_status'    => 'publish',
		    'fields'         => 'id',
		    'order_by'       => $order_by,
		    'order'          => $order,
		    'paged'          => $paged,
	    ) 
	  );
	  $products = array();
	  foreach ( $posts as $post_id) {
	    $post = get_product($post_id);
	    $products[] = $this->translateProductAttributes($post);
	    
	  }
	  $this->result->addPayload($products);
	  $this->done();
  }
  
}
