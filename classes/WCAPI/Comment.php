<?php
namespace WCAPI;
/**
 * A Customer class to insulate the API from the details of the
 * database representation
*/
require_once(dirname(__FILE__) . "/Base.php");
require_once(dirname(__FILE__) . "/Category.php");
class Comment extends Base {
   public static function setupMetaAttributes() {
    // We only accept these attributes.
    static::$_meta_attributes_table = array(
      
    );
    /*
      With this filter, plugins can extend this ones handling of meta attributes for a customer,
      this helps to facilitate interoperability with other plugins that may be making arcane
      magic with a customer, or want to expose their customer extensions via the api.
    */
    static::$_meta_attributes_table = apply_filters( 'WCAPI_user_meta_attributes_table', static::$_meta_attributes_table );
  } // end setupMetaAttributes
  public static function setupModelAttributes() {
    $wpdb = self::$adapter;
    self::$_model_settings = array_merge( Base::getDefaultModelSettings(), array(
      'model_table'                => $wpdb->comments,
      'meta_table'                => $wpdb->commentmeta,
      'model_table_id'             => 'comment_ID',
      'meta_table_foreign_key'    => 'comment_id',
      'meta_function'             => 'get_comment_meta',
      'update_meta_function'      => 'update_comment_meta',
      //'model_conditions' => "WHERE post_type IN ('product','product_variation')",
      'belongs_to' => array(
        'order' => array(
            'class_name' => 'Order', 
            'foreign_key' => 'comment_post_ID',
            
        ),
      ),
    ) );
    self::$_model_attributes_table = array(
      'name'            => array('name' => 'comment_author',        'type' => 'string'),
      'date'            => array('name' => 'comment_date_gmt',      'type' => 'string'),
      'email'           => array('name' => 'comment_author_email',  'type' => 'string'),
      'body'            => array('name' => 'comment_content',       'type' => 'text'),
      'approved'        => array('name' => 'comment_approved',      'type' => 'number'),
      'object_id'       => array('name' => 'comment_post_ID',       'type' => 'number'),
      'parent_id'       => array('name' => 'comment_parent',        'type' => 'number'),
      'user_id'         => array('name' => 'user_id',               'type' => 'number'),
      'type'            => array('name' => 'comment_type',
                                 'type' => 'string',
                                 'values' => array(
                                                    'order_note',
                                              ) 
                          ),
    );
    self::$_model_attributes_table = apply_filters( 'WCAPI_comment_model_attributes_table', self::$_model_attributes_table );
  }
 
  
}