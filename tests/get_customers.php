<?php
require_once "functions.php";
$url = 'http://woo.localhost/c6db13944977ac5f7a8305bbfb06fd6a/';
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_customers',
  'arguments'   => array(
    'token' => '1234',
    'per_page' => 2,
    'page'     => 1
  )
);
echo json_encode($data,JSON_PRETTY_PRINT);

$result = curl_post($url,$data);
echo "Result is: \n\n";
echo $result;
echo "\n\n";
//print_r( json_decode($result,true) );
echo "\n\n";
switch (json_last_error()) {
  case JSON_ERROR_NONE:
      echo ' - No errors';
  break;
  case JSON_ERROR_DEPTH:
      echo ' - Maximum stack depth exceeded';
  break;
  case JSON_ERROR_STATE_MISMATCH:
      echo ' - Underflow or the modes mismatch';
  break;
  case JSON_ERROR_CTRL_CHAR:
      echo ' - Unexpected control character found';
  break;
  case JSON_ERROR_SYNTAX:
      echo ' - Syntax error, malformed JSON';
  break;
  case JSON_ERROR_UTF8:
      echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
  break;
  default:
      echo ' - Unknown error';
  break;
}
echo "\n\n";
