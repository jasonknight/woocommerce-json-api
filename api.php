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
        goto output_the_buffer;
      } else {
        goto run_plugin;
      }
    } else {
      goto run_plugin;
    }
    run_plugin:
      @ob_clean();
      if ( !defined('REDE_PLUGIN_BASE_PATH') ) {
        define( 'REDE_PLUGIN_BASE_PATH', plugin_dir_path(__FILE__) );
      }
      if (! defined('REDENOTSET')) {
        define( 'REDENOTSET','__RED_E_NOTSET__' ); // because sometimes false, 0 etc are
        // expected but consistently dealing with these situations is tiresome.
      }
      require_once( plugin_dir_path(__FILE__) . 'classes/class-rede-helpers.php' );
      require_once( plugin_dir_path(__FILE__) . 'woocommerce-json-api-core.php' );
      woocommerce_json_api_template_redirect();
    output_the_buffer:
      $contents = ob_get_contents();
      ob_end_clean();
      header("Content-Type: application/json");
      die($contents);
} else {
  die(0);
}


