<?php
require_once "functions.php";
include "config.php";
$Header("Setting Orders Status");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_orders',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 1,
    'page'     => 1
  )
);
$result = curl_post($url,$data);

$result = json_decode($result,true);

$order = $result['payload'][0];

echo "Order.status is: {$order['status']}\n";
$old_status = $order['status'];
$new_status = 'complete';

$order['status'] = $new_status;
echo "Now Order.status will be: {$order['status']}\n";

$result['proc'] = 'set_orders';
$result['payload'] = array($order);
$result = curl_post($url,$result);
echo $result;
$result = json_decode($result,true);

$order = $result['payload'][0];
print_r($result);
print_r($order);
echo "Order.status is: {$order['status']}\n";
equal($new_status,$order['status']);

$order['status'] = $old_status;

$result['proc'] = 'set_orders';
$result['payload'] = array($order);
$result = curl_post($url,$result);
$result = json_decode($result,true);
$order = $result['payload'][0];
equal($old_status,$order['status']);
