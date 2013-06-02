<?php 
/**
  Add fields to the user profile that allow the addition 
  of a "token" that can be used by the API to log that
  user into the system for performing actions.
  
*/

function woocommerce_json_api_show_user_profile( $user ) {
  $attrs = array (
	  'json_api_settings' => array(
		  'title' => __( 'WooCommerce JSON API Settings', 'woocommerce-json-api' ),
		  'fields' => array(
		  'json_api_token' => array(
				  'label' => __( 'API Token', 'woocommerce-json-api' ),
				  'description' => _('A large string of letters and numbers, mixed case, that will be used to authenticate requests','woocommerce-json-api')
			  ),
		  )
	  )
	);
  $fields = apply_filters('woocommerce_json_api_settings_fields', $attrs);
  
}
/**
    
*/
function woocommerce_json_api_edit_user_profile( $user ) {

}
/**
    
*/
function woocommerce_json_api_update_user_profile( $user_id ) {

}
