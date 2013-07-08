<?php

/**
  * Main API Router for plugin interface
  */ 
require_once( dirname( __FILE__ ) . '/class-wc-pos-helpers.php' );
class WC_POS_API {
  /*
    We don't define a constructor here, because
    we want to avoid implicit magic
  */
  
  
  // READMEAPI
  /*
    This is the central entry point to the entire
    API suite of functions. Here is where we clean,
    validate, and route queries to the internals.
    
  */
  public function route($params) {
    if (isset($params['proc'])) {
      if ($params['proc'] == "find_by_sku") {
       
        $this->find_by_sku($params);
        
      } else if ($params['proc'] == "find_current_order") {
      
        $this->find_current_order();
        
      }  else if ($params['proc'] == "set_product_quantity") {
      
        $this->set_product_quantity($params);
        
      }  else if ($params['proc'] == "add_to_cart") {
      
        $this->add_to_cart($params);
        
      } else if ($params['proc'] == "find_payment_methods") {
      
        $this->find_payment_methods($params);
        
      } else if ($params['proc'] == "find_shipping_methods") {
      
        $this->find_shipping_methods($params);
        
      } else if ($params['proc'] == "get_payment_method_form") {
      
        $this->get_payment_method_form($params);
        
      } else if ($params['proc'] == "retrieve_on_hold_orders") {
      
        $this->retrieve_on_hold_orders($params);
        
      } else if ($params['proc'] == "restore_cart") {
      
        $this->restore_cart($params);
        
      } else if ($params['proc'] == "render_order_html") {
      
        $this->render_order_html($params);
        
      } else if ($params['proc'] == "render_checkout_html") {
      
        $this->render_checkout_html($params);
        
      }
    }
  }
  // READMEAPI
  // Call this function to setup a new response
  private function create_new_result($action) {
    	if ( !defined( 'WOOCOMMERCE_CART' ) ) {
	  define('WOOCOMMERCE_CART', true);
	}
	$result = array();
	$result["action"] = $action;
	$result["errors"] = array();
	return $result;
  }
  // This is called at the end of every JSON request
  private function done($result) {
    header("Content-type: application/json");
    if ( !isset($result['status']) ) {
      $result['status'] = true; // i.e. default to true, makes it less to type up there
    }
    echo( json_encode($result) );
    die;
  }
  /**
    Given params such as: { sku: 12345 } return the product/variant that matches that SKU.
    The clerk isn't going to work with id's and neither should we, even if the system has
    the concept of products, variants, and options, we still need to latch onto something 
    that is functionally the equivalent of an SKU, i.e. Stock Keeping Unit, which should be
    Stock Keeping ID, because that's what it is.
    
    Whatever magic we have to perform to Drill down to an addable item is necessary, as it
    will make our job easier if all functions on the POS refer to items in a universal way, 
    but it is made all that much easier if both we and the users speak about items in the
    same way. 
    
    It may seem arbitrary, but it's one of those things that after some time you realize that
    this arbitrary and enforced way of doing it saves you so much time when talking to clients,
    when adding features. You just know how to get stuff out, and put stuff back in.
  */
  private function find_by_sku($params) {
	global $wpdb, $woocommerce;
	// We may want to do some filtering, some checking and so on
	// for instance with GS1 items, or dynamic item creation
	$sku = mysql_real_escape_string ( $params['sku'] );
	$result = $this->create_new_result("find_by_sku");
	$pid = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1",$sku) );
	if ($pid) {      
		$result["status"] = true;
		$result['payload'][] = $this->sane_product_attributes($pid);
	} else {
		$result["status"] = false;
		$result["errors"] = array("text" => __( 'No products found', 'wc_pos' ) );
	}
	$this->done($result);
  } // End find_by_sku
  private function find_current_order() {
    global $wpdb, $woocommerce;

    $result = $this->create_new_result("find_current_order");
    
    // MARK: I moved this here because the other function is cart agnostic
    // it could be the current cart, or it could be some other cart 
    // object, we don't know.

	  // Recalc cart totals so all numbers are up to date
	
	
	  $woocommerce->cart->calculate_totals();
	  $woocommerce->cart->get_cart();
	
	  $woocommerce->cart->get_cart_subtotal();
	  $woocommerce->cart->get_discounts_before_tax();
	  $woocommerce->cart->needs_shipping();
	  $woocommerce->cart->get_fees();
	  $woocommerce->cart->get_tax_totals();
	  $woocommerce->cart->get_discounts_after_tax();
	  // READMEWOOCOM
	  // We probably want to do this too no? Maybe people have some crazyness after totals are calced...
    do_action( 'woocommerce_cart_totals_after_order_total' );
    
    $result['payload'] = $this->sane_cart_attributes($woocommerce->cart);
    
    $this->done($result);
  }
  
  // We allow the UI to decide how quantity setting will work,
  // here we just thin wrap around the WC API
  private function set_product_quantity($params) {
    global $woocommerce;
    
	// Increase/Decrease/Remove can be controlled by the UI
	// Increase qty of any item already in the cart - referenced by using its item key set by WooCom
	if (0 == $params['quantity']) {
		$woocommerce->cart->set_quantity( $params['item_key'], $params['quantity'] );
	} else {

		$new_qty = $params['quantity'] - $params['old_quantity']; // will be negative if we are subtracting
		$this->wc_add_to_cart($params['product_id'],$new_qty,$params['variation_id']);
	}
		
	$this->find_current_order();
  }
  // Here is where we actually add the item to the cart
  private function add_to_cart($params) {
  
    if ( !isset( $params['variation_id'] ) ) 
	$params['variation_id'] = false;
	
    $this->wc_add_to_cart( $params['product_id'], $params['quantity'], $params['variation_id'] );
    $this->find_current_order(); // i.e. we just redirect internally to a different action
    
  }
  // Here we get a list of available payment methods
  private function find_payment_methods( $params ) {
    global $woocommerce;
    
    $result = $this->create_new_result("find_payment_methods");
    
    $gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
    $result['payload'] = $gateways;
    
    $this->done($result);
    
  }
  
  private function find_shipping_methods( $params ) {
    global $woocommerce,$wpdb;
    
    $result = $this->create_new_result("find_shipping_methods");
    
    $shipping_methods = $this->wc_get_available_shipping_methods();
    $result['payload'] = $shipping_methods;
    
    $this->done($result);
    
  }
  
  private function retrieve_on_hold_orders( $params ) {
    global $woocommerce,$wpdb;
    
    $result = $this->create_new_result("retrieve_on_hold_orders");
    $cart_sessions = get_option( '_wc_pos_save_carts', false );
		
		if ( !$cart_sessions )
			die( json_encode( array( 'result' => 'empty' ) ) );
		
		// Sort by key ( which is a Unix timestamp ) so we get a chronological order
		ksort( $cart_sessions );
		
		foreach( $cart_sessions as $key => $contents ) { 
			
			// Return a list of carts by date and item count and total including currency symbol
			$carts[ date( 'Y-m-d H:i:s', $key ) ] = array( 'key' => $key, 'count' => $contents['cart_contents_count'], 'total' => woocommerce_price( $contents['total'] ) );
		
		}
		$result['payload'] = $carts;

    $this->done($result);
    
  }
  
  private function restore_cart($params) {
    global $woocommerce,$wpdb,$user_ID;
    $cart_sessions = get_option( '_wc_pos_save_carts', false );
	$result = $this->create_new_result("restore_cart"); // just for error purposes
	if ( !$cart_sessions ) {
		$this->add_error($result,__("No such order on hold"));
		$this->done($result);
	}

	// Sort by key so we get a chronological order
	ksort( $cart_sessions );
	foreach( $cart_sessions as $key => $contents ) { 
	
		if ( $key == $params['key'] ) { 
			$woocommerce->session->cart = $contents['cart'];

			update_user_meta( $user_ID, '_woocommerce_persistent_cart', array(
				'cart' => $woocommerce->session->cart,
			) );
			$woocommerce->cart->get_cart_from_session();
			$this->find_current_order();

		}

	}
    
  }
  
  private function get_payment_method_form($params) {
	global $woocommerce;

	$result = $this->create_new_result("find_payment_methods");
	$gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
	$result['payload'] = '';
	foreach ($gateways as $payment_method) {
		if ($payment_method->id == $params['payment_method_id'] && $payment_method->has_fields()) {
			$result['payload'] = $payment_method->form_fields();
		}
	}

	$this->done($result);
  }

  private function render_order_html($params) {
    global $woocommerce;
    $result = $this->create_new_result("render_order_html");
    // This is defined in the class-wc-pos-helpers.php
    // it renders the template, and emits some filters that other
    // devs can latch onto
    $result['payload'] = (new WC_POS_Helpers())->render_template('pos_ui_mini_cart.php');
    $this->done($result);
  }
  private function render_checkout_html($params) {
    global $woocommerce;
    $result = $this->create_new_result("render_checkout_html");
    // This is defined in the class-wc-pos-helpers.php
    // it renders the template, and emits some filters that other
    // devs can latch onto
    $result['payload'] = (new WC_POS_Helpers())->render_template('pos_ui_checkout.php');
    $this->done($result);
  }
/**
  Generic API Functions follow, these are used within the above "callbacks" to make things
  a bit easier and cut down on repetitive code.
*/  
   
  // OMFG Becky! The attrs on this stuff is like totally INSANE.
  // this method combines all the relevant info for a product into an
  // easy to navigate structure so we can expose it to JS and hide
  // some of the painful details of woo
  private function sane_product_attributes($pid) {
    $attrs = array();
    $product = get_product($pid);
    $meta = get_post_custom( $pid, true );
    
    
    if ($product->is_type("variable")) {
      $variations = $product->get_available_variations();
    }
    
    
    foreach($product->post as $k=>$v) {
      $k = str_replace("post_","",$k);
      $attrs[strtolower($k)] = $v;
    }
    
    foreach($meta as $k=>$v) {
      $k = str_replace("_","",$k);
      $attrs[$k] = $v[0];
    }  
    
    return $attrs;
  } //  sane_product_attributes
  
  private function sane_cart_attributes($cart) {
    global $wpdb;
    $products = array();
    $new_cart = array();
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
      $_product = $cart_item['data'];
      // Only display if allowed
      if ( 
            ! apply_filters('woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) || 
            ! $_product->exists() || 
            $cart_item['quantity'] == 0 
         ) {
				continue;
		  }
		  $product_price = get_option( 'woocommerce_tax_display_cart' ) == 'excl' ? $_product->get_price_excluding_tax() : $_product->get_price_including_tax();
	    $quantity = apply_filters( 'woocommerce_widget_cart_item_quantity', $cart_item['quantity'], $cart_item, $cart_item_key );
	    $product_name = apply_filters('woocommerce_widget_cart_product_title', $_product->get_title(), $_product );
	    $product_sku = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_sku' AND post_id='%d' LIMIT 1",$_product->id) );
			$products[] = array(
			  'name' => $product_name,
			  'title' => $product_name,
			  'price' => $product_price,
			  'id'    => $_product->id,
			  'variation_id' => (isset( $_product->variation_id ) ? $_product->variation_id : ''),
			  'quantity' => $quantity,
			  'total' => $quantity * $product_price,
			  'key' => $cart_item_key,
			  'sku' => $product_sku
			);
    }
    /*foreach ($cart->cart_contents as $k=>$p) {
      $product = $this->sane_product_attributes($p["product_id"]);
      $attribs_we_want = explode(",","quantity,variation,variation_id,line_total,line_tax,line_subtotal,line_subtotal_tax");
      foreach ($attribs_we_want as $a) {
        $product[$a] = $p[$a]; 
      }
      $product["total"] = $product["quantity"] * $product["price"];
      $product["key"] = $k;
      $products[] = $product;
    }*/
    
    
    $attribs_we_want = explode(",","applied_coupons,coupon_discount_amounts,total,subtotal,tax_total");
    foreach ($attribs_we_want as $a) {
      $new_cart[$a] = $cart->{$a}; // dynamic method call
    }
     
    $new_cart["total"] = $cart->total;
    $new_cart['formatted_total'] = $cart->get_total();
    $new_cart["products"] = $products;
    return $new_cart;
  } // sane_cart_attributes
  
  // taken directly from class-wc-shipping
  private function wc_get_available_shipping_methods() {
    global $woocommerce;
    // Loop packages and merge rates to get a total for each shipping method
    
		$available_methods = array();
		foreach ( $woocommerce->shipping->packages as $package ) {
			if ( ! $package['rates'] ) continue;

			foreach ( $package['rates'] as $id => $rate ) {

				if ( isset( $available_methods[$id] ) ) {
					// Merge cost and taxes - label and ID will be the same
					$available_methods[$id]->cost += $rate->cost;

					foreach ( array_keys( $available_methods[$id]->taxes + $rate->taxes ) as $key ) {
					    $available_methods[$id]->taxes[$key] = ( isset( $rate->taxes[$key] ) ? $rate->taxes[$key] : 0 ) + ( isset( $available_methods[$id]->taxes[$key] ) ? $available_methods[$id]->taxes[$key] : 0 );
					}
				} else {
					$available_methods[$id] = $rate;
				}

			}

		}
		return $available_methods;
  }
  
  
  // Add item to cart
  // Clone of WooCom add_to_cart() method except that it accepts a decrease in quantity since the native WooCom method doesn't
  private function wc_add_to_cart(  $product_id, $quantity = 1, $variation_id = '', $variation = '', $cart_item_data = array() ) {
		global $woocommerce;
		// Load cart item data - may be added by other plugins
		$cart_item_data = (array) apply_filters( 'woocommerce_add_cart_item_data', $cart_item_data, $product_id, $variation_id );

		// Generate a ID based on product ID, variation ID, variation data, and other cart item data
		$cart_id = $woocommerce->cart->generate_cart_id( $product_id, $variation_id, $variation, $cart_item_data );

		// See if this product and its options is already in the cart
		$cart_item_key = $woocommerce->cart->find_product_in_cart( $cart_id );

		// Call the WooCom get_product() function which triggers a cascade of functions to use the correct class method of
		// loading a product based on its type ( simple / variable / etc )
		$product_data = get_product( $variation_id ? $variation_id : $product_id );

		if ( ! $product_data )
			return false;

		// Force quantity to 1 if sold individually
		if ( $product_data->is_sold_individually() )
			$quantity = 1;

		// Check product is_purchasable
		if ( ! $product_data->is_purchasable() ) {
		
			$woocommerce->add_error( sprintf( __( 'Sorry, &quot;%s&quot; cannot be purchased.', 'woocommerce' ), $product_data->get_title() ) );
			
			$woocommerce->show_messages();
			
			return false;
		}

		// Stock check - only check if we're managing stock and backorders are not allowed
		if ( ! $product_data->is_in_stock() ) {

			// Add an error using the error tracking native to WooCom
			$woocommerce->add_error( sprintf( __( 'You cannot add &quot;%s&quot; to the cart because the product is out of stock.', 'woocommerce' ), $product_data->get_title() ) );
			
			$woocommerce->show_messages();

			return false;
			
		} elseif ( ! $product_data->has_enough_stock( $quantity ) ) {

			$woocommerce->add_error( sprintf(__( 'You cannot add that amount of &quot;%s&quot; to the cart because there is not enough stock (%s remaining).', 'woocommerce' ), $product_data->get_title(), $product_data->get_stock_quantity() ));
			
			$woocommerce->show_messages();

			return false;

		}

		// Downloadable/virtual qty check
		if ( $product_data->is_sold_individually() ) {
		
			$in_cart_quantity = $cart_item_key ? $woocommerce->cart->cart_contents[$cart_item_key]['quantity'] : 0;

			// If its greater than 0, its already in the cart
			if ( $in_cart_quantity > 0 ) {
				$woocommerce->add_error( sprintf('%s',  __( 'You already have this item in your cart.', 'woocommerce' ) ) );
				
				$woocommerce->show_messages();
				
				return false;
			}
		}

		// Stock check - this time accounting for whats already in-cart
		$product_qty_in_cart = $woocommerce->cart->get_cart_item_quantities();

		if ( $product_data->managing_stock() ) {

			// Variations
			if ( $variation_id && $product_data->variation_has_stock ) {

				if ( isset( $product_qty_in_cart[ $variation_id ] ) && ! $product_data->has_enough_stock( $product_qty_in_cart[ $variation_id ] + $quantity ) ) {
				
					$woocommerce->add_error( sprintf(__( 'You cannot add that amount to the cart &mdash; we have %s in stock and you already have %s in your cart.', 'woocommerce' ), $product_data->get_stock_quantity(), $product_qty_in_cart[ $variation_id ] ));
					
					$woocommerce->show_messages();
					
					return false;
				}

			// Products
			} else {

				if ( isset( $product_qty_in_cart[ $product_id ] ) && ! $product_data->has_enough_stock( $product_qty_in_cart[ $product_id ] + $quantity ) ) {
				
					$woocommerce->add_error( sprintf(__( 'You cannot add that amount to the cart &mdash; we have %s in stock and you already have %s in your cart.', 'woocommerce' ), $product_data->get_stock_quantity(), $product_qty_in_cart[ $product_id ] ));
					
					$woocommerce->show_messages();
							
					return false;
				}

			}

		}

		// If cart_item_key is set, the item is already in the cart
		if ( $cart_item_key ) {

			$new_quantity = $quantity + $woocommerce->cart->cart_contents[$cart_item_key]['quantity'];

			$woocommerce->cart->set_quantity( $cart_item_key, $new_quantity );

		} else {

			$cart_item_key = $cart_id;

			// Add item after merging with $cart_item_data - hook to allow plugins to modify cart item
			$woocommerce->cart->cart_contents[$cart_item_key] = apply_filters( 'woocommerce_add_cart_item', array_merge( $cart_item_data, array(
				'product_id'	=> $product_id,
				'variation_id'	=> $variation_id,
				'variation' 	=> $variation,
				'quantity' 		=> $quantity,
				'data'			=> $product_data
			) ), $cart_item_key );

		}

		// Don't fire this action because it forcibly causes a redirect - which is undesirable since we're adding via Ajax
		//do_action( 'woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );

		$this->set_cart_cookies();
		
		$woocommerce->cart->calculate_totals();

		return true;
	}
	
  // Clone of WooCom method ( to set cart cookies ) since that method is private and we need to use it
	private function set_cart_cookies( $set = true ) {
		global $woocommerce;
		
		if ( ! headers_sent() ) {

			if ( $set ) {
				setcookie( "woocommerce_items_in_cart", "1", 0, COOKIEPATH, COOKIE_DOMAIN, false );
				
				setcookie( "woocommerce_cart_hash", md5( json_encode( $woocommerce->cart->get_cart() ) ), 0, COOKIEPATH, COOKIE_DOMAIN, false );
				
			} else {
			
				setcookie( "woocommerce_items_in_cart", "0", time() - 3600, COOKIEPATH, COOKIE_DOMAIN, false );
				
				setcookie( "woocommerce_cart_hash", "0", time() - 3600, COOKIEPATH, COOKIE_DOMAIN, false );
			}
		}
	}
	private function add_error($result,$error_text) {
	  $result['status'] = false;
	  $result['errors'][] = $error_text;
	}
}
?>
