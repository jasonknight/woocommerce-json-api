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
if (! defined('REDENOTSET')) {
  define( 'REDENOTSET','__RED_E_NOTSET__' ); // because sometimes false, 0 etc are
  // exspected but consistently dealing with these situations is tiresome.
}

require_once( plugin_dir_path(__FILE__) . 'classes/class-rede-helpers.php' );

require_once( plugin_dir_path(__FILE__) . 'woocommerce-json-api-core.php' );

/**
  Initialize the plugin. This plugin will be called at the end of the file.
*/
function woocommerce_json_api_initialize_plugin() {
  $helpers = new RedEHelpers();
  require_once( REDE_PLUGIN_BASE_PATH . '/woocommerce-json-api-actions.php' );
  require_once( plugin_dir_path(__FILE__) . 'woocommerce-json-api-filters.php' );
  require_once( plugin_dir_path(__FILE__) . 'woocommerce-json-api-shortcodes.php' );
} // end woocommerce_json_api_initialize_plugin

function woocommerce_json_api_activate() {
  global $wpdb;
  $helpers = new RedEHelpers();
  /*
    We really want to avoid people even making a good guess as to the
    page URL, even if they don't have credentials to use it, better
    safe than sorry. That's not to say this is fool proof, but it's 
    a good start to ensuring that only people we want to have
    access to our API know about it.
  */
  $json_api_slug = get_option( $helpers->getPluginPrefix() . '_slug' );
  if ( ! $json_api_slug ) {
    $json_api_slug = md5(uniqid(rand(), true));
    update_option($helpers->getPluginPrefix() . '_slug',$json_api_slug);
  }
  $page = $helpers->newPage('WooCommerce JSON API','[' . $helpers->getPluginPrefix() . '_shortcode]',true);
  $page['post_name'] = $json_api_slug;
  $found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . $wpdb->posts . " WHERE post_name = %s LIMIT 1;", $page['post_name'] ) );
  if ( ! $found ) {
    $page_id = wp_insert_post($page);
  } else {
    // we are probably reactivating the plugin, so turn the page back on
    $page = array( 'ID' => $found, 'post_status' => 'pending');
    wp_update_post( $page );
  }

} // end woocommerce_json_api_activate()

function woocommerce_json_api_deactivate() {
  global $wpdb;
  $helpers = new RedEHelpers();

  $json_api_slug = get_option( $helpers->getPluginPrefix() . '_slug' );
  if ( ! $json_api_slug ) {
    // Ooops, no api slug?
  } else {
    $found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . $wpdb->posts . " WHERE post_name = %s LIMIT 1;", $page['post_name'] ) );
    if ( $found ) {
      $page = array( 'ID' => $found, 'post_status' => 'pending');
      wp_update_post( $page );
    }
  }

} // end woocommerce_json_api_activate()


register_activation_hook( __FILE__, 'woocommerce_json_api_activate' );
register_deactivation_hook( __FILE__, 'woocommerce_json_api_deactivate' );

add_action( 'init', 'woocommerce_json_api_initialize_plugin' );

