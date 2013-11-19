<?php 
/*
* This file is meant to be accessed directly via a url...
* maybe it's not the best idea, but it'll have to do.
*/
// We need to see if we can get to the wp-blog-header file.
// normally, we will be in wp-content/plugins/woocommerce-json-api
define('WP_USE_THEMES', true);
$path = dirname( __FILE__ );
$target = $path . "/../../../wp-blog-header.php";
if ( file_exists($target) ) {
  require($target);
} else {
  die(0);
}


