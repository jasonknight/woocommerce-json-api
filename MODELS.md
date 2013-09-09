I am writing this page while actually adding a new feature to the API, this will be particularly in depth.

The first step is to open up **classes/class-wc-json-api.php** and find the function called `public static function getImplementedMethods()`, it will look similar to this:

```php
<?php
public static function getImplementedMethods() {
    if (self::$implemented_methods) {
      return self::$implemented_methods;
    }
    self::$implemented_methods = array(
      'get_system_time',
      'get_products',
      'get_categories',
      'get_taxes',
      'get_shipping_methods',
      'get_payment_gateways',
      'get_tags',
      'get_products_by_tags',
      'get_customers',
      
      // Write capable methods
      
      'set_products',
      'set_categories',

    );
    return self::$implemented_methods;
  }
```

In this case, we will add the method "get_orders". All methods are named get|set_{resource}s. Since the API thinks **ONLY IN COLLECTIONS.**

We will modify it like so:

```php
<?php
  public static function getImplementedMethods() {
    if (self::$implemented_methods) {
      return self::$implemented_methods;
    }
    self::$implemented_methods = array(
      'get_system_time',
      'get_products',
      'get_categories',
      'get_taxes',
      'get_shipping_methods',
      'get_payment_gateways',
      'get_tags',
      'get_products_by_tags',
      'get_customers',
      'get_orders', // New Method
      
      // Write capable methods
      
      'set_products',
      'set_categories',

    );
    return self::$implemented_methods;
  }
```

Here we have added the new method **get_orders.** We will need to scroll to the bottom of the file and add a method like so:

```php
<?php
public function get_customers( $params ) {
    global $wpdb;
    
    blah blah blah
    $this->result->setPayload($customers);
    return $this->done();
  }

  private function get_orders( $params ) {
    $posts_per_page = $this->helpers->orEq( $params['arguments'], 'per_page', 15 ); 
    $paged          = $this->helpers->orEq( $params['arguments'], 'page', 0 );
    $ids            = $this->helpers->orEq( $params['arguments'], 'ids', false);

    ... Your code will be here ...

    $this->result->setPayload( $orders );
    return $this->done();
  }
```

In this case, we added **get_orders** right after the function **get_customers**. Normally, we group functions of like kind together. So new methods for customers would be inserted after **get_customers** and before **get_orders.**

_Always end the function with `return $this->done();`_ This is a special method that takes care of preparing our results and returning them based on the configuration.

This is really all that is API specific. At this point, you can use any code you want to populate the `$orders` collection. It can be an array of arrays, or an array of objects, or models, or even string representations (Like CSV lines perhaps?) You are not limited in any way.

In this case, we will get a bit complicated. We will create an order model to help us out.

### The model structure has been made much easier since this was written. 

Please take a look at the files to see how things are being done. While it is still "true" much of this has been made unnecessary and has been abstracted out to the base model.

Models inherit from `JSONAPIBaseRecord` class. This class is a kind of ActiveRecordy implementation, though nowhere near as complete and complex.

I won't cover the full base class implementation here, but just show you what you need to write to be up and running.

```php
<?php
require_once(dirname(__FILE__) . "/class-rede-base-record.php");
class WC_JSON_API_Order extends JSONAPIBaseRecord {

  
  public $_status;
  
}
```

In this case $_status is facilitated by the Wordpress Terms API (Why I honestly don't know, but there must be some reason.). Since it requires
a circuitous route to and from the value for each "record" we will need to create our own getters/setters for this value.

```php
<?php
require_once(dirname(__FILE__) . "/class-rede-base-record.php");
class WC_JSON_API_Order extends JSONAPIBaseRecord {

  
  public $_status;

  public function getStatus() {
    if ( $this->_status ) {
      return $this->_status;
    }
    $terms = wp_get_object_terms( $this->id, 'shop_order_status', array('fields' => 'slugs') );
    $this->_status = (isset($terms[0])) ? $terms[0] : 'pending';
    return $this->_status;
  }

  public function setStatus( $s ) {
    $this->_status = $s;
  } 

}
```

Now, we need to tell the BaseRecord some information about this "record" or "resource" and how it is stored.

This is handled by two functions, one describes the Model attributes, or the main attributes on whatever table
is holding them, and the second describes the "meta" attributes. These are usually found on the postmeta or usermeta
tables, in the case of order->status they will be gotten through the getters/setters above defined.

```php
<?php
require_once(dirname(__FILE__) . "/class-rede-base-record.php");
class WC_JSON_API_Order extends JSONAPIBaseRecord {

  
  public $_status;

  public function getStatus() {
    if ( $this->_status ) {
      return $this->_status;
    }
    $terms = wp_get_object_terms( $this->id, 'shop_order_status', array('fields' => 'slugs') );
    $this->_status = (isset($terms[0])) ? $terms[0] : 'pending';
    return $this->_status;
  }

  public function setStatus( $s ) {
    $this->_status = $s;
  } 
  public static function setupModelAttributes() {
    static::$_model_settings = array_merge( JSONAPIBaseRecord::getDefaultModelSettings(), array(
        'model_conditions' => "WHERE post_type IN ('shop_order')",
        'has_many' => array(
          'order_items' => array('class_name' => 'WC_JSON_API_OrderItem', 'foreign_key' => 'order_id'),
        ),
      ) 
    );

    static::$_model_attributes_table = array(
      'name'            => array('name' => 'post_title',  'type' => 'string'),
      'guid'            => array('name' => 'guid',        'type' => 'string'),

    );
    static::$_model_attributes_table = apply_filters( 'woocommerce_json_api_order_model_attributes_table', static::$_model_attributes_table );
  }
}
```

This function is automatically called before using the Model. While the internal bits of the Arrays can be changed, it must
be input in more or less this fashion, with the variable names as described.

This following bit of code is important:

```php
<?php
    // depends on late static binding.
    // Notice the call to array_merge( JSONAPIBaseRecord::getDefaultModelSettings(), array(...) )
    // The second array is where we put overrides. Because most CPTs are on the posts table, so
    // unless the Model deviates from this, you don't have to put in already known information.
    static::$_model_settings = array_merge( JSONAPIBaseRecord::getDefaultModelSettings(), array(
        'model_conditions' => "WHERE post_type IN ('shop_order')",
        'has_many' => array( // We have many order items, this class we will build in a second.
          'order_items' => array('class_name' => 'WC_JSON_API_OrderItem', 'foreign_key' => 'order_id'),
        ),
      ) 
    );
```

Next, we need to define the MetaAttributes of the model

```php
<?php
require_once(dirname(__FILE__) . "/class-rede-base-record.php");
class WC_JSON_API_Order extends JSONAPIBaseRecord {

  
  public $_status;

  public function getStatus() {
    if ( $this->_status ) {
      return $this->_status;
    }
    $terms = wp_get_object_terms( $this->id, 'shop_order_status', array('fields' => 'slugs') );
    $this->_status = (isset($terms[0])) ? $terms[0] : 'pending';
    return $this->_status;
  }

  public function setStatus( $s ) {
    $this->_status = $s;
  } 
  public static function setupModelAttributes() {
    static::$_model_settings = array_merge( JSONAPIBaseRecord::getDefaultModelSettings(), array(
        'model_conditions' => "WHERE post_type IN ('shop_order')",
        'has_many' => array(
          'order_items' => array('class_name' => 'WC_JSON_API_OrderItem', 'foreign_key' => 'order_id'),
        ),
      ) 
    );

    static::$_model_attributes_table = array(
      'name'            => array('name' => 'post_title',  'type' => 'string'),
      'guid'            => array('name' => 'guid',        'type' => 'string'),

    );
    static::$_model_attributes_table = apply_filters( 'woocommerce_json_api_order_model_attributes_table', static::$_model_attributes_table );
  }
  public static function setupMetaAttributes() {
    // We only accept these attributes.
    static::$_meta_attributes_table = array(
      'order_key'             => array('name' => '_order_key',                  'type' => 'string'), 
      'billing_first_name'    => array('name' => '_billing_first_name',         'type' => 'string'), 
      'billing_last_name'     => array('name' => '_billing_last_name',          'type' => 'string'), 
      'billing_company'       => array('name' => '_billing_company' ,           'type' => 'string'), 
      'billing_address_1'     => array('name' => '_billing_address_1',          'type' => 'string'), 
      'billing_address_2'     => array('name' => '_billing_address_2',          'type' => 'string'), 
      'billing_city'          => array('name' => '_billing_city',               'type' => 'string'), 
      'billing_postcode'      => array('name' => '_billing_postcode',           'type' => 'string'), 
      'billing_country'       => array('name' => '_billing_country',            'type' => 'string'), 
      'billing_state'         => array('name' => '_billing_state',              'type' => 'string'), 
      'billing_email'         => array('name' => '_billing_email',              'type' => 'string'), 
      'billing_phone'         => array('name' => '_billing_phone',              'type' => 'string'), 
      'shipping_first_name'   => array('name' => '_shipping_first_name',        'type' => 'string'), 
      'shipping_last_name'    => array('name' => '_shipping_last_name' ,        'type' => 'string'), 
      'shipping_company'      => array('name' => '_shipping_company',           'type' => 'string'), 
      'shipping_address_1'    => array('name' => '_shipping_address_1' ,        'type' => 'string'), 
      'shipping_address_2'    => array('name' => '_shipping_address_2',         'type' => 'string'), 
      'shipping_city'         => array('name' => '_shipping_city',              'type' => 'string'), 
      'shipping_postcode'     => array('name' => '_shipping_postcode',          'type' => 'string'), 
      'shipping_country'      => array('name' => '_shipping_country',           'type' => 'string'), 
      'shipping_state'        => array('name' => '_shipping_state',             'type' => 'string'), 
      'shipping_method'       => array('name' => '_shipping_method' ,           'type' => 'string'), 
      'shipping_method_title' => array('name' => '_shipping_method_title',      'type' => 'string'), 
      'payment_method'        => array('name' => '_payment_method',             'type' => 'string'), 
      'payment_method_title'  => array('name' => '_payment_method_title',       'type' => 'string'), 
      'order_discount'        => array('name' => '_order_discount',             'type' => 'number'), 
      'cart_discount'         => array('name' => '_cart_discount',              'type' => 'number'), 
      'order_tax'             => array('name' => '_order_tax' ,                 'type' => 'number'), 
      'order_shipping'        => array('name' => '_order_shipping' ,            'type' => 'number'), 
      'order_shipping_tax'    => array('name' => '_order_shipping_tax' ,        'type' => 'number'), 
      'order_total'           => array('name' => '_order_total',                'type' => 'number'), 
      'customer_user'         => array('name' => '_customer_user',              'type' => 'number'), 
      'completed_date'        => array('name' => '_completed_date',             'type' => 'datetime'), 
      'status'                => array(
                                        'name' => 'status', 
                                        'type' => 'string', 
                                        'getter' => 'getStatus',
                                        'setter' => 'setStatus',
                                        'updater' => 'updateStatus'
                                ),
      
    );
    /*
      With this filter, plugins can extend this ones handling of meta attributes for a product,
      this helps to facilitate interoperability with other plugins that may be making arcane
      magic with a product, or want to expose their product extensions via the api.
    */
    static::$_meta_attributes_table = apply_filters( 'woocommerce_json_api_order_meta_attributes_table', static::$_meta_attributes_table );
  } // end setupMetaAttributes
}
```

The same rules as setupModelAttributes apply here. It needs to be in much the same form, however we don't describe the model
at this point. A point of interest in the 'status' attribute, where we define the getter/setter as well as the updater which
will be defined later for when we allow the model to be saved/created.

The last necessary function is 

```php
<?php
  public function asApiArray() {
    $attrs = parent::asApiArray();
    $attrs['order_items'] = $this->order_items;
    return $attrs;
  }
```

This function is optional, and you will notice the call to `parent::asApiArray()` which is a generic merging of the meta and model
attributes. Here we need to handle the associations separately.


Now let's look at the class `WC_JSON_API_OrderItem`

```php
<?php
require_once(dirname(__FILE__) . "/class-rede-base-record.php");
require_once(dirname(__FILE__) . "/class-wc-json-api-order.php");
class WC_JSON_API_OrderItem extends JSONAPIBaseRecord {

  public static function setupMetaAttributes() {
    // We only accept these attributes.
    static::$_meta_attributes_table = array(
      'quantity'          => array('name' => '_qty',           'type' => 'number'), 
      'tax_class'         => array('name' => '_tax_class',    'type' => 'number'), 
      'product_id'        => array('name' => '_product_id',    'type' => 'number'), 
      'variation_id'      => array('name' => '_variation_id',    'type' => 'number'), 
      'subtotal'          => array('name' => '_line_subtotal',    'type' => 'number'),
      'total'             => array('name' => '_line_total',    'type' => 'number'),  
      'tax'               => array('name' => '_line_tax',    'type' => 'number'),  
      'subtotal_tax'      => array('name' => '_line_subtotal_tax',    'type' => 'number'), 
      // if this is a tax li, then there will be these fields...what a mess.
      'rate_id'           => array('name' => 'rate_id',     'type' => 'number'), 
      'label'             => array('name' => 'label',       'type' => 'number'), 
      'compound'          => array('name' => 'compound',    'type' => 'number'), 
      'tax_amount'        => array('name' => 'tax_amount',  'type' => 'number'), 
      'shipping_tax'      => array('name' => 'shipping_tax','type' => 'number'), 

    );
    static::$_meta_attributes_table = apply_filters( 'woocommerce_json_api_order_item_meta_attributes_table', static::$_meta_attributes_table );
  } // end setupMetaAttributes
  public static function setupModelAttributes() {
    global $wpdb;
    static::$_model_settings = array_merge(JSONAPIBaseRecord::getDefaultModelSettings(), array(
      'model_table'                => $wpdb->prefix . 'woocommerce_order_items',
      'meta_table'                => $wpdb->prefix . 'woocommerce_order_itemmeta',
      'model_table_id'             => 'order_item_id',
      'meta_table_foreign_key'    => 'order_item_id',
      'meta_function' => 'woocommerce_get_order_item_meta',
      'belongs_to' => array(
        'order' => array('class_name' => 'WC_JSON_API_Order', 'foreign_key' => 'order_id'),
        'product' => array('class_name' => 'WC_JSON_API_Product', 'meta_attribute' => 'product_id'),
      ),
    ) );
    static::$_model_attributes_table = array(
      'name'            => array('name' => 'order_item_name',  'type' => 'string'),
      'type'            => array('name' => 'order_item_type',  'type' => 'string'),
      'order_id'            => array('name' => 'order_id',     'type' => 'number'),

    );
    static::$_model_attributes_table = apply_filters( 'woocommerce_json_api_order_item_model_attributes_table', static::$_model_attributes_table );
  }
}
```

Considering, that's pretty short. We didn't setup any getters/setters, or need to override the default `asApiArray()`

All in all, it's pretty easy to create new models in the API, mostly it's just copy and paste from existing models,
and tweaking a few methods here and there, or some settings etc.

With this setup, we can use code like this:

```php
<?php
  $order = WC_JSON_APIOrder::find(1);
  $order_items = $order->order_items;
  foreach ( $order_items as $oi ) {
    $product = $oi->product;
    ...
  }
```

In the end, well worth the effort. We can also do this:

```php
<?php
  $product = WC_JSON_API_Product::find( 1 );
  $order_items = $product->order_items;
  foreach ( $order_items as $oi ) {
    $order = $oi->order;
    ...
  }
```

This let's us get quick access to information without having to know a whole lot about
the Wordpress API.

## Back to the API

Now that we have the models defined, we can fill in the code for our API function.

```php
<?php
  private function get_orders( $params ) {
    $posts_per_page = $this->orEq( $params['arguments'], 'per_page', 15 ); 
    $paged          = $this->orEq( $params['arguments'], 'page', 0 );
    $ids            = $this->orEq( $params['arguments'], 'ids', false);

    if ( ! $ids ) {
      $orders = array();
      $models = WC_JSON_API_Order::all("*")->per($posts_per_page)->page($paged)->fetch();
      foreach ( $models as $model ) {
        $orders[] = $model->asApiArray();
      }
    } else if ( $ids ) {
    
      $posts = $ids;
      $orders = array();
      foreach ( $posts as $post_id) {
        try {
          $post = WC_JSON_API_Order::find($post_id);
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
```

Of course, we have a bit of error handling code so that the API is more fault tolerant, but also produces some meaningful warnings
and errors when bad input is sent. Again most of this code is copy/pasted with a few bits changed.

I do have plans of working out a way to generalize even this out, making it dead simple to add to the API, and I would
even like to add the ability for other plugins to extend the API without having to muck with the code.

