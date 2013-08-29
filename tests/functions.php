<?php 

function curl_post($url, array $post = NULL, array $options = array()) { 
    $defaults = array( 
        CURLOPT_POST => 1, 
        CURLOPT_HEADER => 0, 
        CURLOPT_URL => $url, 
        CURLOPT_FRESH_CONNECT => 1, 
        CURLOPT_RETURNTRANSFER => 1, 
        CURLOPT_FORBID_REUSE => 1, 
        CURLOPT_TIMEOUT => 4, 
        CURLOPT_POSTFIELDS => http_build_query($post),
        CURLOPT_SSL_VERIFYPEER => FALSE,
        CURLOPT_SSL_VERIFYHOST,2
    ); 

    $ch = curl_init(); 
    curl_setopt_array($ch, ($options + $defaults)); 
    if( ! $result = curl_exec($ch)) 
    { 
        trigger_error(curl_error($ch)); 
    } 
    curl_close($ch); 
    return $result; 
} 
function verifyHasErrors($test,$result, $code) {
  echo "Verifying: $test\n";
  $r = json_decode($result,true);
  if ( $r['status'] == false && $r['errors'][0]['code'] == $code) {
    echo "PASSED\n";
  } else {
    echo "FAILED\n";
  }
}
function verifyHasWarnings($test,$result, $code) {
  echo "Verifying: $test\n";
  $r = json_decode($result,true);
  if ( $r['status'] == true && $r['warnings'][0]['code'] == $code) {
    echo "PASSED\n";
  } else {
    echo "FAILED\n";
  }
}
function verifySuccess($test,$result) {
  echo "Verifying: $test\n";
  $r = json_decode($result,true);
  if ( $r['status'] == true) {
    echo "PASSED\n";
  } else {
    echo "FAILED\n";
    echo $result . "\n\n";
  }
}