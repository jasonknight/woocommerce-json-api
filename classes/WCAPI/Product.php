<?php
namespace WCAPI;
/**
 * A Product class to insulate the API from the details of the
 * database representation
*/
require_once(dirname(__FILE__) . "/Base.php");
require_once(dirname(__FILE__) . "/Category.php");
require_once(dirname(__FILE__) . "/OrderItem.php");
class Product extends Base{   
  /**
  * Here we normalize the attributes, giving them a consistent name scheme and obvious
  * meaning, as well as making them easier to type so that we have a nice, user
  * friendly interface into WooCom.
  * 
  * We also need to be able to validate the inputs from outside, so we have to 
  * attach information to each key about what it can contain, and how we should
  * cast things to and from the DB.
  * 
  * When we say bool, we mean a WP Bool, which is `yes` or `no`. I actually prefer this
  * idea, because of the way PHP and many languages handle boolean values. It's just
  * so much more clear.
  *
  * The fundamental idea for this class is that there doesn't seem to be a single entry
  * point into and out of the database for WooCom which provides a mixture of classes
  * and functions that get, process, display, and save products to the database and that
  * depend on things like $_POST and various Defines. 
  *
  * We want to abstract away the naughty bits of the database representation of the product
  * in question.
  */
  public static function setupMetaAttributes() {
    if ( static::$_meta_attributes_table ) {
      return;
    }
    // We only accept these attributes.
    static::$_meta_attributes_table = array(
      'sku'               => array('name' => '_sku',              'type' => 'string'),
      'downloadable'      => array('name' => '_downloadable',     'type' => 'bool'),
      'virtual'           => array('name' => '_virtual',          'type' => 'bool'),
      'manage_stock'      => array('name' => '_manage_stock',     'type' => 'bool'),
      'sold_individually' => array('name' => '_sold_individually','type' => 'bool'),
      'featured'          => array('name' => '_featured',         'type' => 'bool'),
      'allow_backorders'  => array(
                              'name' => '_backorders',       
                              'type' => 'string', 
                              'values' => array('yes','no','notify')
                             ),
      'quantity'          => array('name' => '_stock',            'type' => 'number', 'filters' => array('woocommerce_stock_amount') ),
      'height'            => array('name' => '_height',           'type' => 'number'),
      'weight'            => array('name' => '_weight',           'type' => 'number'),
      'length'            => array('name' => '_length',           'type' => 'number'),
      'price'             => array('name' => '_price',            'type' => 'number'),
      'regular_price'     => array('name' => '_regular_price',    'type' => 'number'),
      'sale_price'        => array('name' => '_sale_price',       'type' => 'number'),
      'sale_from'         => array('name' => '_sale_price_dates_from', 'type' => 'timestamp'),
      'sale_to'           => array('name' => '_sale_price_dates_to',   'type' => 'timestamp'),
      // 'download_paths'    => array('name' => '_file_paths',            
      //                              'type' => 'array', 
      //                              'filters' => array('woocommerce_file_download_paths') 
      //                         ),
      'status'            => array(
                               'name' => '_stock_status',          
                               'type' => 'string', 
                               'values' => array(
                                'instock',
                                'outofstock',
                               ),
                             ),
      'attributes'        => array(
                              'name' => '_attributes',   
                              'type' => 'object', 
                              'class' => 'ProductAttributes',
                             ), 
      'tax_class'         => array('name' => '_tax_class',        'type' => 'string'),
      'tax_status'        => array(
                               'name' => '_tax_status',           
                               'type' => 'string',
                               'values' => array(
                                'taxable',
                                'shipping',
                                'none',
                               ),
                             ),
    );
    /*
      With this filter, plugins can extend this ones handling of meta attributes for a product,
      this helps to facilitate interoperability with other plugins that may be making arcane
      magic with a product, or want to expose their product extensions via the api.
    */
    static::$_meta_attributes_table = apply_filters( 'woocommerce_json_api_product_meta_attributes_table', static::$_meta_attributes_table );
  } // end setupMetaAttributes
  
  public static function setupModelAttributes() {
    static::$_model_settings = array_merge( Base::getDefaultModelSettings(), array(
      'model_table'                => $wpdb->posts,
      'meta_table'                => $wpdb->postmeta,
      'model_table_id'             => 'id',
      'meta_table_foreign_key'    => 'post_id',
      'model_conditions' => "WHERE post_type IN ('product','product_variation')",
      'has_many' => array(
        'order_items' => array('class_name' => 'OrderItem', 'foreign_key' => 'order_id'),
      ),
    ) );
    if ( static::$_model_attributes_table ) {
      return;
    }
    static::$_model_attributes_table = array(
      'name'            => array('name' => 'post_title',             'type' => 'string'),
      'slug'            => array('name' => 'post_name',              'type' => 'string'),
      'type'            => array('name' => 'post_type',              'type' => 'string'),
      'description'     => array('name' => 'post_content',           'type' => 'string'),
      'parent_id'       => array('name' => 'post_parent',           'type' => 'string'),
      'publishing'          => array(
                                  'name' => 'post_status',            
                                  'type' => 'string',
                                  'values' => array(
                                    'publish',
                                    'inherit',
                                    'pending',
                                    'public',
                                    'future',
                                    'draft',
                                    'trash',
                                  ),
                          ),
    );
    static::$_model_attributes_table = apply_filters( 'woocommerce_json_api_product_model_attributes_table', static::$_model_attributes_table );
  }
  
  public function asApiArray() {
    $wpdb = static::$adapter;
    $category_objs = woocommerce_get_product_terms($this->_actual_model_id, 'product_cat', 'all');
    $categories = array();

    foreach ( $category_objs as $cobj ) {
      // This looks scary if you've never used Javascript () evaluates the
      // the contents and returns the value, in the same way that (3+4) * 8 
      // works. Because we define the class with a Fluid API, most functions
      // that modify state of the object, return the object.
      try {
        $_cat = new Category();
        $categories[] = $_cat->setCategory( $cobj )->asApiArray();
      } catch (Exception $e) {
        // we should put some logging here soon!
        JSONAPIHelpers::error( $e->getMessage() );
      }
      
    }
    $attributes = array_merge(static::$_model_attributes_table, static::$_meta_attributes_table);
    $attributes_to_send['id'] = $this->getModelId();
    foreach ( $attributes as $name => $desc ) {
      $attributes_to_send[$name] = $this->dynamic_get( $name, $desc, $this->getModelId());
    }
    $attributes_to_send['categories'] = $categories;
    $attributes_to_send['tags'] = wp_get_post_terms($this->_actual_model_id,'product_tag');
    $feat_image = wp_get_attachment_url( get_post_thumbnail_id( $this->_actual_model_id) );
    $attributes_to_send['featured_image'] = $feat_image;
    return $attributes_to_send;
  }

  public static function find_by_sku( $sku ) {
    $wpdb = static::$adapter;
    $product = new Product();
    $product->setValid( false );
    $pid = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1",$sku) );
    if ( $pid ) {
      $product = Product::find( $pid );
    }
    return $product;
  }
  
  public function create( $attrs = null ) {
    $wpdb = static::$adapter;
    $user_ID = $GLOBALS['user_ID'];
    // We should setup attrib tables if it hasn't
    // already been done
    static::setupModelAttributes();
    static::setupMetaAttributes();
    // Maybe we want to set attribs and create in one go.
    if ( $attrs ) {
      foreach ( $attrs as $name=>$value ) {
        $this->{ $name } = $value;
      }
    }
    $post = array( 'post_author' => $user_ID, 'post_type' => 'product');
    foreach (static::$_model_attributes_table as $attr => $desc) {
      $value = $this->dynamic_get( $attr, $desc, null);
      $post[ $desc['name'] ] = $value;
    }
    $post['post_type'] = 'product';
    $id = wp_insert_post( $post, true);
    if ( is_wp_error( $id )) {
      // we  should handle errors
      $this->setValid(false);
      $this->_result->addError( __('Failed to create product'), WCAPI_CANNOT_INSERT_RECORD );
    } else {
      $this->setValid(true);
      foreach (static::$_meta_attributes_table as $attr => $desc) {
        if ( isset( $this->_meta_attributes[$attr] ) ) {
          $value = $this->_meta_attributes[$attr];
          if ( ! empty($value) ) {
            update_post_meta($id,$desc['name'],$value);
          }
        } 
      }  
      $this->_actual_model_id = $id;
    }
    return $this;
  }


  
  
}
