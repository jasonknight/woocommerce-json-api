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

$products = json_decode($result,true);

$product = $products['payload'][0];

$old_price = $product['price'];
$nprice = $old_price * 2;

$oname = $product['name'];
$nname = $oname . " I edited it...";

$product['name'] = $nname;
$product['price'] = $nprice;
$products['proc'] = 'set_products';
$products['payload'][0] = $product;


$result = curl_post($url,$products);

// Now do the load a second time:

$result = curl_post($url,$data);

$products = json_decode($result,true);

$product = $products['payload'][0];

notEqual($oname, $product['name']);
equal($nname, $product['name']);

notEqual($old_price, $product['price']);
equal($nprice, $product['price']);

$product['name'] = $oname;
$product['price'] = $old_price;
$products['proc'] = 'set_products';
$products['payload'][0] = $product;

$result = curl_post($url,$products);