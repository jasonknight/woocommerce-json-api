<?php
require_once "functions.php";
include "config.php";

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_store_settings',
  'arguments'   => array(
    'token' => $token,
  )
);
$result = curl_post($url,$data);
verifySuccess("Get Store Settings",$result);
verifyNonZeroPayload("NonZero Result", $result);

