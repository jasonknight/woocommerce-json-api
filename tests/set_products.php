<?php
include "functions.php";
include "config.php";
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_products',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 2,
    'page'     => 1
  )
);
echo json_encode($data,JSON_PRETTY_PRINT);

$result = curl_post($url,$data);
echo "Result is: \n\n";
echo $result;
echo "\n\n";
$result = json_decode($result,true);
echo "\n\n";


$result['payload'][0]['name'] = 'Api created product 55' . " Edited ";
$result['payload'][0]['description'] = "A test description";
$result['payload'][0]['price'] = "99.57";
$result['proc'] = 'set_products';
$result = curl_post($url,$result);
echo "Result is: " . $result;
