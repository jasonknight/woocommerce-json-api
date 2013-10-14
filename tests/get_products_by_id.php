<?php
include "functions.php";
include "config.php";

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_products',
  'arguments'   => array(
    'username' => 'admin',
    'password' => 'nimda',
    'ids' => array(462),
  ),
  'model_filters' => array(

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
echo json_encode($data,JSON_PRETTY_PRINT);

$result = curl_post($url,$data);
echo "Result is: \n\n";
echo $result;
$result = json_decode($result,true);
$product = $result['payload'][0];
print_r($product['images']);
die;

echo "Getting next: ";
$data['arguments']['ids'] = "1,2,3";
$result = curl_post($url,$data);
$result = json_decode($result,true);
print_r($result['errors']);
echo "\n\n";

echo "Getting next: ";
$data['arguments']['ids'] = array('1',2,'Bill');
$result = curl_post($url,$data);
$result = json_decode($result,true);
print_r($result['errors']);
echo "\n\n";

