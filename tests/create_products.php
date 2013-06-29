<?php
function curl_post($url, array $post = NULL, array $options = array()) 
{ 
    $defaults = array( 
        CURLOPT_POST => 1, 
        CURLOPT_HEADER => 0, 
        CURLOPT_URL => $url, 
        CURLOPT_FRESH_CONNECT => 1, 
        CURLOPT_RETURNTRANSFER => 1, 
        CURLOPT_FORBID_REUSE => 1, 
        CURLOPT_TIMEOUT => 4, 
        CURLOPT_POSTFIELDS => http_build_query($post) 
    ); 

    $ch = curl_init(); 
    curl_setopt_array($ch, ($options + $defaults)); 
    if( ! $result = curl_exec($ch)) 
    { 
        trigger_error(curl_error($ch)); 
    } 
    curl_close($ch); 
    return $result; 
} 
$url = 'http://woo.localhost/c6db13944977ac5f7a8305bbfb06fd6a/';
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'set_products',
  'arguments'   => array(
    'token' => '1234',
  ),
  'payload' => array(
    array(
      'name' => 'Api created product ' . rand(0,100),
      'price' => 15.95,
      'regular_price' => 15.95,
      'sku' => 'A' . rand(100,1000),
      'description' => '<b>Hello World!</b>',
      'tax_status' => 'none'
    ),
  ),
);

$result = curl_post($url,$data);
echo "Result is: " . $result;
