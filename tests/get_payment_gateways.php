<?php
include "functions.php";
include "config.php";

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_payment_gateways',
  'arguments'   => array(
    'token' => $token,
  )
);
$result = curl_post($url,$data);
echo $result;
verifySuccess("Get Payment Gateways", $result);

