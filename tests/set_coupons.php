<?php
require_once "functions.php";
include "config.php";
$Header("Reading Coupons");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_coupons',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 2,
    'page'     => 1
  )
);
$result = curl_post($url,$data);
echo $result;
verifySuccess("Get Coupons",$result);
verifyNonZeroPayload("Get Coupons", $result);

$result = json_decode($result,true);
$old_coupon = $result['payload'][0];
$new_coupon = $old_coupon;
$new_coupon['code'] = 'APICREATE' . rand(100,500);
unset($new_coupon['id']);
$result['payload'][] = $new_coupon;
$result['proc'] = 'set_coupons';
$result = curl_post($url,$result);
echo $result;
verifySuccess("Set Coupons",$result);
verifyNonZeroPayload("Set Coupons", $result);

