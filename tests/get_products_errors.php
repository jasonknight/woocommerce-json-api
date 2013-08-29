<?php
include "functions.php";
include "config.php";

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_products',
  'arguments'   => array(
    'token' => 'TOTALLYWRONGTOKEN',
  )
);
$result = curl_post($url,$data);
verifyHasErrors("Wrong Token Sent",$result, -4);

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_products',
  'arguments'   => array(
    'token' => $token,
    'order_by' => 'junk column',
  )
);
$result = curl_post($url,$data);
verifyHasErrors("Wrong arguments",$result, -5);

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_products',
  'arguments'   => array(
    'token' => $token,
    'skus' => array('doesntexist')
  )
);
$result = curl_post($url,$data);
verifyHasWarnings("Returns warning when product doesn't exist by sku",$result, 1);

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_products',
  'arguments'   => array(
    'token' => $token,
    'ids' => array('9999')
  )
);
$result = curl_post($url,$data);
verifyHasWarnings("Returns warning when product doesn't exist by id",$result, 1);
