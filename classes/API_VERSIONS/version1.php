<?php 
require_once( plugin_dir_path(__FILE__) . '/../class-rede-helpers.php' );
require_once( dirname(__FILE__) . '/../WCAPI/includes.php' );

use WCAPI as API;
class WC_JSON_API_Provider_v1 extends JSONAPIHelpers {
  public $helpers;
  public $result;
  public $the_user;
  public $provider;
  public $parent;
  public static $implemented_methods;

  public static function getImplementedMethods() {
    $accepted_resources = array('Product','ProductAttribute','Category','Comment','Order','OrderItem','OrderTaxItem','OrderCouponItem','Customer','Coupon','Review','Image');
    self::$implemented_methods = array(
      'get_system_time' => null,
      'get_supported_attributes' => array(
          'resources' => array(
            'type' => 'array',
            'values' => $accepted_resources,
            'default' => $accepted_resources,
            'required' => false,
            'sizehint' => 10,
            'description' => __('List what resources you would like additional information on.','woocommerce_json_api'),
          ),
        ),
      'get_products' => array(
        'order_by' => array(
          'type' => 'string',
          'values' => array('ID','post_title','post_date','post_author','post_modified','woocommerce_json_api'),
          'default' => "ID",
          'required' => false,
          'sizehint' => 1,
          'description' => __('What column to order results by','woocommerce_json_api'),
        ),
        'order' => array(
          'type' => 'number',
          'values' => array('ASC','DESC'),
          'default' => 'ASC',
          'required' => false,
          'sizehint' => 1,
          'description' => __('What order to show the results in','woocommerce_json_api'),
        ),
        'page' => array(
          'type' => 'number',
          'values' => null,
          'default' => 1,
          'required' => false,
          'sizehint' => 1,
          'description' => __('What page to show.','woocommerce_json_api'),
        ),
        'per_page' => array(
          'type' => 'number',
          'values' => null,
          'default' => 15,
          'sizehint' => 1,
          'required' => false,
          'description' => __('How many results to show','woocommerce_json_api'),
        ),
        'ids' => array(
          'type' => 'array',
          'values' => null,
          'default' => null,
          'required' => false,
          'sizehint' => 10,
          'description' => __('An array of IDs to use as a filter','woocommerce_json_api'),
        ),
        'skus' => array(
          'type' => 'array',
          'values' => null,
          'default' => null,
          'required' => false,
          'sizehint' => 10,
          'description' => __('An array of SKUs to use as a filter','woocommerce_json_api'),
        ),
        'parent_ids' => array(
          'type' => 'array',
          'values' => null,
          'default' => null,
          'required' => false,
          'sizehint' => 10,
          'description' => __('An array of parent IDs to use as a filter','woocommerce_json_api'),
        ),

      ),
      'get_categories' => array(
        'order_by' => array(
          'type' => 'string',
          'values' => array('id','count','name','slug'),
          'default' => "name",
          'required' => false,
          'sizehint' => 1,
          'description' => __('What column to order results by','woocommerce_json_api'),
        ),
        'order' => array(
          'type' => 'number',
          'values' => array('ASC','DESC'),
          'default' => 'ASC',
          'required' => false,
          'sizehint' => 1,
          'description' => __('What order to show the results in','woocommerce_json_api'),
        ),
        'ids' => array(
          'type' => 'array',
          'values' => null,
          'default' => null,
          'required' => false,
          'sizehint' => 10,
          'description' => __('An array of IDs to use as a filter','woocommerce_json_api'),
        ),
      ),
      'get_images' => array(
        'order_by' => array(
          'type' => 'string',
          'values' => array('id','count','name','slug'),
          'default' => "name",
          'required' => false,
          'sizehint' => 1,
          'description' => __('What column to order results by','woocommerce_json_api'),
        ),
        'order' => array(
          'type' => 'number',
          'values' => array('ASC','DESC'),
          'default' => 'ASC',
          'required' => false,
          'sizehint' => 1,
          'description' => __('What order to show the results in','woocommerce_json_api'),
        ),
        'ids' => array(
          'type' => 'array',
          'values' => null,
          'default' => null,
          'required' => false,
          'sizehint' => 10,
          'description' => __('An array of IDs to use as a filter','woocommerce_json_api'),
        ),
        'parent_ids' => array(
          'type' => 'array',
          'values' => null,
          'default' => null,
          'required' => false,
          'sizehint' => 10,
          'description' => __('An array of parent IDs to use as a filter','woocommerce_json_api'),
        ),
        'page' => array(
          'type' => 'number',
          'values' => null,
          'default' => 1,
          'required' => false,
          'sizehint' => 1,
          'description' => __('What page to show.','woocommerce_json_api'),
        ),
        'per_page' => array(
          'type' => 'number',
          'values' => null,
          'default' => 15,
          'sizehint' => 1,
          'required' => false,
          'description' => __('How many results to show','woocommerce_json_api'),
        ),
      ),
      'get_coupons' => null,
      'get_taxes' => null,
      'get_shipping_methods' => null,
      'get_payment_gateways' => null,
      'get_tags' => array(
          'order_by' => array(
            'type' => 'string',
            'values' => array('name','count','term_id'),
            'default' => "name",
            'required' => false,
            'sizehint' => 1,
            'description' => __('What column to order results by','woocommerce_json_api'),
          ),
          'order' => array(
            'type' => 'number',
            'values' => array('ASC','DESC'),
            'default' => 'ASC',
            'required' => false,
            'sizehint' => 1,
            'description' => __('What order to show the results in','woocommerce_json_api'),
          ),
        ),
      'get_products_by_tags' => array(
        'order_by' => array(
          'type' => 'string',
          'values' => array('ID','post_title','post_date','post_author','post_modified'),
          'default' => "ID",
          'required' => false,
          'sizehint' => 1,
          'description' => __('What column to order results by','woocommerce_json_api'),
        ),
        'order' => array(
          'type' => 'number',
          'values' => array('ASC','DESC'),
          'default' => 'ASC',
          'required' => false,
          'sizehint' => 1,
          'description' => __('What order to show the results in','woocommerce_json_api'),
        ),
        'page' => array(
          'type' => 'number',
          'values' => null,
          'default' => 1,
          'required' => false,
          'sizehint' => 1,
          'description' => __('What page to show.','woocommerce_json_api'),
        ),
        'per_page' => array(
          'type' => 'number',
          'values' => null,
          'default' => 15,
          'required' => false,
          'sizehint' => 1,
          'description' => __('How many results to show','woocommerce_json_api'),
        ),
        'tags' => array(
          'type' => 'array',
          'values' => null,
          'default' => null,
          'required' => true,
          'sizehint' => 10,
          'description' => __('An array of tag slugs','woocommerce_json_api'),
        ),
      ),
      'get_customers' => array(
          'page' => array(
            'type' => 'number',
            'values' => null,
            'default' => 1,
            'required' => false,
            'sizehint' => 1,
            'description' => __('What page to show.','woocommerce_json_api'),
          ),
          'per_page' => array(
            'type' => 'number',
            'values' => null,
            'default' => 15,
            'required' => false,
            'sizehint' => 1,
            'description' => __('How many results to show','woocommerce_json_api'),
          ),
          'ids' => array(
            'type' => 'array',
            'values' => null,
            'default' => null,
            'required' => false,
            'sizehint' => 1,
            'description' => __('An array of IDs to use as a filter','woocommerce_json_api'),
          ),
        ),
      'get_orders' => array(
          'page' => array(
            'type' => 'number',
            'values' => null,
            'default' => 1,
            'required' => false,
            'sizehint' => 1,
            'description' => __('What page to show.','woocommerce_json_api'),
          ),
          'per_page' => array(
            'type' => 'number',
            'values' => null,
            'default' => 15,
            'required' => false,
            'sizehint' => 1,
            'description' => __('How many results to show','woocommerce_json_api'),
          ),
          'ids' => array(
            'type' => 'array',
            'values' => null,
            'default' => null,
            'required' => false,
            'sizehint' => 1,
            'description' => __('An array of IDs to use as a filter','woocommerce_json_api'),
          ),
        ),
      'get_store_settings' => array(
          'filter' => array(
            'type' => 'string',
            'values' => null,
            'default' => 1,
            'required' => false,
            'sizehint' => 4,
            'description' => __('A name filter to use','woocommerce_json_api'),
          ),
        ),
      'get_site_settings' => array(
          'filter' => array(
            'type' => 'string',
            'values' => null,
            'default' => 1,
            'required' => false,
            'sizehint' => 4,
            'description' => __('A name filter to use','woocommerce_json_api'),
          ),
        ),
      'get_api_methods' => null,
      
      // Write capable methods
      
      'set_products'  => array(
          'payload' => array(
            'type' => 'array',
            'values' => null,
            'default' => null,
            'required' => true,
            'sizehint' => 1,
            'description' => __('A collection of Product arrays for update/create, to create omit `id`','woocommerce_json_api'),
          ),
        ),
      'set_categories' => null,
      'set_orders' => null,
      'set_store_settings' => null,
      'set_site_settings' => null,

    );
    return self::$implemented_methods;
  }
  public function __construct( &$parent ) {
    //$this = new JSONAPIHelpers();
    $this->result = null;
    // We will use this to set perms
    self::getImplementedMethods();
    $this->parent = $parent;
    $this->result = &$this->parent->result;
    $this->the_user = $this->parent->the_user;
    parent::init();
  }
   
  public function isImplemented( $proc ) {
    
    if ( isset($proc) &&  
         $this->inArray( $proc, array_keys(self::$implemented_methods)) 
    ) {

      return true;

    } else {

      return false;
    }
  }
  
  public function done() {
    return $this->parent->done();
  }
  
  
  public function translateTaxRateAttributes( $rate ) {
    $attrs = array();
    foreach ( $rate as $k=>$v ) {
      $attrs[ str_replace('tax_rate_','',$k) ] = $v;
    }
    return $attrs;
  }


  /*******************************************************************
  *                         Core API Functions                       *
  ********************************************************************
  * These functions are called as a result of what was set in the
  * JSON Object for `proc`.
  ********************************************************************/
  
  public function get_system_time( $params ) {
    
    $data = array(
      'timezone'  => date_default_timezone_get(),
      'date'      => date("Y-m-d"),
      'time'      => date("h:i:s",time())
    );
    $this->result->addPayload($data);
    return $this->done();
  }
  
  /**
  * This is the single entry point for fetching products, ordering, paging, as well
  * as "finding" by ID or SKU.
  */
  public function get_products( $params ) {
    global $wpdb;
    $allowed_order_bys = array('ID','post_title','post_date','post_author','post_modified');
    /**
    *  Read this section to get familiar with the arguments of this method.
    */
    $posts_per_page = $this->orEq( $params['arguments'], 'per_page', 15 ); 
    $paged          = $this->orEq( $params['arguments'], 'page', 0 );
    $order_by       = $this->orEq( $params['arguments'], 'order_by', 'ID');
    $order          = $this->orEq( $params['arguments'], 'order', 'ASC');
    $ids            = $this->orEq( $params['arguments'], 'ids', false);
    $parent_ids     = $this->orEq( $params['arguments'], 'parent_ids', false);
    $skus           = $this->orEq( $params['arguments'], 'skus', false);
    
    $by_ids = true;
    if ( ! $this->inArray($order_by,$allowed_order_bys) ) {
      $this->result->addError( __('order_by must be one of these:','woocommerce_json_api') . join( $allowed_order_bys, ','), WCAPI_BAD_ARGUMENT );
      return $this->done();
      return;
    }
    if ( ! $ids && ! $skus ) {
        if ($parent_ids) {
          $posts = API\Product::all('id', "`post_parent` IN (" . join(",",$parent_ids) . ")")->per($posts_per_page)->page($paged)->fetch(function ( $result) {
            return $result['id'];
          });
        } else {
          $posts = API\Product::all()->per($posts_per_page)->page($paged)->fetch(function ( $result) {
            return $result['id'];
          });
        }
        
      JSONAPIHelpers::debug( "IDs from all() are: " . var_export($posts,true) );
    } else if ( $ids ) {
    
      $posts = $ids;
      
    } else if ( $skus ) {
    
      $posts = array();
      foreach ($skus as $sku) {
        $pid = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1",$sku) );
        if ( ! $pid ) {
          $this->result->addWarning( $sku . ': ' . __('Product does not exist','woocommerce_json_api'), WCAPI_PRODUCT_NOT_EXISTS, array( 'sku' => $sku) );
        } else {
          $posts[] = $pid;
        }
      }
      
    }

    $products = array();
    foreach ( $posts as $post_id) {
      $post = API\Product::find($post_id);

      if ( !$post ) {
        $this->result->addWarning( $post_id. ': ' . __('Product does not exist','woocommerce_json_api'), WCAPI_PRODUCT_NOT_EXISTS, array( 'id' => $post_id) );
      } else {

        $products[] = $post->asApiArray();
      }
      
    }
    // We manage the array ourselves, so call setPayload, instead of addPayload
    $this->result->setPayload($products);

    return $this->done();
  }
  public function get_supported_attributes( $params ) {
    $accepted_resources = array('Product','ProductAttribute','Category','Comment','Order','OrderItem','OrderTaxItem','OrderCouponItem','Customer','Coupon','Review','Image');
    $models = $this->orEq( $params['arguments'], 'resources', $accepted_resources);
    
    if ( ! is_array($models) ) {
      $this->badArgument('resources','an Array of Strings');
    }
    $results = array();
    foreach ( $models as $m ) {
      if ( $m == 'Product' ) {
        $model = new API\Product();
      } else if ( $m == 'Category' ) {
        $model = new API\Category();
      } else if ( $m == 'Order' ) {
        $model = new API\Order();
      } else if ( $m == 'OrderItem' ) {
        $model = new API\OrderItem();
      } else if ( $m == 'Customer' ) {
        $model = new API\Customer();
      } else if ( $m == 'Coupon' ) {
        $model = new API\Coupon();
      } else if ( $m == 'Comment' ) {
        $model = new API\Comment();
      } else if ( $m == 'Image' ) {
        $model = new API\Image();
      } else if ( $m == 'Review' ) {
        $model = new API\Review();
      } else if ( $m == 'OrderTaxItem' ) {
        $model = new API\OrderTaxItem();
      } else if ( $m == 'OrderCouponItem' ) {
        $model = new API\OrderCouponItem();
      } else if ( $m == 'ProductAttribute' ) {
        $model = new API\ProductAttribute();
      } else {
        $this->badArgument($m, join(',', $accepted_resources ) );
        return $this->done();
      }
      $results[$m] = $model->getSupportedAttributes() ;
    }
    $this->result->setPayload( array( $results ) );
    return $this->done();
  }
  public function get_products_by_tags($params) {
    global $wpdb;
    $allowed_order_bys = array('id','name','post_title');
    $terms = $this->orEq( $params['arguments'], 'tags', false);
    if ( ! $terms ) {
      $this->missingArgument('tags');
      return $this->done();
    }
    if ( !is_array($terms)) {
      $this->badArgument('terms',__('Requires an array of slugs','woocommerce_json_api') );
      return $this->done();
    }
    foreach ($terms as &$term) {
      $term = $wpdb->prepare("%s",$term);
    }
    if ( count($terms) < 1) {
      $this->result->addError( __('you must specify at least one term','woocommerce_json_api'), WCAPI_BAD_ARGUMENT );
      return $this->done();
    }
    $posts_per_page = $this->orEq( $params['arguments'], 'per_page', 15 ); 
    $paged          = $this->orEq( $params['arguments'], 'page', 0 );
    $order_by       = $this->orEq( $params['arguments'], 'order_by', 'id');
    if ( ! $this->inArray($order_by,$allowed_order_bys) ) {
      $this->result->addError( __('order_by must be one of these:','woocommerce_json_api') . join( $allowed_order_bys, ','), WCAPI_BAD_ARGUMENT );
      return $this->done();
      return;
    }
    $order          = $this->orEq( $params['arguments'], 'order', 'ASC');
    
    // It would be nice to use WP_Query here, but it seems to be semi-broken when working
    // with custom taxonomies like product_tag...
    // We don't really care about the distinctions here anyway, it's mostly superfluous, because
    // we only want posts of type product so we can just select against the terms and not care.

    $sql = "
              SELECT
                p.id 
              FROM 
                {$wpdb->posts} AS p, 
                {$wpdb->terms} AS t, 
                {$wpdb->term_taxonomy} AS tt, 
                {$wpdb->term_relationships} AS tr 
              WHERE 
                t.slug IN (" . join(',',$terms) . ") AND 
                tt.term_id = t.term_id AND 
                tr.term_taxonomy_id = tt.term_taxonomy_id AND 
                p.id = tr.object_id
            ";
    $ids = $wpdb->get_col( $sql );
    $params['arguments']['ids'] = $ids;
    return $this->get_products( $params );
  }
  /*
    Similar to get products, in fact, we should be able to resuse te response
    for that call to edit the products thate were returned.
    
    WooCom has as kind of disconnected way of saving a product, coming from Rails,
    it's a bit jarring. Most of this function is taken from woocommerce_admin_product_quick_edit_save()
    
    It seems that Product objects don't know how to save themselves? This may not be the
    case but a cursory search didn't find out exactly how products are really
    being saved. That's no matter because they are mainly a custom post type anyway,
    and most fields attached to them are just post_meta fields that are easy enough
    to find in the DB.
    
    There's certainly a more elegant solution to be found, but this has to get
    up and working, and be pretty straightforward/explicit. If I had the time,
    I'd write a custom Product class that knows how to save itself,
    and then just make setter methods modify internal state and then abstract out.
  */
    // FIXME: We need some way to ensure that adding of products is not
    // exploited. we need to track errors, and temporarily ban users with
    // too many. We need a way to lift the ban in the interface and so on.
  public function set_products( $params ) {
    JSONAPIHelpers::debug("set_products beginning");
    $products = $this->orEq( $params, 'payload', array() );
    foreach ( $products as &$attrs) {
      $product = null;
      if (isset($attrs['id'])) {
        $product = API\Product::find($attrs['id']);
      } else if ( isset($attrs['sku'])) {
        $product = API\Product::find_by_sku($attrs['sku']);
      }
      if ($product && is_object($product) && $product->isValid()) {
        $product->fromApiArray( $attrs );
        $product->update();
        $attrs = $product->asApiArray();
      } else {
        $this->result->addWarning( 
          __(
              'Product does not exist.',
              'woocommerce_json_api'
            ),
          WCAPI_PRODUCT_NOT_EXISTS, 
          array( 
            'id' => isset($attrs['id']) ? $attrs['id'] : 'none',
            'sku' => isset($attrs['sku']) ? $attrs['sku'] : 'none',
          )
        );
        // Let's create the product if it doesn't exist.
        JSONAPIHelpers::debug("Creating a new product");
        $product = new API\Product();
        $product->create( $attrs );
        if ( ! $product->isValid() ) {
          JSONAPIHelpers::debug("Product is not valid!");
          return $this->done();
        }
        $attrs = $product->asApiArray();
      }
    }
    $this->result->setPayload( $products );
    JSONAPIHelpers::debug("set_products done.");
    return $this->done();
  }

  
  /**
   *  Get product categories
  */
  public function get_categories( $params ) {
  
    $allowed_order_bys = array('id','count','name','slug');
    
    $order_by       = $this->orEq( $params['arguments'], 'order_by', 'name');
    if ( ! $this->inArray($order_by,$allowed_order_bys) ) {
      $this->badArgument('order_by', __('must be one of these:','woocommerce_json_api') . join( $allowed_order_bys, ','));
      return $this->done();
    }
    $order          = $this->orEq( $params['arguments'], 'order', 'ASC');
    $ids            = $this->orEq( $params['arguments'], 'ids', false);
    
    $hide_empty     = $this->orEq( $params['arguments'], 'hide_empty', false);
    
    $args = array(
      'fields'         => 'ids',
      'order_by'       => $order_by,
      'order'          => $order,
    );
    
    if ($ids) {
      $args['include'] = $ids;
    }
    
    $categories = get_terms('product_cat', $args);
    foreach ( $categories as $id ) {
      $category = API\Category::find( $id );
      $this->result->addPayload( $category->asApiArray() );
    }

    return $this->done();
  }
  
  public function set_categories( $params ) {
    $categories = $this->orEq( $params, 'payload', array());
    foreach ( $categories as &$category ) {
      if ( isset($category['id']) ) {
        $actual = API\Category::find( $category['id'] );
        $actual->fromApiArray($category);
        $actual->update();
      } else {
        $actual = new API\Category();
        $actual->create( $category );
        $category = $category->asApiArray();
      }
      $category = $actual->asApiArray();
    }
    $this->result->setPayload($categories);
    return $this->done();
  }
  /**
   * Get tax rates defined for store
  */
  public function get_taxes( $params ) {
    global $wpdb;
    
    $tax_classes = explode("\n",get_option('woocommerce_tax_classes'));
    $tax_classes = array_merge($tax_classes, array(''));
    
    $tax_rates = array();
    
    foreach ( $tax_classes as $tax) {
      $name = $tax;
      if ( $name == '' ) {
        $name = "DefaultRate";
      } 
      // Never have a select * without a limit statement.
      $found_rates = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates where tax_rate_class = %s LIMIT %d",$tax,100) );
      $rates = array();
      foreach ( $found_rates as $rate ) {
       
        $rates[] = $this->translateTaxRateAttributes($rate);
      }
      $tax_rates[] = array(
        'name' => $name,
        'rates' => $rates
      );
    }
    $this->result->setPayload($tax_rates); 
    return $this->done();   
  }
  /**
  * WooCommerce handles shipping methods on a per class/instance basis. So in order to have a
  * shipping method, we must have a class file that registers itself with 'woocommerce_shipping_methods'.
  */
  public function get_shipping_methods( $params ) {
    $klass = new WC_Shipping();
    $klass->load_shipping_methods();
    $methods = array();
    foreach ( $klass->shipping_methods as $sm ) {
      $methods[] = array(
        'id' => $sm->id,
        'name' => $sm->title,
        'display_name' => $sm->method_title,
        'enabled' => $sm->enabled,
        'settings' => $sm->settings,
        'plugin_id' => $sm->plugin_id,
      );
    }
    $this->result->setPayload( $methods );
    return $this->done();
  }
  
  /**
  *  Get info on Payment Gateways
  */
  public function get_payment_gateways( $params ) {
    $klass = new WC_Payment_Gateways();
    foreach ( $klass->payment_gateways as $sm ) {
      $methods[] = array(
        'id' => $sm->id,
        'name' => $sm->title,
        'display_name' => $sm->method_title,
        'enabled' => $sm->enabled,
        'settings' => $sm->settings,
        'plugin_id' => $sm->plugin_id,
      );
    }
    $this->result->setPayload( $methods );
    return $this->done();
  }
  public function get_tags( $params ) {
    $allowed_order_bys = array('name','count','term_id');
    $allowed_orders = array('DESC','ASC');
    $args['order']                = $this->orEq( $params['arguments'], 'order', 'ASC');
    $args['order_by']             = $this->orEq( $params['arguments'], 'order_by', 'name');

    if ( ! $this->inArray($args['order_by'],$allowed_order_bys) ) {
      $this->result->addError( __('order_by must be one of these:','woocommerce_json_api') . join( $allowed_order_bys, ','), WCAPI_BAD_ARGUMENT, $args );
      return $this->done();
      return;
    }

    if ( ! $this->inArray($args['order'],$allowed_orders) ) {
      $this->result->addError( __('order must be one of these:','woocommerce_json_api') . join( $allowed_orders, ','), WCAPI_BAD_ARGUMENT );
      return $this->done();
      return;
    }

    $args['hide_empty']           = $this->orEq( $params['arguments'], 'hide_empty', true);
    $include                      = $this->orEq( $params['arguments'], 'include', false);
    if ( $include ) {
      $args['include'] = $include;
    }
    $number                       = $this->orEq( $params['arguments'], 'per_page', false);
    if ( $number ) {
      $args['number'] = $number;
    }
    $like                         = $this->orEq( $params['arguments'], 'like', false);
    if ( $like ) {
      $args['name__like'] = $like;
    }
    $tags = get_terms('product_tag', $args);
    $this->result->setPayload($tags);
    return $this->done();
  }
  public function get_customers( $params ) {
    global $wpdb;
    $posts_per_page = $this->orEq( $params['arguments'], 'per_page', 15 ); 
    $paged          = $this->orEq( $params['arguments'], 'page', 0 );
    $ids            = $this->orEq( $params['arguments'], 'ids', false);

    if ( ! is_numeric($paged) ) {
      $this->badArgument('page',__('must be a number','woocommerce_json_api') );
      return $this->done();
    }
    if ( ! is_numeric($posts_per_page) ) {
      $this->badArgument('per_page',__('must be a number','woocommerce_json_api') );
      return $this->done();
    }
    if ( $paged > 1 ) {
      $page = $posts_per_page * $paged - 1;
    } else {
      $page = 0;
    }
    $sql = "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'paying_customer' AND meta_value = '1'";

    if ( $ids ) {
      if ( ! is_array($ids) ) {
        $this->badArgument('ids',__('must be an array of numbers','woocommerce_json_api') );
        return $this->done();
      } else {
        foreach ( $ids as &$id ) {
          $id = $wpdb->prepare("%s",$id);
        }
        $sql .= " WHERE user_id IN (" . join(',',$ids) . ")";
      }
    }
    $sql .= " LIMIT $page,$posts_per_page";
    $customer_ids = $wpdb->get_col($sql);
    $customers = array();
    foreach ( $customer_ids as $id ) {
      $c = API\Customer::find( $id );
      $customers[] = $c->asApiArray();
    }
    $this->result->setPayload($customers);
    return $this->done();
  }

  public function get_orders( $params ) {
    $posts_per_page = $this->orEq( $params['arguments'], 'per_page', 15 ); 
    $paged          = $this->orEq( $params['arguments'], 'page', 0 );
    $ids            = $this->orEq( $params['arguments'], 'ids', false);

    if ( ! $ids ) {
      $orders = array();
      $models = API\Order::all("*")->per($posts_per_page)->page($paged)->fetch();
      foreach ( $models as $model ) {
        $orders[] = $model->asApiArray();
      }
    } else if ( $ids ) {
    
      $posts = $ids;
      $orders = array();
      foreach ( $posts as $post_id) {
        try {
          $post = API\Order::find($post_id);
        } catch (Exception $e) {
          JSONAPIHelpers::error("An exception occurred attempting to instantiate a Order object: " . $e->getMessage());
          $this->result->addError( __("Error occurred instantiating Order object"),-99);
          return $this->done();
        }
        
        if ( !$post ) {
          $this->result->addWarning( $post_id. ': ' . __('Order does not exist','woocommerce_json_api'), WCAPI_ORDER_NOT_EXISTS, array( 'id' => $post_id) );
        } else {
          $orders[] = $post->asApiArray();
        }
        
      }
    }
    
    $this->result->setPayload($orders);
    return $this->done();
  }

  public function set_orders( $params ) {
    $payload = $this->orEq( $params,'payload', false);
    if ( ! $payload || ! is_array($payload)) {
      $this->result->addError( __('Missing payload','woocommerce_json_api'), WCAPI_BAD_ARGUMENT );
      return $this->done();
    }
    $orders = array();
    foreach ( $payload as $order ) {
      if ( isset( $order['id'] ) ) {
        $model = API\Order::find( $order['id'] );
        $model->fromApiArray($order);
        $model->update();
        $orders[] = $model->asApiArray();
      }
    }
    $this->result->setPayload($orders);
    return $this->done();
  }

  public function get_store_settings( $params ) {
    global $wpdb;
    $filter = $this->orEq( $params['arguments'],'filter', '');
    $filter = $wpdb->prepare("%s",$filter);
    $filter = substr($filter, 1,strlen($filter) - 2);
    $sql = "SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'woocommerce_{$filter}%' AND LENGTH(option_value) < 1024";
    $results = $wpdb->get_results( $sql, 'ARRAY_A');
    $payload = array();
    foreach ( $results as $result ) {
      $key = str_replace('woocommerce_','',$result['option_name']);
      $payload[$key] = maybe_unserialize( $result['option_value']);
    }
    $this->result->setPayload( $payload );
    return $this->done();
  }
  public function set_store_settings( $params ) {
    global $wpdb;
    $filter = $this->orEq( $params['arguments'],'filter', '');
    $payload = $this->orEq( $params,'payload', false);
    if ( ! $payload || ! is_array($payload)) {
      $this->result->addError( __('Missing payload','woocommerce_json_api'), WCAPI_BAD_ARGUMENT );
      return $this->done();
    }
    $filter = $wpdb->prepare("%s",$filter);
    $filter = substr($filter, 1,strlen($filter) - 2);
    $sql = "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'woocommerce_{$filter}%' AND LENGTH(option_value) < 1024";
    $results = $wpdb->get_results( $sql, 'ARRAY_A');
    $new_settings = array();
    $meta_sql = "
        UPDATE {$wpdb->options}
          SET `option_value` = CASE `option_name`
            ";
    $option_keys = array();
    foreach ( $results as $result ) {
      $key = str_replace('woocommerce_','',$result['option_name']);
      if ( isset( $payload[$key])) {
       //$option_keys[] = $wpdb->prepare("%s",$result['option_name']);
        $meta_sql .= $wpdb->prepare( "\t\tWHEN '{$result['option_name']}' THEN %s\n ", $payload[$key]);
      }
    }
    $meta_sql .= "
        ELSE `option_value`
        END
    ";
    $wpdb->query($meta_sql);
    return $this->get_store_settings( $params );
  }

  public function get_site_settings( $params ) {
    global $wpdb;
    $filter = $this->orEq( $params['arguments'],'filter', '');
    $filter = $wpdb->prepare("%s",$filter);
    $filter = substr($filter, 1,strlen($filter) - 2);
    if ( strlen($filter) > 1) {
      $sql = "SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE '{$filter}%' AND LENGTH(option_value) < 1024";
    } else {
      $sql = "SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE '{$filter}%' AND option_name NOT LIKE 'woocommerce_%' AND LENGTH(option_value) < 1024";
    }
    
    $results = $wpdb->get_results( $sql, 'ARRAY_A');
    $payload = array();
    foreach ( $results as $result ) {
      $key = $result['option_name'];
      $payload[$key] = maybe_unserialize( $result['option_value']);
    }
    $this->result->setPayload( $payload );
    return $this->done();
  }
  public function set_site_settings( $params ) {
    global $wpdb;
    $filter = $this->orEq( $params['arguments'],'filter', '');
    $payload = $this->orEq( $params,'payload', false);
    if ( ! $payload || ! is_array($payload)) {
      $this->result->addError( __('Missing payload','woocommerce_json_api'), WCAPI_BAD_ARGUMENT );
      return $this->done();
    }
    $filter = $wpdb->prepare("%s",$filter);
    $filter = substr($filter, 1,strlen($filter) - 2);
    if ( strlen($filter) > 1) {
      $sql = "SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE '{$filter}%' AND LENGTH(option_value) < 1024";
    } else {
      $sql = "SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE '{$filter}%' AND option_name NOT LIKE 'woocommerce_%' AND LENGTH(option_value) < 1024";
    }
    $results = $wpdb->get_results( $sql, 'ARRAY_A');
    $new_settings = array();
    $meta_sql = "
        UPDATE {$wpdb->options}
          SET `option_value` = CASE `option_name`
            ";
    $option_keys = array();
    foreach ( $results as $result ) {
      $key = $result['option_name'];
      if ( isset( $payload[$key] ) ) {
       //$option_keys[] = $wpdb->prepare("%s",$result['option_name']);
        $meta_sql .= $wpdb->prepare( "\t\tWHEN '{$result['option_name']}' THEN %s\n ", $payload[$key]);
      }
    }
    $meta_sql .= "
        ELSE `option_value`
        END
    ";
    $wpdb->query($meta_sql);
    return $this->get_store_settings( $params );
  }
  public function get_api_methods( $params ) {
    $m = self::getImplementedMethods();
    $this->result->setPayload($m);
    return $this->done();
  }

  public function get_coupons( $params ) {
    global $wpdb;
    $allowed_order_bys = array('ID','post_title','post_date','post_author','post_modified');
    /**
    *  Read this section to get familiar with the arguments of this method.
    */
    $posts_per_page = $this->orEq( $params['arguments'], 'per_page', 15 ); 
    $paged          = $this->orEq( $params['arguments'], 'page', 0 );
    $order_by       = $this->orEq( $params['arguments'], 'order_by', 'ID');
    $order          = $this->orEq( $params['arguments'], 'order', 'ASC');
    $ids            = $this->orEq( $params['arguments'], 'ids', false);
    $parent_ids     = $this->orEq( $params['arguments'], 'parent_ids', false);
    $skus           = $this->orEq( $params['arguments'], 'skus', false);
    
    $by_ids = true;
    if ( ! $this->inArray($order_by,$allowed_order_bys) ) {
      $this->result->addError( __('order_by must be one of these:','woocommerce_json_api') . join( $allowed_order_bys, ','), WCAPI_BAD_ARGUMENT );
      return $this->done();
      return;
    }
    if ( ! $ids && ! $skus ) {
      $products = API\Coupon::all('*')->per($posts_per_page)->page($paged)->fetch(function ( $result) {
        $model = new API\Coupon();
        $model->fromDatabaseResult($result);
        return $model->asApiArray();
      });
      $this->result->setPayload($products);

      return $this->done();

    } else if ( $ids ) {
    
      $posts = $ids;
      
    } else if ( $skus ) {
    
      $coupons = array();
      foreach ($skus as $sku) {
        $coupon = API\Coupon::find_by_sku($sku);
        if ( ! $coupon ) {
          $this->result->addWarning( $sku . ': ' . __('Coupon does not exist','woocommerce_json_api'), WCAPI_PRODUCT_NOT_EXISTS, array( 'sku' => $sku) );
        } else {
          $coupons[] = $coupon;
        }
      }
      $this->result->setPayload($products);

      return $this->done();

    }

    $coupons = array();
    foreach ( $posts as $post_id) {

        $post = API\Coupon::find($post_id);

      
      if ( !$post ) {
        $this->result->addWarning( $post_id. ': ' . __('Coupon does not exist','woocommerce_json_api'), WCAPI_PRODUCT_NOT_EXISTS, array( 'id' => $post_id) );
      } else {

        $coupons[] = $post->asApiArray();
      }
      
    }
    // We manage the array ourselves, so call setPayload, instead of addPayload
    $this->result->setPayload($coupons);

    return $this->done();
  }
   public function get_images( $params ) {
    $allowed_order_bys = array('ID','post_title','post_date','post_author','post_modified');
    $posts_per_page = $this->orEq( $params['arguments'], 'per_page', 15 ); 
    $paged          = $this->orEq( $params['arguments'], 'page', 0 );
    $order_by       = $this->orEq( $params['arguments'], 'order_by', 'ID');
    $order          = $this->orEq( $params['arguments'], 'order', 'ASC');
    $ids            = $this->orEq( $params['arguments'], 'ids', false);
    $ids            = $this->orEq( $params['arguments'], 'ids', false);
    $parent_ids     = $this->orEq( $params['arguments'], 'parent_ids', false);
    $by_ids = true;
    if ( ! $this->inArray($order_by,$allowed_order_bys) ) {
      $this->result->addError( __('order_by must be one of these:','woocommerce_json_api') . join( $allowed_order_bys, ','), WCAPI_BAD_ARGUMENT );
      return $this->done();
      return;
    }
    if ( ! $ids && ! $skus ) {
        if ($parent_ids) {
          $images = API\Image::all('*', "`post_parent` IN (" . join(",",$parent_ids) . ")")->per($posts_per_page)->page($paged)->fetch(function ( $result) {
            $model = new API\Image();
            $model->fromDatabaseResult($result);
            return $model->asApiArray();
          });
        } else {
          $images = API\Image::all('*')->per($posts_per_page)->page($paged)->fetch(function ( $result) {
            $model = new API\Image();
            $model->fromDatabaseResult($result);
            return $model->asApiArray();
          });
          $this->result->setPayload($images);
        }

      return $this->done();

    } else if ( $ids ) {
    
      $posts = $ids;
      
    } 

    $images = array();
    foreach ( $posts as $post_id) {

        $post = API\Image::find($post_id);

      
      if ( !$post ) {
        $this->result->addWarning( $post_id. ': ' . __('Image does not exist','woocommerce_json_api'), WCAPI_PRODUCT_NOT_EXISTS, array( 'id' => $post_id) );
      } else {

        $images[] = $post->asApiArray();
      }
      
    }
    // We manage the array ourselves, so call setPayload, instead of addPayload
    $this->result->setPayload($images);

    return $this->done();
  }
}