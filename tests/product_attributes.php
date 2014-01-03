<?php
include "functions.php";
include "config.php";

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_products',
  'arguments'   => array(
    'username' => 'admin',
    'password' => 'nimda',
    'ids' => array(691),
    'include' => array(
      'variations' => false,
      'images' => false,
      'featured_image' => false,
    ),
  ),
);
echo json_encode($data,JSON_PRETTY_PRINT);

$result = curl_post($url,$data);
echo "Result is: \n\n";
echo $result;
