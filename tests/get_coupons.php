<?php
require_once "functions.php";
include "config.php";
$Header("Reading Customers");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_coupons',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 2,
    'page'     => 1
  )
);
$result = json_decode(curl_post($url,$data));
print_r($result);
die;
verifySuccess("Get Coupons",$result);
verifyNonZeroPayload("Get Coupons", $result);

