<?php
include "functions.php";
include "config.php";

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_products',
  'arguments'   => array(
    'token' => $token,
    'ids' => array(64),
  )
);
echo json_encode($data,JSON_PRETTY_PRINT);

$result = curl_post($url,$data);
echo "Result is: \n\n";
echo $result;
echo "\n\n";

echo "Getting next: ";
$data['arguments']['ids'] = "1,2,3";
$result = curl_post($url,$data);
$result = json_decode($result,true);
print_r($result['errors']);
echo "\n\n";

echo "Getting next: ";
$data['arguments']['ids'] = array('1',2,'Bill');
$result = curl_post($url,$data);
$result = json_decode($result,true);
print_r($result['errors']);
echo "\n\n";

