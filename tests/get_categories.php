<?php
include "functions.php";
include "config.php";

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_categories',
  'arguments'   => array(
    'token' => $token,
  )
);
$result = curl_post($url,$data);
verifySuccess("Get Categories", $result);

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_categories',
  'arguments'   => array(
    'token' => $token,
    'order_by' => 'non-exisent-column'
  )
);
$result = curl_post($url,$data);
verifyHasErrors("Sort by bad column", $result, -5);

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_categories',
  'arguments'   => array(
    'token' => $token,
    'ids' => array(33,54),
  )
);
$result = curl_post($url,$data);
echo $result;
verifySuccess("Get Categories by ids", $result);
