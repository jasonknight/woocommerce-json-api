<?php
require_once( dirname( __FILE__ ) . '/Base.php' );
require_once( dirname( __FILE__ ) . '/Product.php' );
require_once( dirname( __FILE__ ) . '/Category.php' );
require_once( dirname( __FILE__ ) . '/Order.php' );
require_once( dirname( __FILE__ ) . '/OrderItem.php' );
require_once( dirname( __FILE__ ) . '/Customer.php' );
require_once( dirname( __FILE__ ) . '/Comment.php' );
require_once( dirname( __FILE__ ) . '/Coupon.php' );
function __fixPHPNSGlobalStupidity() {
  global $wpdb,$post,$user_ID,$post_ID;
  \WCAPI\Base::setAdapter( $wpdb );
}
__fixPHPNSGlobalStupidity();

