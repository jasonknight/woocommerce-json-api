<?php
add_action( 'show_user_profile',        'woocommerce_json_api_show_user_profile',   1 );
add_action( 'edit_user_profile',        'woocommerce_json_api_edit_user_profile',   1 );
add_action( 'personal_options_update',  'woocommerce_json_api_update_user_profile', 1 );
add_action( 'edit_user_profile_update', 'woocommerce_json_api_update_user_profile', 1 );
add_action( 'template_redirect',        'woocommerce_json_api_template_redirect',  -99 );

