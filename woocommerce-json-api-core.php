<?php 
/**
  Add fields to the user profile that allow the addition 
  of a "token" that can be used by the API to log that
  user into the system for performing actions.
  
*/

function woocommerce_json_api_show_user_profile( $user ) {
  $helpers = new RedEHelpers();
  $key = $helpers->getPluginPrefix() . '_settings';
  $meta = unserialize( get_user_meta( $user->ID, $key, true ) );

  $attrs = array (
	  'json_api_settings' => array(
		  'title' => __( 'WooCommerce JSON API Settings', $helpers->getPluginTextDomain() ),
		  'fields' => array(
		      array(
		        'name'          => $helpers->getPluginPrefix() . '_settings[token]',
		        'id'            => 'json_api_token_id',
		        'value'         => $meta['token'],
				    'label'         => __( 'API Token', 'woocommerce-json-api' ),
				    'description'   => __('A large string of letters and numbers, mixed case, that will be used to authenticate requests','woocommerce-json-api')
			    ),
		  ),
	  ),
	);
  $attrs = apply_filters('woocommerce_json_api_settings_fields', $attrs);
                                                                              // The second argument puts this var in scope, similar to a
                                                                              // "binding" in Ruby
  $content = $helpers->render_template( 'user-fields.php', array( 'attrs' => $attrs) );
  
  echo $content;
}
/**
    
*/
function woocommerce_json_api_edit_user_profile( $user ) {
  woocommerce_json_api_show_user_profile( $user );
}
/**
    
*/
function woocommerce_json_api_update_user_profile( $user_id ) {
  $helpers = new RedEHelpers();
  $key = $helpers->getPluginPrefix() . '_settings';
  $params = serialize($_POST[$key]);
  update_user_meta($user_id,$key,$params);
}
