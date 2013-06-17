<?php
/**
  A Product class to insulate the API from the details of the
  database representation
*/
require_once(dirname(__FILE__) . "/class-rede-base-record.php");
require_once(dirname(__FILE__) . "/class-wc-json-api-category.php");
class WC_JSON_API_Product extends RedEBaseRecord {
  // Products are split off into two
  // datasources, posteta, and the posts
  // themselves.
  private $_meta_attributes;
  // This is static because we really only need to
  // do this mapping once, not for each object we create.
  // We need to keep it small and fast
  public static $_meta_attributes_table; // a mapping of wich product attribs
                                   // go to the meta table
  private $_post_attributes;
  public static $_post_attributes_table;

  // A the id for the actual product, used for queries.
  private $_actual_product_id;

    
  /**
    Here we normalize the attributes, giving them a consistent name scheme and obvious
    meaning, as well as making them easier to type so that we have a nice, user
    friendly interface into WooCom.
    
    We also need to be able to validate the inputs from outside, so we have to 
    attach information to each key about what it can contain, and how we should
    cast things to and from the DB.
    
    When we say bool, we mean a WP Bool, which is `yes` or `no`. I actually prefer this
    idea, because of the way PHP and many languages handle boolean values. It's just
    so much more clear.

    The fundamental idea for this class is that there doesn't seem to be a single entry
    point into and out of the database for WooCom which provides a mixture of classes
    and functions that get, process, display, and save products to the database and that
    depend on things like $_POST and various Defines. 

    We want to abstract away the naughty bits of the database representation of the product
    in question.
  */
  public static function setupMetaAttributes() {
    if ( self::$_meta_attributes_table ) {
      return;
    }
    // We only accept these attributes.
    self::$_meta_attributes_table = array(
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
      'download_paths'    => array('name' => '_file_paths',            'type' => 'array', 'filters' => array('woocommerce_file_download_paths') ),
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
                              'class' => 'WC_JSON_API_ProductAttributes',
                             ), 
      'tax_class'         => array('name' => '_tax_class',        'type' => 'string'),
      'tax_status'        => array(
                               'name' => '_height',           
                               'type' => 'string',
                               'values' => array(
                                'taxable',
                                'shipping',
                                'none',
                               ),
                             ),
    );
  } // end setupMetaAttributes
  
  public static function setupPostAttributes() {
    if ( self::$_post_attributes_table ) {
      return;
    }
    self::$_post_attributes_table = array(
      'name'            => array('name' => 'post_title',             'type' => 'string'),
      'slug'            => array('name' => 'post_name',              'type' => 'string'),
      'type'            => array('name' => 'post_type',              'type' => 'string'),
      'description'     => array('name' => 'post_content',           'type' => 'string'),
    );
  }
  
  public function asApiArray() {
    global $wpdb;
    $category_objs = woocommerce_get_product_terms($this->_actual_product_id, 'product_cat', 'all');
    $categories = array();

    foreach ( $category_objs as $cobj ) {
                      // This looks scary if you've never used Javascript () evaluates the
                      // the contents and returns the value, in the same way that (3+4) * 8 
                      // works. Because we define the class with a Fluid API, most functions
                      // that modify state of the object, return the object.
      $categories[] = (new WC_JSON_API_Category)->setCategory( $cobj )->asApiArray();
    }
    $attrs = array(
        'id'   => $this->getProductId(),
        'name' => $this->name,
        'description' => $this->description,
        'slug' => $this->slug,
        'permalink' => get_permalink( $this->_actual_product_id ),
        'price' => array( 
          'amount' => $this->price,
          'currency' => get_woocommerce_currency(),
          'symbol' => get_woocommerce_currency_symbol(),
          'taxable' => $this->taxable,
        ),
        'sku' => $this->sku,
        'stock' => array(
          'managed' => $this->manage_stock,
          'for_sale' => $this->quantity,
          'in_stock' => $this->stock,
          'downloadable' => $this->downloadable,
          'virtual' => $this->virtual,
          'sold_individually' => $this->sold_individually,
          'download_paths' => isset( $_meta_attributes['download_paths'] ) ? $this->download_paths : array(),
        ),
        'categories' => $categories,
      );
    return $attrs;
  }
  /**
    From here we have a dynamic getter. We return a special REDENOTSET variable.
  */
  public function __get( $name ) {
    if ( isset( self::$_meta_attributes_table[$name] ) ) {
      if ( isset ( $this->_meta_attributes[$name] ) ) {
        return $this->_meta_attributes[$name];
      } else {
        return REDENOTSET;
      }
    } else if ( isset( self::$_post_attributes_table[$name] ) ) {
      if ( isset( $this->_post_attributes[$name] ) ) {
        return $this->_post_attributes[$name];
      } else {
        return REDENOTSET;
      }
    }
  } // end __get
  
  // Dynamic setter
  public function __set( $name, $value ) {
    if ( isset( self::$_meta_attributes_table[$name] ) ) {
      $this->_meta_attributes[$name] = $value;
    } else if ( isset( self::$_post_attributes_table[$name] ) ) {
      $this->_post_attributes[$name] = $value;
    } else {
      throw new Exception( __('That attribute does not exist to be set.','woocommerce_json_api') . " `$name`");
    }
  } 
  public function setProductId( $id ) {
    $this->_actual_product_id = $id;
  }
  
  public function isValid() {
    return $this->_invalid;
  }
  public function getProductId() {
    return $this->_actual_product_id;
  }
  // How do we find Products?
  
  public static function find( $id ) {
    global $wpdb;
    self::setupPostAttributes();
    self::setupMetaAttributes();
    $product = new WC_JSON_API_Product();
    $product->setValid( false );
    $post = get_post( $id, 'ARRAY_A' );
    if ( $post ) {
      $product->setProductId( $id );
      foreach ( self::$_post_attributes_table as $name => $desc ) {
        $product->{$name} = $post[$desc['name']];
      }
      foreach ( self::$_meta_attributes_table as $name => $desc ) {
        $product->{$name} = get_post_meta( $id, $desc['name'], true );
        if ( $desc['type'] == 'array') {
          $product->{$name} = maybe_unserialize( $product->{$name} );
        }
        if ( isset($desc['filters']) ) {
          foreach ( $desc['filters'] as $filter ) {
            $product->{$name} = apply_filters($filter, $product->{$name}, $product->getProductId() );
          }
        }
      }
      $product->setValid( true );
      $product->setNewRecord( false );
    }
    return $product;
  }
  public function setNewRecord( $bool ) {
    $this->_new_record = $bool;
  }
  public function isNewRecord() {
    return $this->_new_record;
  }
  public static function find_by_sku( $sku ) {
    global $wpdb;
    $product = new WC_JSON_API_Product();
    $product->setValid( false );
    $pid = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1",$sku) );
    if ( $pid ) {
      $product = WC_JSON_API_Product::find( $pid );
    }
    return $product;
  }
  
  // How do we update products?
  public function update() {
    global $wpdb;
    $meta_sql = "
      UPDATE {$wpdb->postmeta}
        SET meta_value = CASE `meta_key`";
    foreach (self::$_meta_attributes_table as $attr => $desc) {
      if ( isset( $this->_meta_attributes[$attr] ) ) {
        $value = $this->_meta_attributes[$attr];
        $meta_sql .= $wpdb->prepare( "\tWHEN `{$desc['name']}` THEN %s\n ", $value);
      } 
    }
    $meta_sql .= "END 
    WHERE post_id = '{$this->_actual_product_id}';";
    $key = md5($meta_sql);
    $this->_queries_to_run[$key] = $meta_sql; 
    return $this;
  }
  
  public static function all($fields = 'id') {
    global $wpdb;
    $sql = "SELECT $fields from {$wpdb->posts} WHERE post_type IN ('product')";
    $product = new WC_JSON_API_Product();
    $product->addQuery($sql);
    return $product;
  }
  /**
    Sometimes we want to act directly on the result to be sent to the user.
    This allows us to add errors and warnings.
  */
  public function setResult ( $result ) {
    $this->_result = $result;
    return $this;
  }
  
  
}
