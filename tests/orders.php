<?php
require_once "functions.php";
include "config.php";
$Header("Writing Orders");
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
$orders = json_decode($result,true);


$has_notes = false;
$has_ois = false;

$note_count = 0;
foreach ( $orders['payload'] as $order) {
  if ( isset( $order['notes'] ) && is_array( $order['notes'] ) && count( $order['notes'] ) > 0) {
    $note_count += count( $order['notes'] );
    $has_notes = true;
  }
  if ( isset( $order['order_items'] ) && is_array( $order['order_items'] ) && count( $order['order_items'] ) > 0) {
    $has_ois = true;
  }
}
equal($has_notes,true,'Has Notes?');
equal($has_ois,true,'Has OrderItems');



$order = $orders['payload'][0];

$order['notes'][] = array (
    'name' => 'WooCommerceAPI',
    'date' => '2013-09-13 10:14:18',
    'email' => 'woocommerce@woo.localhost',
    'body' => 'This note was added from the test suite!',
    'approved' => 1,
    'object_id' => 32,
    'parent_id' => 0,
    'user_id' => 0,
    'type' => 'order_note',
);

$orders['payload'] = array( $order );
$orders['proc'] = 'set_orders';

$result = curl_post($url,$orders);
$result = curl_post($url,$data);

$orders = json_decode($result,true);

$note_count2 = 0;
foreach ( $orders['payload'] as $order) {
  if ( isset( $order['notes'] ) && is_array( $order['notes'] ) && count( $order['notes'] ) > 0) {
    $note_count2 += count( $order['notes'] );
    $has_notes = true;
  }
  if ( isset( $order['order_items'] ) && is_array( $order['order_items'] ) && count( $order['order_items'] ) > 0) {
    $has_ois = true;
  }
}

notEqual($note_count2, $note_count);

$Header("Creating a new Order");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_orders',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 2,
    'page'     => 1
  )
);
$result = curl_post($url,$data);
$result = json_decode($result,true);

$new_order = $result['payload'][0];
unset($new_order['id']);
unset($new_order['notes']);
$relations = array('order_items','tax_items','coupon_items');
foreach ( $relations as $relation ) {
  foreach ($new_order[$relation] as &$item) {
    unset($item['id']);
  }
}
$new_order['name'] = "From the API";
$new_order['status'] = 'processing';
$result['payload'] = array($new_order);
$result['proc'] = 'set_orders';
$new_result = curl_post($url,$result);
$new_result = json_decode($new_result,true);

equal($new_result['status'],true);
keyExists('id',$new_result['payload'][0]);

