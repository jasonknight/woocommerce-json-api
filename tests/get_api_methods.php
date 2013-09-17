<?php
require_once "functions.php";
include "config.php";
$Header("Reading Orders");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_api_methods',
  'arguments'   => array(
    'token' => $token,
  )
);
$result = curl_post($url,$data);

echo $result;
echo "\n";

