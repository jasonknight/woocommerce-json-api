<?php 
/*
  Plugin Name: WooCommerce JSON API
  Plugin URI: https://github.com/jasonknight/woocommerce-json-api
  Description: Generic JSON API For WooCommerce
  Author: Jason Knight
  Version: 1.0 BETA
  Author URI: http://red-e.eu
*/
define( 'REDE_PLUGIN_BASE_PATH', plugin_dir_path(__FILE__) );
require_once( plugin_dir_path(__FILE__) . 'classes/class-rede-helpers.php' );
require_once( plugin_dir_path(__FILE__) . 'woocommerce-json-api-core.php' );
/**
  Initialize the plugin. This plugin will be called at the end of the file.
*/
function woocommerce_json_api_initialize_plugin() {
  require_once( REDE_PLUGIN_BASE_PATH . '/woocommerce-json-api-actions.php' );
} // end woocommerce_json_api_initialize_plugin

add_action( 'init', 'woocommerce_json_api_initialize_plugin' );

