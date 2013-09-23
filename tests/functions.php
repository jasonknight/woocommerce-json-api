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

function _fail() {
  $cmd = new CmdColors();
  $cmd->_("FAILED","red");
  echo "\n";
  exit;
}
function _pass() {
  $cmd = new CmdColors();
  $cmd->_("PASSED","green");
  echo "\n";
}
function _notice($str) {
  $cmd = new CmdColors();
  $cmd->_(str_pad(substr($str,0,55),60),"yellow");
}
$Fail = function() {
  _fail();
};
$Pass = function() {
  _pass();
};
$Notice = function($str) {
  _notice($str);
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
function notEqual($a,$b, $label = "should not equal") {
  global $Fail,$Pass,$Notice;
  if ( $label != '' ) {
    $Notice($label);
  } else {
    $ta = testToS($a);
    $tb = testToS($b);
    $Notice($label);
  }
  if ( $a === $b ) {
    call_user_func($Fail,"FAILED");
  } else {
    call_user_func($Pass,"PASSED");
  }
}
function testToS($x) {
  $tx = $x;
  if ( is_object($x) )
      $tx = get_class($x);
  if ( is_array($x) )
      $tx = "Array[".count($x)."]";
  return $tx;
}
function equal($a,$b, $label = "should equal") {
  global $Fail,$Pass,$Notice;
  $ta = testToS($a);
  $tb = testToS($b);
  $Notice($label);
  if ( $a !== $b ) {
    call_user_func($Fail,"FAILED");
  } else {
    call_user_func($Pass,"PASSED");
  }
}
function keyExists($a,$b, $label = "array key exists") {
  global $Fail,$Pass,$Notice;
  $Notice($label);
  //echo " checking for $a in Array";
  if ( !array_key_exists($a, $b) ) {
    call_user_func($Fail,"FAILED");
  } else {
    call_user_func($Pass,"PASSED");
  }
}

function shouldThrowError( $callable, $label = "should throw and error" ) {
  global $Fail,$Pass,$Notice;
  $Notice($label);
  try {
    call_user_func($callable);
  } catch (Exception $e) {
    call_user_func($Pass,"PASSED");
  }
  call_user_func($Fail,"FAILED");
}
function shouldBeTypeOf($obj,$type, $label = "should be type of") {
  global $Fail,$Pass,$Notice;
  $Notice($label);
  if ( gettype($obj) == $type ) {
    call_user_func($Pass,"PASSED");
  } else {
    call_user_func($Fail,"FAILED");
  }
}
function shouldBeClassOf($obj,$type, $label = "should be class of") {
  global $Fail,$Pass,$Notice;
  $Notice($label);
  if ( get_class($obj) == $type ) {
    call_user_func($Pass,"PASSED");
  } else {
    call_user_func($Fail,"FAILED");
  }
}
function hasAtLeast($a,$l,$label = "has at least") {
  global $Fail,$Pass,$Notice;
  $Notice($label);
  if ( is_array($a) && count($a) >= $l ) {
    call_user_func($Pass,"PASSED");
  } else {
    call_user_func($Fail,"FAILED");
  }
}
function hasAtMost($a,$l,$label = "has at most") {
  global $Fail,$Pass,$Notice;
  $Notice($label);
  if ( is_array($a) && count($a) <= $l ) {
    call_user_func($Pass,"PASSED");
  } else {
    call_user_func($Fail,"FAILED");
  }
}

class Mock {
  public $calls;
  public $set_attrs;
  public $got_attrs;
  public $when_attrs;
  public $when_calls;
  public $allow_attr_write;
  public $name;
  public function __construct( $name ) {
    $this->calls = array();
    $this->set_attrs = array();
    $this->got_attrs = array();
    $this->when_attrs = array();
    $this->when_calls = array();
    $this->allow_attr_write = false;
    $this->name = $name;
  }
  public function allowAttrWrite($t) {
    $this->allow_attr_write = $t;
    return $this;
  }
  public function whenAttr($name,$value) {
    $this->when_attrs[$name] = $value;
    return $this;
  }
  public function whenCalled($name,$value) {
    $this->when_calls[$name] = $value;
    return $this;
  }
  public function __call($name,$args) {
    $this->calls[] = $name;
    if ( isset( $this->when_calls[$name]) ) {
      return $this->when_calls[$name];
    }
    return null;
  }
  public function __set($name,$value) {
    $this->set_attrs[] = $name;
    if ( isset( $this->when_attrs[$name]) ) {
      $this->when_attrs[$name] = $value;
    }
  }
  public function __get($name) {
    $this->got_attrs[] = $name;
    if ( isset( $this->when_attrs[$name]) ) {
      return $this->when_attrs[$name];
    }
    return null;
  }

  public function hasReceived($name) {
    $all = array_merge($this->calls, $this->set_attrs, $this->got_attrs);
    $b = in_array($name, $all);
    if ( $b ) {
      _pass();
    } else {
      _fail();
    }
    return $this;
  }
  public function wasSet($name) {
    $b = in_array($name, $this->set_attrs);
    if ( $b ) {
      _pass();
    } else {
      _fail();
    }
    return $this;
  }
  public function wasGotten() {
    $b = in_array($name, $this->got_attrs);
    if ( $b ) {
      _pass();
    } else {
      _fail();
    } 
    return $this;
  }
}