<?php
/**
  Core JSON API
*/
// Error Codes are negative, Warning codes are positive
define('WCAPI_EXPECTED_ARGUMENT',             -1);
define('WCAPI_NOT_IMPLEMENTED',               -2);
define('WCAPI_UNEXPECTED_ERROR',              -3);
define('WCAPI_INVALID_CREDENTIALS',           -4);
define('WCAPI_BAD_ARGUMENT',                  -5);

define('WCAPI_PRODUCT_NOT_EXISTS', 1);
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
  /**
    This function is the single entry point into the API.
    
    The order of operations goes like this:
    
    1) A new result object is created.
    2) Check to see if it's a valid API User, if not, do stuff and quit
    3) Check to see if the method requested has been implemented
    4) If it's implemented, call and turn over control to the method
  */
  public function route( $params ) {
    $this->createNewResult( $params );
    if ( ! $this->isValidAPIUser( $params ) ) {
      $this->result->addError( __('Not a valid API User', $this->helpers->getPluginTextDomain() ), WCAPI_INVALID_CREDENTIALS );
      $this->done();
    }
    if ( $this->isImplemented( $params ) ) {
      try {
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
          __('Expected argument was not present', $this->helpers->getPluginTextDomain()) . ' `proc`',
           WCAPI_EXPECTED_ARGUMENT );
    }
    $this->result->addError( __('That API method has not been implemented', $this->helpers->getPluginTextDomain() ), WCAPI_NOT_IMPLEMENTED );
    $this->done();
  }
  
  
  private function unexpectedError( $params, $error ) {
    $this->createNewResult( $params );
    $this->result->addError( __('An unexpected error has occured', $this->helpers->getPluginTextDomain() ), WCAPI_UNEXPECTED_ERROR );
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
      'id'   => $post->ID,
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
  
  /**
    This is the single entry point for fetching products, ordering, paging, as well
    as "finding" by ID or SKU.
  */
  private function get_products( $params ) {
    global $wpdb;
    $allowed_order_bys = array('post_title','post_date','post_author','post_modified');
    /**
      Read this section to get familiar with the arguments of this method.
    */
    $posts_per_page = $this->helpers->orEq( $params['arguments'], 'per_page', 15 ); 
    $paged          = $this->helpers->orEq( $params['arguments'], 'page', 0 );
    $order_by       = $this->helpers->orEq( $params['arguments'], 'order_by', 'post_date');
    $order          = $this->helpers->orEq( $params['arguments'], 'order', 'DESC');
    $ids            = $this->helpers->orEq( $params['arguments'], 'ids', false);
    $skus           = $this->helpers->orEq( $params['arguments'], 'skus', false);
    
    $by_ids = true;
    if ( ! $this->helpers->inArray($order_by,$allowed_order_bys) ) {
      $this->result->addError( __('order_by must be one of these:','woocommerce_json_api') . join( $allowed_order_bys, ','), WCAPI_BAD_ARGUMENT );
      $this->done();
      return;
    }
    if ( ! $ids && ! $skus ) {
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
	  } else if ( $ids ) {
	  
	    $posts = $ids;
	    
	  } else if ( $skus ) {
	  
	    $posts = array();
	    foreach ($skus as $sku) {
	      $pid = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1",$sku) );
	      if ( ! $pid ) {
	        $this->result->addWarning( $sku . ': ' . __('Product does not exist'), WCAPI_PRODUCT_NOT_EXISTS, array( 'sku' => $sku) );
	      } else {
	        $posts[] = $pid;
	      }
	    }
	    
	  }

	  $products = array();
    foreach ( $posts as $post_id) {
      $post = get_product($post_id);
      if ( !$post ) {
        $this->result->addWarning( $post_id. ': ' . __('Product does not exist'), WCAPI_PRODUCT_NOT_EXISTS, array( 'id' => $post_id) );
      } else {
        $products[] = $this->translateProductAttributes($post);
      }
      
    }
    $this->result->setPayload($products);

	  $this->done();
  }
  
}
