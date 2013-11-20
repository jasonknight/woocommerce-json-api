<?php
/**
 * Core JSON API
*/
// Error Codes are negative, Warning codes are positive
define('JSONAPI_EXPECTED_ARGUMENT',             -1);
define('JSONAPI_NOT_IMPLEMENTED',               -2);
define('JSONAPI_UNEXPECTED_ERROR',              -3);
define('JSONAPI_INVALID_CREDENTIALS',           -4);
define('JSONAPI_BAD_ARGUMENT',                  -5);
define('JSONAPI_CANNOT_INSERT_RECORD',          -6);
define('JSONAPI_PERMSNOTSET',                   -7);
define('JSONAPI_PERMSINSUFF',                   -8);
define('JSONAPI_INTERNAL_ERROR',                -9);

define('JSONAPI_PRODUCT_NOT_EXISTS', 1);
define('JSONAPI_ORDER_NOT_EXISTS', 2);
define('JSONAPI_NO_RESULTS_POSSIBLE', 3);
define('JSONAPI_MODEL_NOT_EXISTS', 1);

require_once( plugin_dir_path(__FILE__) . '/class-rede-helpers.php' );
require_once( plugin_dir_path(__FILE__) . '/class-wc-json-api-result.php' );
require_once( dirname(__FILE__) . '/WCAPI/includes.php' );

use WCAPI as API;

if ( !defined('PHP_VERSION_ID')) {
  $version = explode('.',PHP_VERSION);
  if ( PHP_VERSION_ID < 50207 ) {
    define('PHP_MAJOR_VERSION',$version[0]);
    define('PHP_MINOR_VERSION',$version[1]);
    define('PHP_RELEASE_VERSION',$version[2]);
  }
}
class WooCommerce_JSON_API extends JSONAPIHelpers {
    // Call this function to setup a new response
  public $helpers;
  public $result;
  public $return_type;
  public $the_user;
  public $provider;
  public static $implemented_methods;

  public function setOut($t) {
    $this->return_type = $t;
  }
  public function setUser($user) {
    $this->the_user = $user;
  }
  public function getUser() {
    return $this->the_user;
  }
  public static function getImplementedMethods() {
    self::$implemented_methods = array(
      'get_system_time',
      'get_supported_attributes',
      'get_products',
      'get_categories',
      'get_taxes',
      'get_shipping_methods',
      'get_payment_gateways',
      'get_tags',
      'get_products_by_tags',
      'get_customers',
      'get_users',
      'get_orders', // New Method
      'get_orders_from_trash',
      'get_products_from_trash',
      'get_store_settings',
      'get_site_settings',
      'get_api_methods',
      'get_coupons',
      'get_images',
      'get_user_meta',
      'get_post_meta',
      
      // Write capable methods
      
      'set_products',
      'set_categories',
      'set_orders',
      'set_store_settings',
      'set_site_settings',
      'set_coupons',
      'set_customers_passwords',
      'set_images',
      'set_user_meta',
      'set_post_meta',

      // Evile delete methods
      'delete_products', // You probably just want to put them in
      // the trash. Pass: publishing = 'trash' or type = 'deleted'

    );
    return self::$implemented_methods;
  }
  public function __construct() {
    //$this = new JSONAPIHelpers();
    $this->result = null;
    $this->provider = null;
    parent::init();
  }
  /**
  *  This function is the single entry point into the API.
  *  
  *  The order of operations goes like this:
  *  
  *  1) A new result object is created.
  *  2) Check to see if it's a valid API User, if not, do stuff and quit
  *  3) Check to see if the method requested has been implemented
  *  4) If it's implemented, call and turn over control to the method
  *  
  *  This function takes a single hash,  usually $_REQUEST
  *  
  *  WHY? 
  *  
  *  Well, as you will notice with WooCommerce, there is an irritatingly large
  *  dependence on _defined_ and $_GET/$_POST variables, throughout their plugin,
  *  each function "depends" on request state, which is fine, except this
  *  violates 'dependency injection'. We don't know where data might come from
  *  in the future, what if another plugin wants to call this one inside of PHP
  *  within a request, multiple times? 
  *  
  *  No module should ever 'depend' on objects outside of itself, they should be
  *  provided with operating data, or 'injected' with it.
  *  
  *  There is nothing 'wrong' with the way WooCommerce does things, only it leads
  *  to a certain inflexibility in what you can do with it.
  */
  public function route( $params ) {
    global $wpdb;
    $method = $this->orEq( $params, 'method',false);
    $proc = $this->orEq($params, 'proc',false);
    if ( 
          $method &&
          $proc  &&
          ! strpos('get_') == 0 && 
          ! strpos('set_') == 0 &&
          ! strpos('delete_') == 0 
       ) {
      switch( strtolower($method) ) {
        case 'get':
          $proc = 'get_'.$proc;
          break;
        case 'put':
          $proc = 'set_'.$proc;
          break;
        case 'delete':
          $proc = 'delete_'.$proc;
          break;
      }
    }
    
    /*
     * The idea behind the provider is that there will be
     * several versions of the API in the future, and the
     * user can choose which one they are writing against.
     * This simplifies the provider files a bit and makes
     * the code more modular.
     */
    $version = intval($this->orEq($params, 'version', 1));
    if ( ! is_numeric($version) ) {
      $version = 1;
    }
    if ( file_exists( dirname(__FILE__ ) .'/API_VERSIONS/version'.$version.'.php' ) ) {
      require_once( dirname(__FILE__ ) .'/API_VERSIONS/version'.$version.'.php' );
      $klass = "WC_JSON_API_Provider_v{$version}";
      $klass = str_replace('.','_',$klass);
      $this->provider = new $klass( $this );
    }

    // Reorganize any uploaded files and put them in
    // the params
    $files = array();


    $params['uploads'] = $files; 

    $this->createNewResult( $params );

    // Now we need to allow for people to add dynamic
    // filters to the models.
    if ( isset( $params['model_filters'] ) ) {
      JSONAPIHelpers::debug( "Model Filters are Present" );
      JSONAPIHelpers::debug( var_export($params['model_filters'],true) );
      foreach ( $params['model_filters'] as $filter_text=>$filter ) {
        foreach ($filter as $key=>&$value) {
          $value['name'] = substr($wpdb->prepare("%s",$value['name']),1,strlen($value['name']));
        }
        $callback = function ($table) use ($filter) {

            return array_merge($table,$filter);
        };
        JSONAPIHelpers::debug( "Adding filter: " . $filter_text );
        add_filter($filter_text, $callback );
      }
    } else {
      JSONAPIHelpers::debug( "No Model Filters Present" );
    }

    if ( isset( $params['image_sizes']) ) {
      JSONAPIHelpers::debug("Image Sizes Are Defined");
      foreach ( $params['image_sizes'] as $size ) {
        foreach(array('name','width','height','crop') as $key ) {
          if ( !isset($size[$key]) ) {
            throw new \Exception( sprintf(__('%s is required when adding image sizes',$this->td),ucwords($key)) );
          }
        }
        add_image_size( $size['name'], $size['width'], $size['height'], $size['crop'] );
      }
    }
    JSONAPIHelpers::debug( "Beggining request" );
    JSONAPIHelpers::debug( var_export($params,true));

    if ( ! $this->isValidAPIUser( $params ) ) {

      $this->result->addError( 
        __('Not a valid API User', 'woocommerce_json_api' ), 
        JSONAPI_INVALID_CREDENTIALS 
      );
      return $this->done();

    }
    if ( isset($params['arguments']['password']) ) {
      // We shouldn't pass back the password.
      $params['arguments']['password'] = '[FILTERED]';
    }
    if ( $this->provider->isImplemented( $proc ) ) {

      try {

        // The arguments are passed by reference here
        $this->validateParameters( $params['arguments'], $this->result);
        if ( $this->result->status() == false ) {
          JSONAPIHelpers::warn("Arguments did not pass validation");
          return $this->done();
        } else {
          JSONAPIHelpers::debug("Arguments have passed validation");
        }
        return $this->provider->{ $proc }($params);

      } catch ( Exception $e ) {
        JSONAPIHelpers::error($e->getMessage());
        $this->unexpectedError( $params, $e);
      }
    } else {
      JSONAPIHelpers::warn("{$proc} is not implemented...");
      $this->notImplemented( $params );
    }
  }
  public function isValidAPIUser( $params ) {
    if ( $this->the_user ) {
      return true;
    }
    if ( ! isset($params['arguments']) ) {
      $this->result->addError( __( 'Missing `arguments` key','woocommerce_json_api' ),JSONAPI_EXPECTED_ARGUMENT );
      return false;
    }
    $by_token = true;
    if ( ! isset( $params['arguments']['token'] ) ) {
      
      if ( 
        isset( $params['arguments']['username'] ) && 
        isset( $params['arguments']['password']) 
      ) {

        $by_token = false;

      } else {
        $this->result->addError( __( 'Missing `token` in `arguments`','woocommerce_json_api' ),JSONAPI_EXPECTED_ARGUMENT );
        return false;
      }
      
    }


    API\Base::setBlogId($GLOBALS['blog_id']);
    $key = $this->getPluginPrefix() . '_settings';
    if (! $by_token ) {
        JSONAPIHelpers::debug("Authentication by username {$params['arguments']['username']}");
        $user = wp_authenticate_username_password( null, $params['arguments']['username'],$params['arguments']['password']);
        
        if ( is_a($user,'WP_Error') ) {
          foreach( $user->get_error_messages() as $msg) {
            $this->result->addError( $msg ,JSONAPI_INTERNAL_ERROR );
          }
          return false;
        }
        $meta = maybe_unserialize( get_user_meta( $user->ID, $key, true ) );
        $this->result->setToken($meta['token']);
        $this->logUserIn($user);
        return true;

    }
    JSONAPIHelpers::debug("Authentication by Token");
    
    
    
    $args = array(
      'blog_id' => $GLOBALS['blog_id'],
      'meta_key' => $key,
      
    );
    $users = get_users( $args );
    foreach ($users as $user) {
      
      $meta = maybe_unserialize( get_user_meta( $user->ID, $key, true ) );

      if (isset( $meta['token']) &&  $params['arguments']['token'] == $meta['token']) {
        if (
          !isset($meta[ 'can_' . $params['proc'] ]) || 
          !isset($meta[ 'can_access_the_api' ])
        ) {

          $this->result->addError( __( 'Permissions for this user have not been set','woocommerce_json_api' ),JSONAPI_PERMSNOTSET );
          return false;

        }
        if ( $meta[ 'can_access_the_api' ] == 'no' ) {

          $this->result->addError( __( 'You have been banned.','woocommerce_json_api' ), JSONAPI_PERMSINSUFF );
          
          return false;
        }
        if ( $meta[ 'can_' . $params['proc'] ] == 'no' ) {

          $this->result->addError( __( 'You do not have sufficient permissions.','woocommerce_json_api' ), JSONAPI_PERMSINSUFF );
          
          return false;

        }
        
        $this->logUserIn($user);
        $this->result->setToken($meta['token']); 
        return true;

      }

    }

    return false;
  }
  public function logUserIn( $user ) {

    wp_set_current_user($user->ID);
    wp_set_auth_cookie( $user->ID, false, is_ssl() );
    $this->setUser($user);
    $this->result->params['statistics']['user_id'] = $user->ID;

  }
  public function unexpectedError( $params, $error ) {
    $this->createNewResult( $params );
    $trace = $error->getTrace();
    foreach ( $trace as &$t) {
      if ( isset($t['file']) )
        $t['file'] = basename($t['file']);
      if ( !isset($t['class']) )
        $t['class'] = 'GlobalScope';
      if ( !isset($t['file']) )
        $t['file'] = 'Unknown';
      if ( !isset($t['line']) )
        $t['line'] = 'GlobalScope';

      $t = "{$t['file']}:{$t['line']}:{$t['class']}";
    }
    $this->result->addError( 
      sprintf( __('An unexpected error has occured %s ', 'woocommerce_json_api' ) ,$error->getMessage() ), 
      JSONAPI_UNEXPECTED_ERROR,
      array('trace' => $trace)
    );

    return $this->done();
  }
  public function createNewResult($params) {
    global $user_ID;
    if ( ! $this->result ) {

      $this->result = new WooCommerce_JSON_API_Result();

      $this->result->setParams( $params );

    }
  }
  public function done() {
    JSONAPIHelpers::debug("WooCommerce_JSON_API::done() called..");
    wp_logout();
    if ( $this->return_type == 'HTTP') {
      if ( !defined('WCJSONAPI_NO_HEADERS') ) {
        header("Content-type: application/json");
      }
      echo( $this->result->asJSON() );
      if ( !defined('WCJSONAPI_NO_DIE') ) {
        die;
      }
    } else if ( $this->return_type == "ARRAY") {

      return $this->result->getParams();

    } else if ( $this->return_type == "JSON") {

      return $this->result->asJSON();

    } else if ( $this->return_type == "OBJECT") {

      return $this->result;

    } 
  }
  public function notImplemented( $params ) {
    $this->createNewResult( $params );

    if ( !isset($params['proc']) ) {

      $this->result->addError( 
          __('Expected argument was not present', 'woocommerce_json_api') . ' `proc`',
           JSONAPI_EXPECTED_ARGUMENT 
      );
    }

    $this->result->addError( 
      __('That API method has not been implemented', 'woocommerce_json_api' ), 
      JSONAPI_NOT_IMPLEMENTED 
    );

    return $this->done();
  }
}
