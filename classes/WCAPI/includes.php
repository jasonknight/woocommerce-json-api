<?php
require_once( dirname( __FILE__ ) . '/Base.php' );
require_once( dirname( __FILE__ ) . '/Product.php' );
require_once( dirname( __FILE__ ) . '/Category.php' );
require_once( dirname( __FILE__ ) . '/Order.php' );
require_once( dirname( __FILE__ ) . '/OrderItem.php' );
require_once( dirname( __FILE__ ) . '/Customer.php' );
function __fixPHPNSGlobalStupidity() {
  global $wpdb;
  \WCAPI\Base::setAdapter( $wpdb );
}
__fixPHPNSGlobalStupidity();

