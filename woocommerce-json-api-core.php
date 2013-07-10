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
require_once( plugin_dir_path(__FILE__) . 'classes/class-wc-json-api.php' );
function woocommerce_json_api_show_user_profile( $user ) {
  $helpers = new JSONAPIHelpers();
  // We use PluginPrefic, which is just the plugin name
  // with - replaced with _, easier to type and more
  // extensible. 
  $key = $helpers->getPluginPrefix() . '_settings';
  $meta = maybe_unserialize( get_user_meta( $user->ID, $key, true ) );
  /*
    The general format at this point should be something like this:
    {
      'token': 1234blahblah
    }
  */
  $attrs = array (
	  'json_api_settings' => array(
		  'title' => __( 'WooCommerce JSON API Settings', 'woocommerce_json_api' ),
		  'fields' => array(
		      array(
		        'name'          => $helpers->getPluginPrefix() . '_settings[token]',
		        'id'            => 'json_api_token_id',
		        'value'         => $helpers->orEq($meta,'token',''),
		        'type'          => 'text',
				    'label'         => __( 'API Token', 'woocommerce_json_api' ),
				    'description'   => __('A large string of letters and numbers, mixed case, that will be used to authenticate requests', 'woocommerce_json_api' )
			    ),
			    array(
		        'name'          => $helpers->getPluginPrefix() . '_settings[ips_allowed]',
		        'id'            => 'json_api_ips_allowed_id',
		        'value'         => $helpers->orEq($meta,'ips_allowed',''),
		        'type'          => 'textarea',
				    'label'         => __( 'IPs Allowed', 'woocommerce_json_api' ),
				    'description'   => __('What ips are permitted to connect with this user...', 'woocommerce_json_api' )
			    ),
		  ),
	  ),
	);
  // Here we implement some permissions, a simple yes/no.
    $method = 'access_the_api';
    $field = array(
      'name'          => $helpers->getPluginPrefix() . '_settings[can_' . $method . ']',
      'id'            => 'json_api_can_' . $method . '_id',
      'value'         => $helpers->orEq($meta,'can_' . $method, 'yes'),
      'type'          => 'select',
      'options'       => array(
          array( 'value' => 'yes', 'content' => __('Yes','woocommerce_json_api') ),
          array( 'value' => 'no', 'content' => __('No','woocommerce_json_api') ),
        ),
      'label'         => __( 'Can access ', 'woocommerce_json_api' ) . ucwords(str_replace('_',' ', $method)),
      'description'   => __('Whether or not this user can access this method', 'woocommerce_json_api' )
    );
    $attrs['json_api_settings']['fields'][] = $field;
  foreach (WooCommerce_JSON_API::getImplementedMethods() as $method) {
    $field = array(
      'name'          => $helpers->getPluginPrefix() . '_settings[can_' . $method . ']',
      'id'            => 'json_api_can_' . $method . '_id',
      'value'         => $helpers->orEq($meta,'can_' . $method, 'yes'),
      'type'          => 'select',
      'options'       => array(
          array( 'value' => 'yes', 'content' => __('Yes','woocommerce_json_api') ),
          array( 'value' => 'no', 'content' => __('No','woocommerce_json_api') ),
        ),
      'label'         => __( 'Can access ', 'woocommerce_json_api' ) . ucwords(str_replace('_',' ', $method)),
      'description'   => __('Whether or not this user can access this method', 'woocommerce_json_api' )
    );
    $attrs['json_api_settings']['fields'][] = $field;
  }

  $attrs = apply_filters('woocommerce_json_api_settings_fields', $attrs);
                                                                              // The second argument puts this var in scope, similar to a
                                                                              // "binding" in Ruby
  $content = $helpers->renderTemplate( 'user-fields.php', array( 'attrs' => $attrs) );
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
  $helpers = new JSONAPIHelpers();
  $key = $helpers->getPluginPrefix() . '_settings';
  $params = serialize($_POST[$key]);
  update_user_meta($user_id,$key,$params);
}
/*
  We want to prevent the json page for showing up in the list
  as long as they are using wp_list_pages, then this will
  work...
*/
function woocommerce_json_api_exclude_pages($exclude) {
  global $wpdb;
  $helpers = new JSONAPIHelpers();
  $json_api_slug = get_option( $helpers->getPluginPrefix() . '_slug' );
  $found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . $wpdb->posts . " WHERE post_name = %s LIMIT 1;", $json_api_slug ) );
  $exclude[] = $found;
  return $exclude;
}

/**
  Shortcode to embed in a page to turn it into a JSON API entry point.
  
  We don't REALLY use this, it's just there to mark a page visually.
  
  What happens is: 
  
  1) You have a page with this shortcode in it, created when it you installed the plugin.
     the slug of this page is saved use: $json_api_slug = get_option( $helpers->getPluginPrefix() . '_slug' );
     You can manually update that slug from the admin settings page for the JSON API
     
     When someone accesses that particular URL, the API works
  
  Some themes are not using wp_list_pages, which prevents us from filtering the page out
  of the list of pages. If that is so, then this is a major security risk, and irritating
  as there is no simple way to hide a page.
  
  So method 2 comes into play:
  
  2) After initializing the plugin, you delete the API page. This will cause it to not be present.
     In that case, any page can be used as an API page, less secure, but at least we are cookin.
     
     This is accomplished by the template_redirect that looks to see if the page is present, if
     not, it inspects the REQUEST vars to see if this is a JSON API post, if so, it will try
     to satisfy it, if not, it will give up on the template redirect and everything will be
     hunky dory. The page will show as normal, and the API will pretend it doesn't exist.
*/
function woocommerce_json_api_shortcode() {
  $helpers = new JSONAPIHelpers();
  $enabled = get_option( $helpers->getPluginPrefix() . '_enabled');
  $require_https = get_option( $helpers->getPluginPrefix() . '_require_https' );
  if ( $enabled != 'no') {
    if ( $require_https == 'yes' && $helpers->isHTTPS() == false ) {
      return;
    }
    $api = new WooCommerce_JSON_API();
    $api->setOut('HTTP');
    $api->setUser(null);
    $api->route($_REQUEST);
  }
}

/*
  Prevent template code from loading :)
*/
function woocommerce_json_api_template_redirect() {
  global $wpdb, $post;
  $helpers = new JSONAPIHelpers();
  $json_api_slug = get_option( $helpers->getPluginPrefix() . '_slug' );
  $found = get_page_by_path( $json_api_slug );
  if ( $found ) {
    if ( $post->ID == $found->ID ) {
      woocommerce_json_api_shortcode();
    } else { 
      return;
    }
  } else {
    // The page was not found, let's check the $_POST params to see if this is a request to
    // us
    if ( isset( $_REQUEST['action']) && 'woocommerce_json_api' == $_REQUEST['action']) {
      woocommerce_json_api_shortcode();
    }
  }
}
function woocommerce_json_api_admin_menu() {
  global $menu;
  $json_api_page = add_submenu_page( 
                                      'woocommerce', 
                                      __( 'JSON API', 'woocommerce' ),  
                                      __( 'JSON API', 'woocommerce' ) , 
                                      'manage_woocommerce', 
                                      'woocommerce_json_api_settings_page', 
                                      'woocommerce_json_api_settings_page' 
  );
}
function woocommerce_json_api_settings_page() {
  $helpers = new JSONAPIHelpers();
  $params = $_POST;
  $nonce = $helpers->orEq( $params, '_wpnonce',false);
  $key = $helpers->getPluginPrefix() . '_sitewide_settings';
  if ( $nonce  && wp_verify_nonce( $nonce, $helpers->getPluginPrefix() . '_sitewide_settings' ) && isset($params[$key]) ) { 
    foreach ($params[$key] as $key2=>$value) {
      update_option($helpers->getPluginPrefix() . '_' . $key2, maybe_serialize($value));
    }
    
  }
  
  
  $json_api_slug = get_option( $helpers->getPluginPrefix() . '_slug' );
  $pages = get_pages( array('post_type' => 'page') );
  $options = array();
  foreach ( $pages as $page ) {
    $options[] = array('value' => $page->post_name, 'content' => $page->post_title);
  }
  $attrs = array (
	  'json_api_sitewide_settings' => array(
		  'title' => __( 'WooCommerce JSON API Settings', 'woocommerce_json_api' ),
		  'fields' => array(
		      array(
		        'name'          => $helpers->getPluginPrefix() . '_sitewide_settings[slug]',
		        'id'            => 'json_api_page_id',
		        'value'         => $json_api_slug,
		        'options'       => $options,
		        'type'          => 'select',
				    'label'         => __( 'API Page', 'woocommerce_json_api' ),
				    'description'   => __('slug of the page to use as the api entry point. Currently: ', 'woocommerce_json_api' ) . $helpers->getTitleBySlug($json_api_slug),
			    ),
          array(
            'name'          => $helpers->getPluginPrefix() . '_sitewide_settings[enabled]',
            'id'            => 'json_api_enabled_id',
            'value'         => get_option( $helpers->getPluginPrefix() . '_enabled' ),
            'options'       => array(
                array( 'value' => 'yes', 'content' => __('Yes','woocommerce_json_api')),
                array( 'value' => 'no', 'content' => __('No','woocommerce_json_api')),
            ),
            'type'          => 'select',
            'label'         => __( 'API Enabled?', 'woocommerce_json_api' ),
            'description'   => __('Quickly enable/disable The API', 'woocommerce_json_api' ),
          ),
          array(
            'name'          => $helpers->getPluginPrefix() . '_sitewide_settings[require_https]',
            'id'            => 'json_api_require_https_id',
            'value'         => get_option( $helpers->getPluginPrefix() . '_require_https' ),
            'options'       => array(
                array( 'value' => 'yes', 'content' => __('Yes','woocommerce_json_api')),
                array( 'value' => 'no', 'content' => __('No','woocommerce_json_api')),
            ),
            'type'          => 'select',
            'label'         => __( 'Require HTTPS', 'woocommerce_json_api' ),
            'description'   => __('Only serve HTTPS requests?', 'woocommerce_json_api' ),
          )
		  ),
	  ),
	);
  $attrs = apply_filters('woocommerce_json_api_sitewide_settings_fields', $attrs);
  
  echo $helpers->renderTemplate('admin-settings-page.php', array( 'attrs' => $attrs, 'json_api_slug' => $json_api_slug) );
}

