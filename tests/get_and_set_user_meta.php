<?php
require_once "functions.php";
include "config.php";
$Header("Writing User Meta");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'set_user_meta',
  'arguments'   => array(
    'token' => $token,
  ),
  'payload' => array(
      array('key' => 'just_a_test', 'value' => array('Hello World',2,3)),
  ),
);

$result = curl_post($url,$data);
$result = json_decode($result,true);
equal($result['status'],true,'Call succeeded?');

$Header("Getting User Meta");
$data2 = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_user_meta',
  'arguments'   => array(
    'token' => $token,
  ),
  'payload' => array(
      array('key' => 'just_a_test', 'value' => null)
  ),
);
$result = curl_post($url,$data2);
$result = json_decode($result,true);
equal($result['status'],true,'Call succeeded?');
equal($result['payload'][0]['value'][0],'Hello World',"Did we get the value back out?");