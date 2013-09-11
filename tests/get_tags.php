<?php
include "functions.php";
include "config.php";

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_tags',
  'arguments'   => array(
    'token' => $token,
  )
);
$result = curl_post($url,$data);
echo $result;
verifySuccess("Get Tags", $result);

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_tags',
  'arguments'   => array(
    'token' => $token,
    'order_by' => 'non-exisent-column'
  )
);
$result = curl_post($url,$data);
verifyHasErrors("Sort by bad column", $result, -5);

