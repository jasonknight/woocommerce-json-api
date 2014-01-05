<?php
require_once "functions.php";
include "config.php";
$Header("Reading Orders");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_orders',
  'arguments'   => array(
    'token' => $token,
    // 'per_page' => 4,
    // 'page'     => 3
  )
);
$result = curl_post($url,$data);
echo "Result is: " . $result;
$orders = json_decode($result,true);

$has_notes = false;
$has_ois = false;

$note_count = 0;

foreach ( $orders['payload'] as $order) {
  if ( isset( $order['notes'] ) && is_array( $order['notes'] ) && count( $order['notes'] ) > 0) {
    $note_count++;
    $has_notes = true;
  }
  if ( isset( $order['order_items'] ) && is_array( $order['order_items'] ) && count( $order['order_items'] ) > 0) {
    $has_ois = true;
  }
}
//equal($has_notes,true,'Has Notes?');
//equal($has_ois,true,'Has OrderItems');

foreach ( $orders['payload'] as $order ) {
  notEqual($order['status'],'pending',"Order status `{$order['status']}` should not  == pending");
}

