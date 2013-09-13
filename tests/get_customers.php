<?php
require_once "functions.php";
include "config.php";
$Header("Reading Customers");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_customers',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 2,
    'page'     => 1
  )
);
$result = curl_post($url,$data);
verifySuccess("Get Customers",$result);
//verifyNonZeroPayload("Get Customers", $result);

