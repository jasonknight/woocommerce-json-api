<?php
require_once "functions.php";
include "config.php";
//$url = 'http://woo.localhost/cart/';
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_images',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 5,
    'page'     => 1
  )
);
echo json_encode($data,JSON_PRETTY_PRINT);

$result = curl_post($url,$data);
echo "Result is: \n\n";
echo $result;
echo "\n\n";

