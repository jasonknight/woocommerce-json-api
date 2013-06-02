<?php 
/**
  Add fields to the user profile that allow the addition 
  of a "token" that can be used by the API to log that
  user into the system for performing actions.
  
  The API is not limited to admins, in fact, the general idea
  is to limit the API by applying whatever limits apply
  to the user.
  
  @param $user the user
*/
function woocommerce_json_api_show_user_profile( $user ) {
  $helpers = new RedEHelpers();
  // We use PluginPrefic, which is just the plugin name
  // with - replaced with _, easier to type and more
  // extensible. 
  $key = $helpers->getPluginPrefix() . '_settings';
  $meta = unserialize( get_user_meta( $user->ID, $key, true ) );
  /*
    The general format at this point should be something like this:
    {
      'token': 1234blahblah
    }
  */
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
  // At this point, content is being rendered in an output buffer for absolute control.
  // You can still overwrite the templates in the usual way, as the current theme is scanned
  // first. There are also hooks defined before and after, that will allow you to alter, replace,
  // or extend the content.
  
  echo $content;
}
/**
    Here we just pass this off to the above function: woocommerce_json_api_show_user_profile( $user )
*/
function woocommerce_json_api_edit_user_profile( $user ) {
  woocommerce_json_api_show_user_profile( $user );
}
/**
  Here we edit the key, which should only be woocommerce_json_api_settings
  at this point, though more info and keys could be added.
  
  Here we are trying to simply use one key that is a serialized array
  of all the little bits of info we need.
*/
function woocommerce_json_api_update_user_profile( $user_id ) {
  $helpers = new RedEHelpers();
  $key = $helpers->getPluginPrefix() . '_settings';
  $params = serialize($_POST[$key]);
  update_user_meta($user_id,$key,$params);
}
/*
  We want to prevent the json page for showing up in the list
  ass long as they are using wp_list_pages, then this will
  work...
*/
function woocommerce_json_api_exclude_pages($exclude) {
  global $wpdb;
  $helpers = new RedEHelpers();
  $json_api_slug = get_option( $helpers->getPluginPrefix() . '_slug' );
  $found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . $wpdb->posts . " WHERE post_name = %s LIMIT 1;", $json_api_slug ) );
  $exclude[] = $found;
  return $exclude;
}
