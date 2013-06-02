<?php 
/*
  Plugin Name: WooCommerce JSON API
  Plugin URI: https://github.com/jasonknight/woocommerce-json-api
  Description: Generic JSON API For WooCommerce
  Author: Jason Knight
  Version: 1.0 BETA
  Author URI: http://red-e.eu
*/

/**
  Initialize the plugin. This plugin will be called at the end of the file.
*/
function woocommerce_json_api_initialize_plugin() {
  die("Activate was called");
} // end woocommerce_json_api_initialize_plugin

add_action( 'init', 'woocommerce_json_api_initialize_plugin' );

?>
