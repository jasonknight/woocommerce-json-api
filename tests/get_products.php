<?php
require_once "functions.php";
include "config.php";
//$url = 'http://woo.localhost/cart/';
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_products',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 2,
    'page'     => 1,
    'include' => array(
      'variations' => false,
      'images' => false,
      'featured_image' => false,
      'categories' => false,
      'tags' => false,
      'reviews' => false,
    ),
  )
);
echo json_encode($data,JSON_PRETTY_PRINT);

$result = curl_post($url,$data);
echo "Result is: \n\n";
echo $result;
echo "\n\n";

