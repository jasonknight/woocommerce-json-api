<?php
/**
 * A Customer class to insulate the API from the details of the
 * database representation
*/
require_once(dirname(__FILE__) . "/class-rede-base-record.php");
require_once(dirname(__FILE__) . "/class-wc-json-api-category.php");
class WC_JSON_API_Customer extends JSONAPIBaseRecord {
  // Customers are split off into two
  // datasources, usermeta, and the users
  // themselves.
  private $_meta_attributes;
  // This is static because we really only need to
  // do this mapping once, not for each object we create.
  // We need to keep it small and fast
  public static $_meta_attributes_table; // a mapping of wich Customer attribs
                                   // go to the meta table
  private $_user_attributes;
  public static $_user_attributes_table;

  // A the id for the actual Customer, used for queries.
  private $_actual_user_id;
  public static function setupMetaAttributes() {
    if ( self::$_meta_attributes_table ) {
      return;
    }
    // We only accept these attributes.
    self::$_meta_attributes_table = array(
      'order_count'               => array('name' => '_order_count',      'type' => 'number'),
    );
    /*
      With this filter, plugins can extend this ones handling of meta attributes for a customer,
      this helps to facilitate interoperability with other plugins that may be making arcane
      magic with a customer, or want to expose their customer extensions via the api.
    */
    self::$_meta_attributes_table = apply_filters( 'woocommerce_json_api_user_meta_attributes_table', self::$_meta_attributes_table );
  } // end setupMetaAttributes
  public static function setupUserAttributes() {
    if ( self::$_user_attributes_table ) {
      return;
    }
    self::$_user_attributes_table = array(
      'name'            => array('name' => 'display_name',           'type' => 'string'),
      'username'        => array('name' => 'user_login',             'type' => 'string'),
      'slug'            => array('name' => 'user_nicename',          'type' => 'string'),
      'email'           => array('name' => 'user_email',             'type' => 'string'),
      'status'          => array('name' => 'user_status',            'type' => 'number'),
      'date_registered' => array('name' => 'user_registered',        'type' => 'datetime'),
    );
    self::$_user_attributes_table = apply_filters( 'woocommerce_json_api_user_attributes_table', self::$_user_attributes_table );
  }
  public function asApiArray() {
    $attributes = array_merge(self::$_user_attributes_table, self::$_meta_attributes_table);
    $attributes_to_send['id'] = $this->getUserId();
    $attributes_to_send = array();
    foreach ( $attributes as $name => $desc ) {
      $attributes_to_send[$name] = $this->dynamic_get( $name, $desc, $this->getUserId());
    }
    return $attributes_to_send;
  }
  /**
  *  From here we have a dynamic getter. We return a special REDENOTSET variable.
  */
  public function __get( $name ) {
    if ( isset( self::$_meta_attributes_table[$name] ) ) {
      if ( isset(self::$_meta_attributes_table[$name]['getter'])) {
        return $this->{self::$_meta_attributes_table[$name]['getter']}();
      }
      if ( isset ( $this->_meta_attributes[$name] ) ) {
        return $this->_meta_attributes[$name];
      } else {
        return '';
      }
    } else if ( isset( self::$_user_attributes_table[$name] ) ) {
      if ( isset( $this->_user_attributes[$name] ) ) {
        return $this->_user_attributes[$name];
      } else {
        return '';
      }
    }
  } // end __get
  // Dynamic setter
  public function __set( $name, $value ) {
    if ( isset( self::$_meta_attributes_table[$name] ) ) {
      if ( isset(self::$_meta_attributes_table[$name]['setter'])) {
        $this->{self::$_meta_attributes_table[$name]['setter']}( $value );
      }
      $this->_meta_attributes[$name] = $value;
    } else if ( isset( self::$_user_attributes_table[$name] ) ) {
      $this->_user_attributes[$name] = $value;
    } else {
      throw new Exception( __('That attribute does not exist to be set.','woocommerce_json_api') . " `$name`");
    }
  } 
  public function setUserId( $id ) {
    $this->_actual_user_id = $id;
  }
  
  
  public function getUserId() {
    return $this->_actual_user_id;
  }
  public static function find( $id ) {
    global $wpdb;
    self::setupUserAttributes();
    self::setupMetaAttributes();
    $customer = new WC_JSON_API_Customer();
    $customer->setValid( false );
    $user = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->users} WHERE ID = %d", (int) $id), 'ARRAY_A' );
    if ( $user ) {
      $customer->setUserId( $id );
      foreach ( self::$_user_attributes_table as $name => $desc ) {
        $customer->dynamic_set( $name, $desc,$user[ $desc['name'] ] );
        //$customer->{$name} = $user[$desc['name']];
      }
      foreach ( self::$_meta_attributes_table as $name => $desc ) {
        $value = get_user_meta( $id, $desc['name'], true );
        // We may want to do some "funny stuff" with setters and getters.
        // I know, I know, "no funny stuff" is generally the rule.
        // But WooCom or WP could change stuff that would break a lot
        // of code if we try to be explicity about each attribute.
        // Also, we may want other people to extend the objects via
        // filters.
        $customer->dynamic_set( $name, $desc, $value, $customer->getUserId() );
      }
      $customer->setValid( true );
      $customer->setNewRecord( false );
    }
    return $customer;
  }
}