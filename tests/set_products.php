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
    'page'     => 1
  )
);


$result = curl_post($url,$data);
$products = json_decode($result,true);

$product = $products['payload'][0];

$old_price = $product['price'];
$nprice = $old_price * 2;

$oname = $product['name'];
$nname = $oname . " I edited it";

$product['name'] = $nname;
$product['price'] = $nprice;
$products['proc'] = 'set_products';
$products['payload'][0] = $product;


$result = curl_post($url,$products);
// Now do the load a second time:

$result = curl_post($url,$data);

$products = json_decode($result,true);

$product = $products['payload'][0];

notEqual($oname, $product['name'], 'old name does not equal new name');
equal($nname, $product['name'],'new name is equal to product name');

notEqual(round($old_price,2), round($product['price'],2),'old price should not equal product price');
equal(round($nprice,2), round($product['price'],2),'new price should equal product price');

$tcount = count($product['tags']);
$ccount = count($product['categories']);

$product['name'] = $oname;
$product['price'] = $old_price;

$r = rand(0,99999);
$product['tags'][] = array(
  "name" =>  "This-is-an-api-tag " . $r,
  "slug" =>  "this-is-a-tag-".$r,
  "group_id" =>  "0",
  "description" =>  "I was created by the API",
  "parent_id" =>  "0",
  "count" =>  "1",
  "taxonomy" =>  "product_tag", 
);
$product['categories'][] = array(
  "name" =>  "This-is-an-api-category " . $r,
  "slug" =>  "this-is-a-category-".$r,
  "group_id" =>  "0",
  "description" =>  "I was created by the API",
  "parent_id" =>  "0",
  "count" =>  "1",
  "taxonomy" =>  "product_cat", 
);
  


$products['proc'] = 'set_products';
$products['payload'][0] = $product;


$result = curl_post($url,$products);

$products = json_decode($result,true);
$product = $products['payload'][0];
notEqual($tcount,count($product['tags']),"New product tags?");
notEqual($ccount, count($product['categories']),"New categories?");
$result = curl_post($url,$data);
