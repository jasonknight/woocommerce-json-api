<?php
require_once( dirname( __FILE__ ) . '/Mapper.php' );
require_once( dirname( __FILE__ ) . '/Base.php' );
require_once( dirname( __FILE__ ) . '/Product.php' );
require_once( dirname( __FILE__ ) . '/Category.php' );
require_once( dirname( __FILE__ ) . '/Order.php' );
require_once( dirname( __FILE__ ) . '/OrderItem.php' );
require_once( dirname( __FILE__ ) . '/OrderTaxItem.php' );
require_once( dirname( __FILE__ ) . '/OrderCouponItem.php' );
require_once( dirname( __FILE__ ) . '/Customer.php' );
require_once( dirname( __FILE__ ) . '/Comment.php' );
require_once( dirname( __FILE__ ) . '/Coupon.php' );
require_once( dirname( __FILE__ ) . '/Review.php' );
require_once( dirname( __FILE__ ) . '/Image.php' );
function __fixPHPNSGlobalStupidity() {
  global $wpdb,$post,$user_ID,$post_ID;
  \WCAPI\Base::setAdapter( $wpdb );
}
__fixPHPNSGlobalStupidity();

