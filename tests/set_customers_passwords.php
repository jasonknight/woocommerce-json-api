<?php
require_once "functions.php";
include "config.php";
$Header("Setting Customers Passwords");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_customers',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 2,
    'page'     => 1
  )
);
$result = curl_post($url,$data);
verifySuccess("Get Customers",$result);
verifyNonZeroPayload("Get Customers", $result);
$result = json_decode($result,true);
$user_id = $result['payload'][0]['id'];

$new_pass = 'new_pass_' . rand(0,10);
$result['payload'] = array( 
	array( 'id' => 1, 'password' => $new_pass)
);
$result['proc'] = 'set_customers_passwords';
$result = curl_post($url,$result);
echo $result;
verifySuccess("did we set it?",$result);

$result = json_decode($result,true);

equal('[FILTERED]',$result['payload'][0]['password'],"New password is: $new_pass");

