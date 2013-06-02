<?php
/**
  This class needs to be instantiated as helpers, and provides all the helper 
  functionality needed by the PHP side of the API
*/
class WC_JSON_API_Helpers {
  private $plugin_name = 'woocommerce-json-api';
  private $path;
  private $css;
  private $js;
  private $templates;
  
  private $wp_template;
  private $wp_theme_root;
  
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
    $this->path             = WOO_JSON_API_BASE_PATH; // Why do this? Maybe a plugin wants to override this later...
    $this->css              = WOO_JSON_API_BASE_PATH . 'templates/css/';
    $this->js               = WOO_JSON_API_BASE_PATH . 'assets/js/'; 
    $this->templates        = WOO_JSON_API_BASE_PATH . 'templates/';
    
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
  public function find_template($template_name) {
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
  // $vars_in_scope is an array like so: {'myvar' => 'some text'} which can
  // be accessed in the template withe $myvar
  public function render_template($template_name, $vars_in_scope = array()) {
    global $woocommerce,$wpdb, $user_ID, $available_methods;
    $vars_in_scope['helpers'] = $this;
    $vars_in_scope = apply_filters( $this->getPluginPrefix() . '_vars_in_scope_for_' . basename($template_name,".php"), $vars_in_scope );
    foreach ($vars_in_scope as $name=>$value) {
      $$name = $value;
    }
    $template_path = $this->find_template($template_name);
    ob_start();
    try {
      include $template_path;
      $content = ob_get_contents();
      ob_end_clean();
      $content = apply_filters( $this->getPluginPrefix() . '_template_rendered_' . basename($template_name,".php") ,$content );
    } catch ( Exception $err) {
      ob_end_clean();
      throw new Exception( __('Error while rendering template ' . $template_name . ' -- ' . $err->getMessage() ) );
    }
    return $content;
  }
  public function getPluginName() {
    return $this->plugin_name;
  }
  public function getPluginPrefix() {
    return str_replace('-','_',$this->plugin_name);
  }
}
?>
