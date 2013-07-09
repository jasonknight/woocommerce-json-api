<?php
function _rede_notset( $mixed ) {
  if (defined('REDENOTSET')) {
    if ($mixed == REDENOTSET) {
      return true;
    } else {
      return false;
    }
  } else {
    throw new Exception( __('REDENOTSET is not defined!','rede_plugins') );
  }
}
/**
  This class needs to be instantiated as helpers, and provides all the helper 
  functionality needed by the PHP side of the API
*/
class RedEHelpers {
  public $plugin_name = 'woocommerce-json-api';
  private $path;
  private $css;
  private $js;
  private $templates;
  
  private $wp_template;
  private $wp_theme_root;

  // Later on, these will be configurable and can be
  // turned off completely from the controls in the UI.
  public static function warn($text) {
    $file = REDE_PLUGIN_BASE_PATH . "warnings.log";
    $fp = fopen($file,'a');
    if (!$fp) {
      die("Could not open log file");
    }
    fwrite($fp,$text . "\n");
    self::debug("[Warn] " . $text);
    fclose($fp);
  }
  public static function error($text) {
    $fp = fopen(REDE_PLUGIN_BASE_PATH . "errors.log",'a');
    if (!$fp) {
      die("Could not open log file");
    }
    fwrite($fp,$text . "\n");
    self::debug("[Error] " . $text);
    fclose($fp);
  }
  public static function debug($text) {
    $fp = fopen(REDE_PLUGIN_BASE_PATH . "debug.log",'a');
    if (!$fp) {
      die("Could not open log file");
    }
    fwrite($fp,$text . "\n");
    fclose($fp);
  }
  public function __construct() {
    // README
    // I wrote this file so that I could explore
    // the WP API a bit more and get an idea
    // about where stuff is, and what functions
    // are available
    // All paths should consistently end with /
    // This is not the case with all WP functions
    // as is the case with theme_root
    
    // All we are doing here is populating some helper variables
    // for use later
    $this->path             = REDE_PLUGIN_BASE_PATH; // Why do this? Maybe a plugin wants to override this later...
    $this->css              = REDE_PLUGIN_BASE_PATH . 'templates/css/';
    $this->js               = REDE_PLUGIN_BASE_PATH . 'assets/js/'; 
    $this->templates        = REDE_PLUGIN_BASE_PATH . 'templates/';
    
    $this->wp_template      = get_template();
    // Just calling get_theme_root doesn't seem to work...
    $this->wp_theme_root    = get_theme_root( $this->wp_template );
    if ( false === strpos( $this->wp_theme_root, $this->wp_template) ) {
      $test_path = $this->wp_theme_root . '/' . $this->wp_template;
      if ( file_exists($test_path) ) {
        $this->wp_theme_root = $test_path . "/";
      }
    } else {
      $this->wp_theme_root .= '/';
    }
    
  }
  // README
  // This function finds where a template is located in the system
  // and returns an absolute path, or throws an error when it
  // is not present on the system
  public function findTemplate($template_name) {
    $test_path = $this->wp_theme_root . 'templates/' . $template_name;
    if ( file_exists( $test_path ) ) {
      return $test_path;
    } else {
      $test_path = $this->path. 'templates/' . $template_name;
      if ( file_exists($test_path) ) {
        return $test_path;
      } else {
        throw new Exception( __('Core Template was not found: ') . ' ' . $template_name );
      }
    }
  }
  public function findClassFile( $filename, $throw_error = false ) {
    $test_path = $this->wp_theme_root . 'classes/' . $filename;
    if ( file_exists( $test_path ) ) {
      return $test_path;
    } else {
      $test_path = $this->path . 'classes/' . $filename;
      if ( file_exists($test_path) ) {
        return $test_path;
      } else {
        if ( $throw_error ) {
          throw new Exception( __('Core Class File was not found: ') . ' ' . $filename );
        } else {
          return false;
        }
      }
    }
  }
  /** 
    $vars_in_scope is an array like so: {'myvar' => 'some text'} which can
    be accessed in the template withe $myvar
    
    @param string template path, relative to the plugin
    @param array of key value pairs to put into scope
    @return the rendered, filtered, executed content of the php template file
  */
  public function renderTemplate($template_name, $vars_in_scope = array()) {
    global $woocommerce,$wpdb, $user_ID, $available_methods;
    $vars_in_scope['helpers'] = $this;
    $vars_in_scope['__VIEW__'] = $template_name; //could be user-files.php or somedir/user-files.php
                                                 
    // The filter will look like: woo_commerce_json_api_vars_in_scope_for_user_files if the
    // views name was user-files.php, if it was in a subdir, like dir/user-files.php it would be dir_user_files
    $vars_in_scope = apply_filters( $this->getPluginPrefix() . '_vars_in_scope_for_' . basename( str_replace('/','_', $template_name),".php"), $vars_in_scope );
    foreach ($vars_in_scope as $name=>$value) {
      $$name = $value;
    }
    $template_path = $this->findTemplate($template_name);
    ob_start();
    try {
      include $template_path;
      $content = ob_get_contents();
      ob_end_clean();
      $content = apply_filters( $this->getPluginPrefix() . '_template_rendered_' . basename( str_replace('/','_', $template_name) ,".php") ,$content );
    } catch ( Exception $err) {
      ob_end_clean();
      throw new Exception( __('Error while rendering template ' . $template_name . ' -- ' . $err->getMessage(), 'rede_plugins' ) );
    }
    return $content;
  }
  /**
    Return the plugin name.
  */
  public function getPluginName() {
    return $this->plugin_name;
  }
  /*
    Get the PluginPrefix, used for meta data keys to help avoid namespace collisions
    with other plugins.
  */
  public function getPluginPrefix() {
    return str_replace('-','_',$this->plugin_name);
  }
  /*
    Does this plugin have a special text domain?
  */
  public function getPluginTextDomain() {
    return $this->getPluginName();
  }
  /***************************************************************************/
  /*                    Checkers, validators                                 */
  /***************************************************************************/
  
  /**
    We want to avoid directly accessing Array keys, because
    a) people have weird debug settings and 
    b) Some idiot thought it was a good idea to add in warnings when you access a null array key.
       Whoever that person is, they should be shot. Out of a cannon. Into the Sun.
       
    @param array to look in
    @param string key
    @param default value if not found (Default is i18n xlated to UnNamed
  */
  function orEq($array,$key,$default = null, $valid_values_list = null) {
    if ( $default === null ) {
      $default = __('UnNamed', $this->getPluginName() ) . ' - ' . $key;
    }
    if ( isset($array[$key]) ) {
      $value = $array[$key];
    } else {
      $value = $default;
    }
    if ($valid_values_list) {
      foreach ( $valid_values_list as $val) {
        if ($value == $val)
          return $value;
      }
      RedEHelpers::warn("orEq was passed a valid_values_list, but inputs did not match, so returning default");
      return $default;
    } else {
      return $value;
    }
    
  }
  /**
    PHP's array_search is clumsy and not helpful with simple searching where all we want
    is a true or false value. It's just easier to do it our own way.
  */
  public function inArray($needle, $haystack) {
    foreach ($haystack as $value) {
      if ($needle === $value) {
        return true;
      }
    }
    return false;
  }
  /**
    We pass in the params, usually $params['arguments'] by reference, as well as
    a reference to the result object so that we can invalidate and add errors to it.
  */
  public function validateParameters( &$params, &$target ) {
    $params = apply_filters('rede_pre_validate_parameters',$params, $target);
    foreach ( $params as $key=>&$value ) {
      $tmp_key = str_replace('_','-',$key);
      $fname = "class-{$tmp_key}-validator.php";
      $tmp_key =  str_replace('-',' ', $tmp_key);
      $tmp_key = ucwords($tmp_key);
      $tmp_key = str_replace(" ",'', $tmp_key);
      $class_name = "{$tmp_key}Validator";
      RedEHelpers::debug("class name to load is {$class_name}");
      $path = $this->findClassFile($fname, false);
      if ( $path ) {
        require_once $path;
        if ( class_exists($class_name) ) {
          $validator = new $class_name();
          $validator->validate( $this, $params, $target );
        }
      }
    }
    $params = apply_filters('rede_post_validate_parameters',$params, $target);
    //return array($params, $target);
  }
  /***************************************************************************/
  /*                         HTML API Helpers                                */
  /***************************************************************************/
  public function labelTag($args) {
    $name = $this->orEq($args,'name');
    $content = $this->orEq($args,'label');
    $classes = $this->orEq($args,'classes','');
    return "<label for='" . esc_attr( $name ) . "' for='" . esc_attr( $classes ) . "'>" . esc_html( $content ) . "</label>";
  }
  public function inputTag($args) {
    $name = $this->orEq($args,'name');
    $value = $this->orEq($args,'value','');
    $id = $this->orEq($args,'id','');
    return "<input type='text' id='" . esc_attr($id) . "' name='" . esc_attr( $name ) . "' value='" . esc_html( $value ) . "' />";
  }
  public function textAreaTag($args) {
    $name = $this->orEq($args,'name');
    $value = $this->orEq($args,'value','');
    $id = $this->orEq($args,'id','');
    $rows = $this->orEq($args,'rows',3);
    return "<textarea id='" . esc_attr($id) . "' name='" . esc_attr( $name ) . "' rows='" . esc_attr( $rows ) . "'>" . esc_html( $value ) . "</textarea>";
  }
  public function selectTag( $args ) {
    $name = $this->orEq($args,'name');
    $value = $this->orEq($args,'value','');
    $id = $this->orEq($args,'id','');
    $options = $this->orEq($args,'options', array() );
    $content = "<select name='$name' id='$id'>\n";
    foreach ( $options as $option ) {
      $opt = "<option value='%s' %s> %s </option>";
      $selected = '';
      if ( $option['value'] == $value ) {
        $selected = " selected='selected'";
      }
      $opt = sprintf($opt,$option['value'],$selected,$option['content']);
      $content .= $opt;
    }
    $content .= "</select>\n";
    return $content;
  } 
  public function hiddenFormFields( $action ) {
    $output = wp_nonce_field($action,'_wpnonce',true,false);
    return $output;
  }
  
  /***************************************************************************/
  /*                       WordPress API Helpers                             */
  /***************************************************************************/
  
  /**
    Convert a title into a slug
  */
  public function createSlug($text) {
    $text = sanitize_title($text);
    return $text;
  }
  /**
     We want to ease the creation of pages
     
     @param $title - The title you want to use, will be converted to the slug
     @param $content - the contents of the page
     @param $publish - boolean
     @return Array of populated values to send to insert_post
  */
  public function newPage($title,$content,$publish = true) {
    $page = array(
			'post_status' 		=> $publish === true ? 'publish' : 'pending',
			'post_type' 		=> 'page',
			'post_author' 		=> 1,
			'post_name' 		=> $this->createSlug($title),
			'post_title' 		=> $title,
			'post_content' 		=> $content,
			'post_parent' 		=> 0,
			'comment_status' 	=> 'closed'
		);
    return $page;
  }
  
 /*
  
 */
 public function getTitleBySlug( $slug, $default = '' ) {
   $page = get_page_by_path( $slug );
   $title = get_the_title($page->ID);
   if ( empty( $title ) ) {
    $title = $default;
   }
   return $title;
 }
 public function getPermalinkBySlug( $slug ) {
  $page = get_page_by_path( $slug );
  return get_permalink( $page );
 }
  
}
?>
