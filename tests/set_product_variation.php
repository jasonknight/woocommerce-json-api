<?php
require_once "functions.php";
include "config.php";
$Header("Writing Product Images");

$r = rand(1,9999999);
$sr = rand(1,5);
$p =$sr * 1.25;


// Try uploading an image

$new_product_data = array(
  'name' => "An API Created Product 203 Variation",
  'price' => $p,
  'sku' => "API$r",
  'visibility' => 'visible',
  'product_type' => 'simple',
  'type' => 'product_variation',
  'status' => 'instock',
  'product_type' => 'simple',
  'parent_id' => '203',
  'size_attribute' => 'small', // this is our dynamic attribute
  'images' => array(
    array('name' => 'fractal.png'),
    array('name' => 'fractal2.png'),
  ),
  'featured_image' => array(
    array('name' => 'fractal3.png'),
  )
);

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'set_products',
  'arguments'   => array(
    'token' => $token,
  ),
  'payload' => array(array('id' => 203,'product_type' => 'variable', 'variations' => array($new_product_data))),
   'images[0]' => "@" . dirname(__FILE__) ."/fractal.png",
   'images[1]' => "@" . dirname(__FILE__) ."/fractal2.png",
   'images[2]' => "@" . dirname(__FILE__) ."/fractal3.png",
   'model_filters' => array(
    /*
     * We need to edit a dynamic attribute, so we have
     * to let the model layer know it should load up 
     * a specific attribute.
     * 
     * In this case, we have an attribute called Size,
     * WooCom will save this in the db as: attribute_size
     */
    'WCAPI_product_meta_attributes_table' => array(
      'size_attribute' => array(
       'name' => 'attribute_size',          
       'type' => 'string', 
       'values' => array(
        'small',
        'medium',
        'large',
       ),
       'sizehint' => 2
     ),
    )

  ),
);

$result = curl_post($url,$data);
echo $result;
$result = json_decode($result,true); 
$product = $result['payload'][0];
keyExists('id',$product['images'][0],'Was the id of the image set?');
