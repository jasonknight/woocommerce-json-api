<?php
require_once "functions.php";
include "config.php";
$Header("Setting Orders Status");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_orders',
  'arguments'   => array(
    'username' => $username,
    'password' => $password,
    'per_page' => 1,
    'page'     => 1
  )
);
$result = curl_post($url,$data);
echo "Get Orders Result is: " . $result . "\n\n";
$result = json_decode($result,true);

$order = $result['payload'][0];

$old_status = $order['status'];
if ( $old_status == "completed" ) {
	$new_status = 'processing';
} else {
	$new_status = 'completed';
}

echo "Current Order Status is: {$order['status']}\n";
$order['status'] = $new_status;

$result['proc'] = 'set_orders';
$result['payload'] = array($order);

$result = curl_post($url,$result);
echo "Result is" . $result;
$result = json_decode($result,true);
print_r($result['payload']);
$order = $result['payload'][0];
echo "After update it is: {$order['status']}\n";
equal($new_status,$order['status'], "Order status should eql $new_status on return");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_orders',
  'arguments'   => array(
    'username' => $username,
    'password' => $password,
    'per_page' => 1,
    'page'     => 1
  )
);
$result = curl_post($url,$data);
$result = json_decode($result,true);

$order = $result['payload'][0];
equal($new_status,$order['status'], "Order status should eql $new_status on get_orders");

$order['status'] = $old_status;

$result['proc'] = 'set_orders';
$result['payload'] = array($order);
$result = curl_post($url,$result);

$result = json_decode($result,true);
$order = $result['payload'][0];
equal($old_status,$order['status'], "Order status should equal $old_status on return");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_orders',
  'arguments'   => array(
    'username' => $username,
    'password' => $password,
    'per_page' => 1,
    'page'     => 1
  )
);
$result = curl_post($url,$data);
$result = json_decode($result,true);

$order = $result['payload'][0];
equal($old_status,$order['status'], "Order status should eql $old_status on get_orders");
