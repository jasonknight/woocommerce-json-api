<?php 
class CmdColors {
  private $foreground_colors = array();
  private $background_colors = array();
  public function __construct() {
    // Set up shell colors
    $this->foreground_colors['black'] = '0;30';
    $this->foreground_colors['dark_gray'] = '1;30';
    $this->foreground_colors['blue'] = '0;34';
    $this->foreground_colors['light_blue'] = '1;34';
    $this->foreground_colors['green'] = '0;32';
    $this->foreground_colors['light_green'] = '1;32';
    $this->foreground_colors['cyan'] = '0;36';
    $this->foreground_colors['light_cyan'] = '1;36';
    $this->foreground_colors['red'] = '0;31';
    $this->foreground_colors['light_red'] = '1;31';
    $this->foreground_colors['purple'] = '0;35';
    $this->foreground_colors['light_purple'] = '1;35';
    $this->foreground_colors['brown'] = '0;33';
    $this->foreground_colors['yellow'] = '1;33';
    $this->foreground_colors['light_gray'] = '0;37';
    $this->foreground_colors['white'] = '1;37';

    $this->background_colors['black'] = '40';
    $this->background_colors['red'] = '41';
    $this->background_colors['green'] = '42';
    $this->background_colors['yellow'] = '43';
    $this->background_colors['blue'] = '44';
    $this->background_colors['magenta'] = '45';
    $this->background_colors['cyan'] = '46';
    $this->background_colors['light_gray'] = '47';
  }

  // Returns colored string
  public function getColoredString($string, $foreground_color = null, $background_color = null) {
    $colored_string = "";

    // Check if given foreground color found
    if (isset($this->foreground_colors[$foreground_color])) {
      $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
    }
    // Check if given background color found
    if (isset($this->background_colors[$background_color])) {
      $colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
    }

    // Add string and end coloring
    $colored_string .=  $string . "\033[0m";

    return $colored_string;
  }

  // Returns all foreground color names
  public function getForegroundColors() {
    return array_keys($this->foreground_colors);
  }

  // Returns all background color names
  public function getBackgroundColors() {
    return array_keys($this->background_colors);
  }

  public function _( $str, $color, $bg = null) {
    static $last = 'black';
    if ( $bg ) {
      echo($this->getColoredString($str, $color, $bg));
      return;
    } else {
      echo($this->getColoredString($str, $color, $last));
    }
    
    if ( $last == 'none' && ($str == "FAILED" || $str == "PASSED") ) {
      $last = 'black';
    } else if ($last == 'black' && ($str == "FAILED" || $str == "PASSED") ) {
      $last = 'none';
    }

  }
}

$Fail = function() {
  $cmd = new CmdColors();
  $cmd->_("FAILED","red");
  echo "\n";
  exit;
};
$Pass = function() {
  $cmd = new CmdColors();
  $cmd->_("PASSED","green");
  echo "\n";
};
$Notice = function($str) {
  $cmd = new CmdColors();
  $cmd->_(str_pad(substr($str,0,55),60),"yellow");
};
$Header = function ($str) {
  $cmd = new CmdColors();
  $cmd->_(str_pad(substr("*----------------------------------------------------*",0,60),60, ' ',STR_PAD_BOTH),"blue", "none");
  echo "\n";
  $cmd->_(str_pad(substr($str,0,60),60,' ', STR_PAD_BOTH),"blue", "none");
  echo "\n";
  $cmd->_(str_pad(substr("*----------------------------------------------------*",0,60),60,' ', STR_PAD_BOTH),"blue","none");
  echo "\n";
};
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
  global $Fail,$Pass,$Notice;
  $Notice("$test");
  $r = json_decode($result,true);
  if ( $r['status'] == false && $r['errors'][0]['code'] == $code) {
    call_user_func($Pass,"PASSED");
  } else {
    call_user_func($Fail,"FAILED");
  }
}
function verifyHasWarnings($test,$result, $code) {
  global $Fail,$Pass,$Notice;
  $Notice("$test ");
  $r = json_decode($result,true);
  if ( $r['status'] == true && $r['warnings'][0]['code'] == $code) {
    call_user_func($Pass,"PASSED");
  } else {
    call_user_func($Fail,"FAILED");
  }
}
function verifySuccess($test,$result) {
  global $Fail,$Pass,$Notice;
  $Notice("$test ");
  $r = json_decode($result,true);
  if ( $r['status'] == true) {
    call_user_func($Pass,"PASSED");
  } else {
    call_user_func($Fail,"FAILED");
    echo $result . "\n\n";
  }
}
function verifyNonZeroPayload($test,$result) {
  global $Fail,$Pass,$Notice;
  $Notice("$test ");
  $r = json_decode($result,true);
  if ( count($r['payload']) > 0) {
    call_user_func($Pass,"PASSED");
  } else {
    call_user_func($Fail,"FAILED");
    echo $result . "\n\n";
  }
}
function notEqual($a,$b, $label = '') {
  global $Fail,$Pass,$Notice;
  $Notice("$label $a should not equal $b");
  if ( $a == $b ) {
    call_user_func($Fail,"FAILED");
  } else {
    call_user_func($Pass,"PASSED");
  }
}
function equal($a,$b, $label = '') {
  global $Fail,$Pass,$Notice;
  $Notice("$label $a should equal $b");
  if ( $a != $b ) {
    call_user_func($Fail,"FAILED");
  } else {
    call_user_func($Pass,"PASSED");
  }
}
function keyExists($a,$b) {
  global $Fail,$Pass,$Notice;
  $Notice("Array Key Exists $a");
  if ( !array_key_exists($a, $b) ) {
    call_user_func($Fail,"FAILED");
  } else {
    call_user_func($Pass,"PASSED");
  }
}