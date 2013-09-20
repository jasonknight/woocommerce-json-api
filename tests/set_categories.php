<?php
include "functions.php";
include "config.php";
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'set_categories',
  'arguments'   => array(
    'token' => $token,
  ),
  'payload' => array(
    array(
      'id' => '13',
      'name' => 'My New Appliances',
    ),
  ),
);

$result = curl_post($url,$data);
echo "Result is: " . $result;
