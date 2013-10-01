<?php
add_action( 'show_user_profile',            'woocommerce_json_api_show_user_profile',   1 );
add_action( 'edit_user_profile',            'woocommerce_json_api_edit_user_profile',   1 );
add_action( 'personal_options_update',      'woocommerce_json_api_update_user_profile', 1 );
add_action( 'edit_user_profile_update',     'woocommerce_json_api_update_user_profile', 1 );
//add_action( 'template_redirect',            'woocommerce_json_api_template_redirect',  100 );
// this action never actually seems to fire...
add_action( 'woocommerce_loaded',           'woocommerce_json_api_template_redirect',   100 );
// add_action('admin_menu',                    'woocommerce_json_api_admin_menu',          10);
// these will only fire if you use the admin-ajax.php url. which is a bitch to use.
add_action('wp_ajax_woocommerce_json_api',  'woocommerce_json_api_template_redirect');
add_action('wp_ajax_nopriv_woocommerce_json_api',  'woocommerce_json_api_template_redirect');
// These don't seem to fire at all
add_action( 'muplugins_loaded', 'woocommerce_json_api_template_redirect' );
add_action( 'plugins_loaded', 'woocommerce_json_api_template_redirect' );

//This one does seem to work.
add_action( 'wp_loaded', 'woocommerce_json_api_template_redirect' );