<?php
include "functions.php";
include "config.php";

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_products_by_tags',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 20,
    'page'     => 1,
    'tags' => array(
      'tes-tag'
    ),
  )
);
$result = curl_post($url,$data);
echo $result;
verifySuccess("Get Tags", $result);

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'gget_products_by_tags',
  'arguments'   => array(
    'token' => '1234',
    'per_page' => 20,
    'page'     => 1,
    'tags' => array(
      'tes-tag'
    ),
    'order_by' => 'non-exisent-column'
  )
);
$result = curl_post($url,$data);
verifyHasErrors("Sort by bad column", $result, -5);

