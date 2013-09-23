<?php
namespace WCAPI;
/**
 * A Customer class to insulate the API from the details of the
 * database representation
*/
require_once(dirname(__FILE__) . "/Base.php");
require_once(dirname(__FILE__) . "/Category.php");
class Image extends Base {
  public $after_create = array('process');
  public static function getModelSettings() {
    include WCAPIDIR."/_globals.php";
    $table = array_merge( Base::getDefaultModelSettings(), array(
        'model_table'                => $wpdb->posts,
        'meta_table'                => $wpdb->postmeta,
        'model_table_id'             => 'id',
        'meta_table_foreign_key'    => 'post_id',
        'model_conditions' => "WHERE post_type IN ('attachment') AND post_status NOT IN ('trash','auto-draft')",
      ) 
    );
    $table = apply_filters('WCAPI_image_model_settings',$table);
    return $table;
  }
  public static function getModelAttributes() {
      $table = array(
      'filename' => array('name' => 'post_title', 'type' => 'string', 'sizehint' => 10, 'group_name' => 'main' ),
      'slug' => array('name' => 'post_name',  'type' => 'string', 'sizehint' => 10),
      'mime_type' => array('name' => 'post_mime_type',  'type' => 'string', 'sizehint' => 10),
      'type' => array('name' => 'post_type',
           'type' => 'string',
           'values' => array(
              'attachment',
            ),
           'default' => 'attachment',
           'sizehint' => 5
      ),
      
      'description' => array('name' => 'post_content', 'type' => 'text', 'sizehint' => 10),
      
      'caption' => array('name' => 'post_excerpt', 'type' => 'text', 'sizehint' => 10),
      
      'parent_id'  => array('name' => 'post_parent','type' => 'string', 'sizehint' => 3),

      'publishing' => array(
        'name' => 'post_status',            
        'type' => 'string',
        'values' => array(
          'inherit',
          'draft',
          'trash',
        ),
        'default' => 'inherit', 
        'sizehint' => 5
      ),
    );
    $table = apply_filters( 'WCAPI_image_model_attributes_table', $table );
    return $table;
  }

  public static function getMetaAttributes() {
    $table = array(
      'path'  => array('name' => '_wp_attached_file', 'type' => 'string', 'sizehint' => 10),
      'metadata'  => array('name' => '_wp_attachment_metadata', 'type' => 'array', 'sizehint' => 10),
      'alt'  => array('name' => '_wp_attachment_image_alt', 'type' => 'string', 'sizehint' => 10),
    );
    /*
      With this filter, plugins can extend this ones handling of meta attributes for a customer,
      this helps to facilitate interoperability with other plugins that may be making arcane
      magic with a customer, or want to expose their customer extensions via the api.
    */
    $table = apply_filters( 'WCAPI_image_meta_attributes_table', $table );
    return $table;
  }

   public static function setupMetaAttributes() {
    // We only accept these attributes.
    static::$_meta_attributes_table = self::getMetaAttributes();
  } // end setupMetaAttributes
  public static function setupModelAttributes() {
    self::$_model_settings = self::getModelSettings();
    self::$_model_attributes_table = self::getModelAttributes();
  }
 
  public function create($attrs) {

  }
}