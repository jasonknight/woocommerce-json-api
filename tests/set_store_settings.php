<?php
require_once "functions.php";
include "config.php";

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_store_settings',
  'arguments'   => array(
    'token' => $token,
  )
);
$result = curl_post($url,$data);
verifySuccess("Get Store Settings",$result);

$result = json_decode($result, true);
$old_value = $result['payload']['force_ssl_checkout'];
$new_value = $old_value == 'no' ? 'yes' : 'no';
notEqual($old_value,$new_value);

$result['payload']['force_ssl_checkout'] = $new_value;
$result['proc'] = 'set_store_settings';
$result = curl_post($url,$result);
verifySuccess("Set Store Settings",$result);
$result = json_decode($result, true);

equal($result['payload']['force_ssl_checkout'],$new_value);

$result['payload']['force_ssl_checkout'] = $old_value;
$result = curl_post($url,$result);
verifySuccess("Set Store Settings 2",$result);
$result = json_decode($result, true);

equal($result['payload']['force_ssl_checkout'],$old_value);


// Test filtering

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_store_settings',
  'arguments'   => array(
    'token' => $token,
    'filter' => 'force'
  )
);
$result = curl_post($url,$data);
verifySuccess("Get Store Filtered Settings",$result);
$result = json_decode($result, true);
keyExists('force_ssl_checkout',$result['payload']);
