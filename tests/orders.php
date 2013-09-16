<?php
require_once "functions.php";
include "config.php";
$Header("Writing Orders");
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
