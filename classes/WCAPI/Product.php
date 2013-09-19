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

  public static function getModelSettings() {
    include WCAPIDIR."/_globals.php";
    $table = array_merge( Base::getDefaultModelSettings(), array(
        'model_table'                => $wpdb->posts,
        'meta_table'                => $wpdb->postmeta,
        'model_table_id'             => 'id',
        'meta_table_foreign_key'    => 'post_id',
        'model_conditions' => "WHERE post_type IN ('product','product_variation') AND post_status NOT IN ('trash','auto-draft')",
        'has_many' => array(
          'order_items' => array('class_name' => 'OrderItem', 'foreign_key' => 'order_id'),
          'categories' => array(
              'class_name' => 'Category', 
              'foreign_key' => '', 
              'sql' => "SELECT t.term_id FROM 
                {$wpdb->terms} AS t, 
                {$wpdb->term_taxonomy} AS tt, 
                {$wpdb->term_relationships} AS tr 
              WHERE
                tr.object_id = %s AND
                tt.term_taxonomy_id = tr.term_taxonomy_id AND
                tt.taxonomy = 'product_cat' AND
                t.term_id = tt.term_id
              ",
              'connect' => function ($product,$category) {
                include WCAPIDIR."/_globals.php";
                $product->insert($wpdb->term_relationships, array(
                    'object_id' => $product->_actual_model_id,
                    'term_taxonomy_id' => $category->taxonomy_id,
                  ) 
                );
              }
          ),
          'tags' => array(
              'class_name' => 'Category', 
              'foreign_key' => '', 
              'sql' => "SELECT t.term_id FROM 
                {$wpdb->terms} AS t, 
                {$wpdb->term_taxonomy} AS tt, 
                {$wpdb->term_relationships} AS tr 
              WHERE
                tr.object_id = %s AND
                tt.term_taxonomy_id = tr.term_taxonomy_id AND
                tt.taxonomy = 'product_tag' AND
                t.term_id = tt.term_id
              ",
              'connect' => function ($product,$tag) {
                include WCAPIDIR."/_globals.php";
                $product->insert($wpdb->term_relationships, array(
                    'object_id' => $product->_actual_model_id,
                    'term_taxonomy_id' => $tag->taxonomy_id,
                  ) 
                );
              }
          ),
          'reviews' => array(
              'class_name' => 'Review', 
              'foreign_key' => 'comment_post_ID', 
              'conditions' => array(
                "comment_approved != 'trash'"
              ),
          ),
        ),
      ) 
    );
    $table = apply_filters('WCAPI_product_model_settings',$table);
    return $table;
  }
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
  public static function getMetaAttributes() {
    $table = array(
      'sku'               => array('name' => '_sku',              'type' => 'string', 'sizehint' => 10),
      'downloadable'      => array('name' => '_downloadable',     'type' => 'bool', 'values' => array('yes','no'), 'default' => 'no',  'sizehint' => 2),
      'visibility'        => array('name' => '_visibility',       'type' => 'string','default' => 'visible','sizehint' => 5),
      'virtual'           => array(
                                    'name' => '_virtual',          
                                    'type' => 'bool',
                                    'values' => array('yes','no'), 
                                    'sizehint' => 2,
                                    'default' => 'no'
                             ),
      'manage_stock'      => array('name' => '_manage_stock',     'type' => 'bool','values' => array('yes','no'), 'default' => 'no', 'sizehint' => 2),
      'sold_individually' => array(
                                      'name' => '_sold_individually',
                                      'type' => 'bool',
                                      'values' => array('yes','no'), 
                                      'sizehint' => 2,
                                      'default' => 'no', 
                              ),
      'featured'          => array('name' => '_featured',         'type' => 'bool','values' => array('yes','no'),'default' => 'no',  'sizehint' => 2),
      'allow_backorders'  => array(
                              'name' => '_backorders',       
                              'type' => 'string', 
                              'values' => array('yes','no','notify'),
                              'default' => 'no', 
                              'sizehint' => 2
                             ),
      'quantity'          => array( 'name' => '_stock',            
                                    'type' => 'number', 
                                    'filters' => array('woocommerce_stock_amount') ,
                                    'sizehint' => 3
                              ),
      'height'            => array('name' => '_height',           'type' => 'number', 'sizehint' => 2),
      'weight'            => array('name' => '_weight',           'type' => 'number', 'sizehint' => 2),
      'length'            => array('name' => '_length',           'type' => 'number', 'sizehint' => 2),
      'price'             => array('name' => '_regular_price',            'type' => 'number', 'sizehint' => 3),
      'sale_price'        => array('name' => '_sale_price',       'type' => 'number', 'sizehint' => 3),
      'sale_from'         => array('name' => '_sale_price_dates_from', 'type' => 'timestamp', 'sizehint' => 6),
      'sale_to'           => array('name' => '_sale_price_dates_to',   'type' => 'timestamp', 'sizehint' => 6),
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
                               'default' => 'instock', 
                               'sizehint' => 4
                             ),
      'attributes'        => array(
                              'name' => '_product_attributes',   
                              'type' => 'array', 
                              'default' => '',
                              'sizehint' => 3,
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
                               'default' => 'none',
                               'sizehint' => 1,
                             ),
    );
    /*
      With this filter, plugins can extend this ones handling of meta attributes for a product,
      this helps to facilitate interoperability with other plugins that may be making arcane
      magic with a product, or want to expose their product extensions via the api.
    */
    $table = apply_filters( 'WCAPI_product_meta_attributes_table', $table );
    return $table;
  }
  public static function getModelAttributes() {
    $table = array(
      'name'                  => array('name' => 'post_title', 'type' => 'string', 'sizehint' => 10),
      'slug'                  => array('name' => 'post_name',  'type' => 'string', 'sizehint' => 10),
      'type'                  => array('name' => 'post_type',
                                       'type' => 'string',
                                       'values' => array(
                                                          'product',
                                                          'product_variation'
                                                    ),
                                       'default' => 'product',
                                       'sizehint' => 5
                                  ),
      'description'           => array('name' => 'post_content',          'type' => 'text', 'sizehint' => 10),
      'short_description'     => array('name' => 'post_excerpt',          'type' => 'text', 'sizehint' => 10),
      'parent_id'             => array('name' => 'post_parent',           'type' => 'string', 'sizehint' => 3),
      'publishing'            => array(
                                    'name' => 'post_status',            
                                    'type' => 'string',
                                    'values' => array(
                                      'publish',
                                      'inherit',
                                      'pending',
                                      'future',
                                      'draft',
                                      'trash',
                                    ),
                                    'default' => 'publish', 
                                    'sizehint' => 5
                                  ),
    );
    $table = apply_filters( 'WCAPI_product_model_attributes_table', $table );
    return $table;
  }
  public static function setupMetaAttributes() {
    // We only accept these attributes.
    self::$_meta_attributes_table = self::getMetaAttributes();
  } // end setupMetaAttributes
  
  public static function setupModelAttributes() {
    self::$_model_settings = self::getModelSettings();
    self::$_model_attributes_table = self::getModelAttributes();
  }
  
  public function asApiArray() {
    include WCAPIDIR."/_globals.php";
    // $category_objs = woocommerce_get_product_terms($this->_actual_model_id, 'product_cat', 'all');
    // $categories = array();

    // foreach ( $category_objs as $cobj ) {
    //   // This looks scary if you've never used Javascript () evaluates the
    //   // the contents and returns the value, in the same way that (3+4) * 8 
    //   // works. Because we define the class with a Fluid API, most functions
    //   // that modify state of the object, return the object.
    //   try {
    //     $_cat = new Category();
    //     $categories[] = $_cat->setCategory( $cobj )->asApiArray();
    //   } catch (Exception $e) {
    //     // we should put some logging here soon!
    //     JSONAPIHelpers::error( $e->getMessage() );
    //   }
      
    // }
    $attributes_to_send = parent::asApiArray();
    $attributes_to_send['categories'] = $this->categories;
    $attributes_to_send['tags'] = $this->tags;//wp_get_post_terms($this->_actual_model_id,'product_tag');
    $attributes_to_send['reviews'] = $this->reviews;
    $feat_image = wp_get_attachment_url( get_post_thumbnail_id( $this->_actual_model_id) );
    $attributes_to_send['featured_image'] = $feat_image;
    return $attributes_to_send;
  }

  public static function find_by_sku( $sku ) {
    include WCAPIDIR."/_globals.php";
    $product = new Product();
    $product->setValid( false );
    $pid = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1",$sku) );
    if ( $pid ) {
      $product = Product::find( $pid );
    }
    return $product;
  }
   
}
