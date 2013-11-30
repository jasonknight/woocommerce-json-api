<?php
namespace WCAPI;
/**
 * A Customer class to insulate the API from the details of the
 * database representation
*/
require_once(dirname(__FILE__) . "/Base.php");
require_once(dirname(__FILE__) . "/Category.php");
require_once(dirname(__FILE__) . "/Upload.php");
class Image extends Base {
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

    $upload_dir = wp_upload_dir();

    $table = array(
      'path'  => array('name' => '_wp_attached_file', 'type' => 'string', 'sizehint' => 10),
      'metadata'  => array('name' => '_wp_attachment_metadata', 'type' => 'array', 'sizehint' => 10),
      'alt'  => array('name' => '_wp_attachment_image_alt', 'type' => 'string', 'sizehint' => 10),

    );

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

  public function create($attrs = NULL) {


    Helpers::debug("Image::create() was called");
    include WCAPIDIR . "/_globals.php";
    include WCAPIDIR."/_model_static_attributes.php";
    $name = $attrs['name'];

    if ( isset( $_FILES['images']) ) {


      foreach ($_FILES["images"]["error"] as $key => $error) {
          if ($error == UPLOAD_ERR_OK) {
              $fname = basename($_FILES["images"]["name"][$key]);
              if ( $fname == $name) {
                $upload = new Upload(
                  $_FILES["images"]["tmp_name"][$key],
                  $_FILES["images"]["name"][$key],
                  $_FILES["images"]["error"][$key],
                  $_FILES["images"]["size"][$key]
                );
                $id = $upload->saveMediaAttachment();
                $record = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$self->settings['model_table']} WHERE {$self->settings['model_table_id']} = %d", (int) $id), 'ARRAY_A' );
                if ( $record ) {
                  $this->fromDatabaseResult( $record );
                  $this->setNewRecord(true); // i.e. we want to know if models were created in this request.
                  Helpers::debug("Set attrs from record: " . var_export($record,true));
                } else {
                  Helpers::debug("Did not get record! setting valid to false");
                  $this->setValid(false);
                }
                return $this;
              }
          }
      } // end foreach

      // We shouldn't get this far if we did it right...
      Helpers::debug("Image::create: throwing exception No entry for %s found in uploaded files!");
      throw new \Exception( sprintf( __('No entry for %s found in uploaded files!',$this->td), $name ) );

    } else {
      Helpers::debug("Image::create: throwing exception You cannot create a new image unless you upload an image with the same name as %s");
      Helpers::debug( var_export($_FILES,true));
      throw new \Exception( sprintf(__('You cannot create a new image unless you upload an image with the same name as %s',$this->td),$name) );
    }
  }
  public function asApiArray($args = array()) {
    $attributes = parent::asApiArray();

    if ( isset($attributes['metadata']) ) {

      $upload_dir = wp_get_attachment_image_src( $this->_actual_model_id, 'full');
      $md = $attributes['metadata'];
      $md['url'] = $upload_dir[0];
      $attributes['metadata'] = $md;
      if ( isset($attributes['metadata']['sizes']) && is_array($attributes['metadata']['sizes'])) {
        foreach ( $attributes['metadata']['sizes'] as $key=>&$md ) {
          // $upload_dir = wp_get_attachment_image_src( $this->_actual_model_id, $key);
          // $md['url'] = $upload_dir[0];
          $medium_array = image_downsize( $this->_actual_model_id, $key );
          $md['url'] = $medium_array[0];
          if ( isset($md['get_attachment_metadata is']) ) {
            unset($md['get_attachment_metadata is']);
          }
          if ( isset($md['array is'])) {
            unset($md['array is']);
          }
          //$md['array is'] = $medium_array;
          //$md['get_attachment_metadata is'] = wp_get_attachment_metadata($this->_actual_model_id);
        }
      }
    }
    return $attributes;
  }
  public function fromApiArray($attributes) {

    if ( isset($attributes['metadata']) ) {


      $md = $attributes['metadata'];
      unset($md['url']);
      $attributes['metadata'] = $md;
      $upload_dir = wp_upload_dir();
      foreach ( $attributes['metadata']['sizes'] as $key=>&$md ) {
        if ( isset($md['url']) ) {
          unset($md['url']);
        }
        if ( isset($md['get_attachment_metadata is']) ) {
          unset($md['get_attachment_metadata is']);
        }
        if ( isset($md['array is'])) {
          unset($md['array is']);
        }
      }


    }

    parent::fromApiArray( $attributes );
  }

}