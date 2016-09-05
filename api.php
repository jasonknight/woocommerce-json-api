<?php 
/*
* This file is meant to be accessed directly via a url...
* maybe it's not the best idea, but it'll have to do.
*/
// We need to see if we can get to the wp-blog-header file.
// normally, we will be in wp-content/plugins/woocommerce-json-api
define('WP_USE_THEMES', true);
define('WCJSONAPI_NO_DIE',true);
define('WCJSONAPI_NO_HEADERS',true);
define('DOING_AJAX', true );
define('WCJSONAPI_USER_NO_CARE',true);

function _wc_jsonapi_log($txt) {
  if ( file_exists( "/tmp/wcjsonapi.log" ) ) {
    file_put_contents( "/tmp/wcjsonapi.log", print_r( $txt, true ) . "\n", FILE_APPEND );
  } else {
    file_put_contents('php://stderr',print_r( $txt, true ) . "\n" );
  }
}
_wc_jsonapi_log($_REQUEST);

_wc_jsonapi_log($_REQUEST);
//define( 'WP_ADMIN', true );
$path = dirname( __FILE__ );
$target = $path . "/../../../wp-load.php";
if ( file_exists($target) ) {
  ob_start();
    require($target);
    // This may succeed
    $contents = ob_get_contents();
    if ( strpos($contents, "<html") === false && strpos($contents, "<body") === false) {
      // we might have succeeded
      if ( strpos($contents,'"payload": [') !== false ) {
        _wc_jsonapi_log("Found paylod: [ in text, going to buffer");
        goto output_the_buffer;
      } else {
        _wc_jsonapi_log("going to run plugin");
        goto run_plugin;
      }
    } else {
      _wc_jsonapi_log("Found HTML in the contents, so going to run_plugin");
      goto run_plugin;
    }
    run_plugin:
    _wc_jsonapi_log("Entering run plugin");
      @ob_clean();
      if ( !defined('REDE_PLUGIN_BASE_PATH') ) {
        define( 'REDE_PLUGIN_BASE_PATH', plugin_dir_path(__FILE__) );
      }
      if (! defined('REDENOTSET')) {
        define( 'REDENOTSET','__RED_E_NOTSET__' ); // because sometimes false, 0 etc are
        // expected but consistently dealing with these situations is tiresome.
      }
      _wc_jsonapi_log("Requiring Files");
      require_once( plugin_dir_path(__FILE__) . 'classes/class-rede-helpers.php' );
      require_once( plugin_dir_path(__FILE__) . 'woocommerce-json-api-core.php' );
      _wc_jsonapi_log("Calling template redirect");
      woocommerce_json_api_template_redirect();
    output_the_buffer:
      $contents = ob_get_contents();
      // Fix for certain bad plugins who do sneaky shit, even
      // though DOING_AJAX is defined. You naughty bastards.
      $contents = str_replace("<!-- WordPressSEOPlugin -->","",$contents);
      ob_end_clean();
      _wc_jsonapi_log("Sending header");
      header("Content-Type: application/json");
      die($contents);
} else {
  die(0);
}


