<?php
namespace WCAPI;
/**
 * A Product class to insulate the API from the details of the
 * database representation
*/
require_once(dirname(__FILE__) . "/Base.php");
require_once(dirname(__FILE__) . "/Category.php");
require_once(dirname(__FILE__) . "/OrderItem.php");
class Coupon extends Base{   
  public $_product_ids;
  public $_exclude_product_ids;
  public static function getModelSettings() {
    include WCAPIDIR."/_globals.php";
    $table = array_merge( Base::getDefaultModelSettings(), array(
        'model_table'                => $wpdb->posts,
        'meta_table'                => $wpdb->postmeta,
        'model_table_id'             => 'id',
        'meta_table_foreign_key'    => 'post_id',
        'model_conditions' => "WHERE post_type IN ('shop_coupon') AND post_status NOT IN ('trash','auto-draft')",
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
        ),
      ) 
    );
    $table = apply_filters('WCAPI_coupon_model_settings',$table);
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
      'discount_type' => array(
        'name' => 'discount_type',
        'type' => 'string', 
        'valus' => array(
          'fixed_cart',
          'percent',
          'fixed_product',
          'percent_product'
        ),
        'sizehint' => 2,
      ),
      'value' => array(
        'name' => 'coupon_amount',
        'type' => 'number', 
        'sizehint' => 1,
      ),
      // Exclusive = adjective: excluding or not admitting other things.
      // I think this is what they meant by individual use.
      'is_exclusive' => array(
        'name' => 'individual_use',
        'type' => 'bool',
        'values' => array('yes','no'), 
        'sizehint' => 1,
      ),
      'limit' => array(
        'name' => 'usage_limit',
        'type' => 'number',
        'sizehint' => 1,
      ),
      'product_ids' => array(
        'name' => 'product_ids',
        'type' => 'array', 
        'sizehint' => 10,
        'getter' => function ($model, $name, $desc, $filter_value) {
          return $model->updateProductIds('getter',$name,$desc,null);  
        },
        'setter' => function ($model, $name, $desc, $value, $filter_value ) {
            $model->updateProductIds('setter',$name,$desc,$value); 
        },
        'updater' => function ($model, $name, $desc, $value, $filter_value ) {
            $model->updateProductIds('updater',$name,$desc,$value); 
        },
      ),
      'exclude_product_ids' => array(
        'name' => 'exclude_product_ids',
        'type' => 'string', 
        'sizehint' => 10,
        'getter' => function ($model, $name, $desc, $filter_value) {
          return $model->updateProductIds('getter',$name,$desc,null);  
        },
        'setter' => function ($model, $name, $desc, $value, $filter_value ) {
            $model->updateProductIds('setter',$name,$desc,$value); 
        },
        'updater' => function ($model, $name, $desc, $value, $filter_value ) {
            $model->updateProductIds('updater',$name,$desc,$value); 
        },
      ),
      'expiry_date' => array(
        'name' => 'expiry_date',
        'type' => 'date(y-m-d)', 
        'sizehint' => 10,
      ),
      'is_before_tax' => array(
        'name' => 'apply_before_tax',
        'type' => 'bool',
        'values' => array('yes','no') ,
        'sizehint' => 1,
      ),
      'is_shipping_free' => array(
        'name' => 'shipping_free',
        'type' => 'bool',
        'values' => array('yes','no') ,
        'sizehint' => 1,
      ),
      'will_exclude_sale_products' => array(
        'name' => 'exclude_sale_items',
        'type' => 'bool',
        'values' => array('yes','no') ,
        'sizehint' => 1,
      ),
      'product_category_ids' => array(
        'name' => 'product_categories',
        'type' => 'array', 
        'sizehint' => 10,
      ),
      'exclude_product_category_ids' => array(
        'name' => 'product_categories',
        'type' => 'array', 
        'sizehint' => 10,
      ),
      'minimum_total' => array(
        'name' => 'minimum_amount',
        'type' => 'number', 
        'sizehint' => 1,
      ),
      'customer_email' => array(
        'name' => 'customer_email',
        'type' => 'number', 
        'sizehint' => 1,
      ),
    );
    /*
      With this filter, plugins can extend this ones handling of meta attributes for a product,
      this helps to facilitate interoperability with other plugins that may be making arcane
      magic with a product, or want to expose their product extensions via the api.
    */
    $table = apply_filters( 'WCAPI_coupon_meta_attributes_table', $table );
    return $table;
  }
  public static function getModelAttributes() {
    $table = array(
      'code' => array(
        'name' => 'post_title', 
        'type' => 'string', 
        'sizehint' => 10
      ),
      'slug' => array(
        'name' => 'post_name',  
        'type' => 'string', 
        'sizehint' => 10
      ),
      'type' => array(
        'name' => 'post_type',
        'type' => 'string',
        'default' => 'shop_coupon',
        'sizehint' => 5
      ),
      'publishing'  => array(
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
    $table = apply_filters( 'WCAPI_coupon_model_attributes_table', $table );
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
  

  public static function find_by_sku( $sku ) {
    include WCAPIDIR."/_globals.php";
    $product = new Product();
    $product->setValid( false );
    $pid = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->posts} WHERE post_title = %s' LIMIT 1",$sku) );
    if ( $pid ) {
      $product = Product::find( $pid );
    }
    return $product;
  }

  public function updateProductIds($type,$name,$desc,$value) {
    
    if ( $type == 'getter') {
      if ( isset( $this->{"_{$desc['name']}"} ) ) {
        return $this->{"_{$desc['name']}"};
      }
      $str = maybe_unserialize(get_post_meta($this->_actual_model_id, $desc['name'],true));
      if ( is_string($str) ) {
        $str = explode(",",$str);
      } else {
        array_map('maybe_unserialize',$str);
      }
      $this->{"_{$desc['name']}"} = $str;
      return $this->{"_{$desc['name']}"};
    } else if ( $type == 'setter' ) {
      $this->{"_{$desc['name']}"} = $value;
    } else if ( $type == 'updater' ) {
      update_post_meta($this->_actual_model_id,$desc['name'],$value);
    } else {
      throw new \Exception("updateProductIds does not understand type of $type");
    }
  }
   
}
