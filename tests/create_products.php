<?php
include "functions.php";
include "config.php";

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'set_products',
  'arguments'   => array(
    'token' => $token,
  ),
  'payload' => array(
    array(
      'name' => 'Api created product ' . rand(0,100),
      'price' => 15.95,
      'regular_price' => 15.95,
      'sku' => 'A' . rand(100,1000),
      'description' => '<b>Hello World!</b>',
      'tax_status' => 'none'
    ),
  ),
);

$result = curl_post($url,$data);
echo "Result is: " . $result;
