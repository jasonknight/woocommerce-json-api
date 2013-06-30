<?php
require_once(dirname(__FILE__) . "/class-rede-base-record.php");
class WC_JSON_API_Category extends RedEBaseRecord {
  private $_attributes;
  public static $_attributes_table;
  public function __construct() {
    $this->_attributes = array();
    $this->setValid(false);
    WC_JSON_API_Category::setupAttributesTable();
  }
  public static function setupAttributesTable() {
    if ( self::$_attributes_table ) {
      return;
    }
    self::$_attributes_table = array(
      'id'            => array( 'name' => 'term_id',            'type' => 'number'),
      'name'          => array( 'name' => 'name',               'type' => 'string'),
      'slug'          => array( 'name' => 'slug',               'type' => 'string'),
      'description'   => array( 'name' => 'description',        'type' => 'string'),
      'parent_id'     => array( 'name' => 'parent',             'type' => 'number'),
      'count'         => array( 'name' => 'count',              'type' => 'number'),
      'group_id'      => array( 'name' => 'term_group',         'type' => 'number'),
      'taxonomy_id'   => array( 'name' => 'term_taxonomy_id',   'type' => 'number'),
    );
  }
  public function setCategory( $category_object ) {
    foreach (self::$_attributes_table as $name=>$attrs) {
      $this->{$name} = $category_object->{$attrs['name']};
    }
    return $this;
  }
  public static function find ( $id ) {
    $term = get_term ( $id, 'product_cat', 'ARRAY_A','get_term');
    $category = new WC_JSON_API_Category();
    if ( $term ) {
      foreach ( self::$_attributes_table as $name => $desc ) {
        $category->dynamic_set( $name, $desc, $term[ $desc['name']], null );
      }
    }
    return $category;
  }
  public static function find_by_name( $name ) {
    global $wpdb;
    WC_JSON_API_Category::setupAttributesTable();
    $sql = "
      SELECT 
        categories.*, 
        taxons.term_taxonomy_id, 
        taxons.description, 
        taxons.parent,
        taxons.count 
      FROM 
        wp_terms as categories, 
        wp_term_taxonomy as taxons 
      WHERE
        (taxons.taxonomy = 'product_cat') and 
        (categories.term_id = taxons.term_id) and
        (categories.name = %s)
    ";
    $sql = $wpdb->prepare( $sql, $name );
    $results = $wpdb->get_results($sql,'ARRAY_A');
    $category = new WC_JSON_API_Category();
    $first = $results[0];
    if ( $first ) {
      $category->setValid( true );
      foreach ( self::$_attributes_table as $name => $desc ) {
        $category->dynamic_set( $name, $desc, $first[ $desc['name']], null );
      }
    }
    return $category;
  }
  /**
    Similar in function to Model.all in Rails, it's just here for convenience.
  */
  public static function all($fields = 'id') {
    global $wpdb;
    WC_JSON_API_Category::setupAttributesTable();
    $sql = "
      SELECT 
        categories.*, 
        taxons.term_taxonomy_id, 
        taxons.description, 
        taxons.parent,
        taxons.count 
      FROM 
        wp_terms as categories, 
        wp_term_taxonomy as taxons 
      WHERE
        (taxons.taxonomy = 'product_cat') and 
        (categories.term_id = taxons.term_id)
    ";
    $category = new WC_JSON_API_Category();
    $category->addQuery($sql);
    return $category;
  }
  public function asApiArray() {
    $attrs = $this->_attributes;
    
    return $attrs;
  }
  public function fromApiArray( $attrs ) {
    $attributes = self::$_attributes_table;
    foreach ( $attrs as $name => $value ) {
      if ( isset($attributes[$name]) ) {
        $desc = $attributes[$name];
        $this->dynamic_set( $name, $desc, $value, null);
      }
    }
    return $this;
  }
  public function __get( $name ) {
    if (isset(self::$_attributes_table[$name])) {
      return $this->$_attributes[$name];
    } else {
      return '';
    }
  }

  public function __set( $name, $value ) {
    if ( isset(self::$_attributes_table[$name])) {
      $this->_attributes[$name] = $value;
    } else {
      throw new Exception( __('That attribute does not exist to be set.','woocommerce_json_api')  . " `$name`" );
    }
  }
}