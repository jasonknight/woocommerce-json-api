<?php 
/*
  Plugin Name: WooCommerce JSON API
  Plugin URI: https://github.com/jasonknight/woocommerce-json-api
  Description: Generic JSON API For WooCommerce
  Author: Jason Knight
  Version: 1.0 BETA
  Author URI: http://red-e.eu
*/

  // Turn on debugging?
//define('WC_JSON_API_DEBUG', true);

define( 'REDE_PLUGIN_BASE_PATH', plugin_dir_path(__FILE__) );
if (! defined('REDENOTSET')) {
  define( 'REDENOTSET','__RED_E_NOTSET__' ); // because sometimes false, 0 etc are
  // exspected but consistently dealing with these situations is tiresome.
}

require_once( plugin_dir_path(__FILE__) . 'classes/class-rede-helpers.php' );

require_once( plugin_dir_path(__FILE__) . 'woocommerce-json-api-core.php' );

/**
 * Initialize the plugin. This plugin will be called at the end of the file.
*/
function woocommerce_json_api_initialize_plugin() {
  $helpers = new JSONAPIHelpers();
  require_once( REDE_PLUGIN_BASE_PATH . '/woocommerce-json-api-actions.php' );
  require_once( plugin_dir_path(__FILE__) . 'woocommerce-json-api-filters.php' );
  require_once( plugin_dir_path(__FILE__) . 'woocommerce-json-api-shortcodes.php' );
  //woocommerce_json_api_template_redirect();
} // end woocommerce_json_api_initialize_plugin

function woocommerce_json_api_activate() {
  global $wpdb;
  $helpers = new JSONAPIHelpers();

} // end woocommerce_json_api_activate()

function woocommerce_json_api_deactivate() {
  global $wpdb;
} // end woocommerce_json_api_activate()


register_activation_hook( __FILE__, 'woocommerce_json_api_activate' );
register_deactivation_hook( __FILE__, 'woocommerce_json_api_deactivate' );

// I am hoping this will make the plugin be the last to be initialized
add_action( 'init', 'woocommerce_json_api_initialize_plugin',5000 );
