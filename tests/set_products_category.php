<?php
require_once "functions.php";
include "config.php";
$Header("Set Product Categories");
////////////////////////////////////////////////////////////
/*
 * CREATING TOTALLY NEW CATEGORIES/TAGS(Same thing!!!!)
 */
///////////////////////////////////////////////////////////
// $data = array(
//   'action'      => 'woocommerce_json_api',
//   'proc'        => 'get_products',
//   'arguments'   => array(
//     'token' => $token,
//     'per_page' => 2,
//     'page'     => 1
//   )
// );


// $result = curl_post($url,$data);
// $products = json_decode($result,true);

// $product = $products['payload'][0];

// $tcount = count($product['tags']);
// $ccount = count($product['categories']);


// $r = rand(0,99999);
// // Here we are creating new tags and categories
// $product['tags'][] = array(
//   "name" =>  "This-is-an-api-tag " . $r,
//   "slug" =>  "this-is-a-tag-".$r,
//   "group_id" =>  "0",
//   "description" =>  "I was created by the API",
//   "parent_id" =>  "0",
//   "count" =>  "1",
//   "taxonomy" =>  "product_tag", 
// );
// $product['categories'][] = array(
//   "name" =>  "This-is-an-api-category " . $r,
//   "slug" =>  "this-is-a-category-".$r,
//   "group_id" =>  "0",
//   "description" =>  "I was created by the API",
//   "parent_id" =>  "0",
//   "count" =>  "1",
//   "taxonomy" =>  "product_cat", 
// );
  


// $products['proc'] = 'set_products';
// $products['payload'][0] = $product;


// $result = curl_post($url,$products);
// $products = json_decode($result,true);
// $product = $products['payload'][0];
// notEqual($tcount,count($product['tags']),"New product tags?");
// notEqual($ccount, count($product['categories']),"New categories?");

///////////////////////////////////////////////////////////////////
/*
 * ADDING EXISTING CATEGORIES/TAGS(Same thing!!!!)
 */
///////////////////////////////////////////////////////////////////

// STEP 1: Get available categories.

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_categories',
  'arguments'   => array(
    'token' => $token,
  )
);

$json = curl_post($url,$data);
$result = json_decode($json,true);
$categories = $result['payload'];

// STEP 2: Get a product

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_products',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 2,
    'page' => 1,
    'order_by' => 'ID',
    'order' => 'desc',
    'include' => array(
      'variations' => false,
      'images' => false,
      'featured_image' => false,
      'reviews' => false,
    ),
  )
);

$json = curl_post($url,$data);
$result = json_decode($json,true);
$products = $result['payload'];


// STEP 3: Assign a category to the product you want
$category_count = count($products[0]['categories']);
$products[0]['categories'][] = $categories[0];

// STEP 4: Send it back to the server:

$result['payload'] = array($products[0]);
$result['proc'] = 'set_products';

$json = curl_post($url,$result);
$result = json_decode($json,true);
$products = $result['payload'];
hasAtLeast($products[0]['categories'],$category_count + 1,"Was the category connected?");

///////////////////////////////////////////////////////
//     Disconnecting a Category from a Product       //
//////////////////////////////////////////////////////

$products[0]['categories'][0]['__disconnect__'] = true;
$result['payload'] = array($products[0]);
$result['proc'] = 'set_products';

$json = curl_post($url,$result);
$result = json_decode($json,true);
$products = $result['payload'];
equal(true,$products[0]['categories'][0]['__disconnected__'],'Was the category disconnected?');