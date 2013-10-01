<?php
require_once "functions.php";
include "config.php";
$Header("Reading Coupons");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_coupons',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 5,
    'page'     => 1
  )
);
$result = curl_post($url,$data);
echo $result;
verifySuccess("Get Coupons",$result);
verifyNonZeroPayload("Get Coupons", $result);


