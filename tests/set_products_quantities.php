<?php
require_once "functions.php";
include "config.php";
$Header("Writing Products");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_products',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 2,
    'page'     => 1,
    'include' => array(
      'variations' => false,
      'images' => false,
      'featured_image' => false,
      'categories' => false,
      'tags' => false,
      'reviews' => false,
      'variations_in_products' => false,
    ),
  )
);


$result = curl_post($url,$data);
$products = json_decode($result,true);

$product = $products['payload'][0];

$chosen_sku = $product['sku'];

$old_quantity = $product['quantity'];
$nqty = $old_quantity + 5;

equal(1,1,"old_quantity: $old_quantity and nqty: $nqty");


$product['quantity'] = $nqty;
$products['proc'] = 'set_products_quantities';
$products['payload'] = array($product);


$result = curl_post($url,$products);

// Now do the load a second time:
equal(1,1,"Now reloading");
$data['arguments']['skus'] = array($chosen_sku);

$result = curl_post($url,$data);

$products = json_decode($result,true);

$product = $products['payload'][0];

notEqual(round($old_quantity,2), round($product['quantity'],2),"{$old_quantity} should not equal {$product['quantity']}");
equal(round($nqty,2), round($product['quantity'],2),'new quantity should equal product quantity');

$product['quantity'] = $old_quantity;


$products['proc'] = 'set_products_quantities';
$products['payload'][0] = $product;


$result = curl_post($url,$products);
$products = json_decode($result,true);
$product = $products['payload'][0];

equal(round($old_quantity,2), round($product['quantity'],2),'old quantity should equal product quantity');


