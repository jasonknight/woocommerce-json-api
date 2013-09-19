<?php
require_once "functions.php";
include "config.php";
$Header("Reading Supported Attributes");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_supported_attributes',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 2,
    'page'     => 1
  )
);
$result = curl_post($url,$data);
verifySuccess("Get Supported Attributes",$result);
verifyNonZeroPayload("Get Supported Attributes", $result);

