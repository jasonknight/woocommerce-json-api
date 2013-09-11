<?php
namespace WCAPI;
require_once(dirname(__FILE__) . "/Base.php");
class Category extends Base {
  public static function setupModelAttributes() {
    $wpdb = static::$adapter;

    self::$_model_settings = array_merge( static::getDefaultModelSettings(), array(
      'model_table' => $wpdb->terms,
      'model_table_id' => 'term_id',
      'meta_table' => $wpdb->term_taxonomy,
      'meta_table_foreign_key' => 'term_id',
      'load_meta_function' => function ($model) {
        $s = $model->getModelSettings();
        $adapter = $model->getAdapter();
        $table = $s['meta_table'];
        $key = $s['meta_table_foreign_key'];
        $sql = $adapter->prepare("SELECT * FROM `$table` WHERE `$key` = %s",$model->id);
        $record = $adapter->get_row($sql,'ARRAY_A');
        return $record;
      },
      'save_meta_function' => function ($model) {
        $s = $model->getModelSettings();
        $adapter = $model->getAdapter();
        $table = $s['meta_table'];
        $key = $s['meta_table_foreign_key'];
        $adapter->update($table,$model->remapMetaAttributes(),array($key => $model->id));
      }
      ) 
    );
    self::$_model_attributes_table = array(
      'id'            => array( 'name' => 'term_id',            'type' => 'number'),
      'name'          => array( 'name' => 'name',               'type' => 'string'),
      'slug'          => array( 'name' => 'slug',               'type' => 'string'),
      'group_id'      => array( 'name' => 'term_group',         'type' => 'number'),
    );
  }
  public static function setupMetaAttributes() {
    self::$_meta_attributes_table = array(
      'description'   => array( 'name' => 'description',        'type' => 'string'),
      'parent_id'     => array( 'name' => 'parent',             'type' => 'number'),
      'count'         => array( 'name' => 'count',              'type' => 'number'),
      
      'taxonomy_id'   => array( 'name' => 'term_taxonomy_id',   'type' => 'number'),
    );
  }
  public function setCategory( $category_object ) {

    foreach (self::$_model_attributes_table as $name=>$attrs) {
      if (is_object($category_object)) {
        $this->{$name} = $category_object->{$attrs['name']};
      } else {
        Helpers::warn("Category was not an object, but was of type: " . gettype($category_object));
      }
    }
    return $this;
  }
  // public static function find ( $id ) {
  //   $term = get_term ( $id, 'product_cat', 'ARRAY_A','get_term');
  //   $category = new Category();
  //   if ( $term ) {
  //     foreach ( self::$_model_attributes_table as $name => $desc ) {
  //       $category->dynamic_set( $name, $desc, $term[ $desc['name']], null );
  //     }
  //   }
  //   return $category;
  // }

  public static function find_by_name( $name ) {
    $wpdb = static::$adapter;
    Category::setupModelAttributes();
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
    $category = new Category();
    $first = $results[0];
    if ( $first ) {
      $category->setValid( true );
      foreach ( self::$_model_attributes_table as $name => $desc ) {
        $category->dynamic_set( $name, $desc, $first[ $desc['name']], null );
      }
    }
    return $category;
  }
  /**
  *  Similar in function to Model.all in Rails, it's just here for convenience.
  */
  public static function all($fields = 'id') {
    $wpdb = static::$adapter;
    Category::setupModelAttributes();
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
    $category = new Category();
    $category->addQuery($sql);
    return $category;
  }
  public function fromApiArray( $attrs ) {
    $attributes = self::$_model_attributes_table;
    foreach ( $attrs as $name => $value ) {
      if ( isset($attributes[$name]) ) {
        $desc = $attributes[$name];
        $this->dynamic_set( $name, $desc, $value, null);
      }
    }
    return $this;
  }
  // public function __get( $name ) {
  //   if (isset(self::$_model_attributes_table[$name])) {
  //     return $this->$_attributes[$name];
  //   } else {
  //     return '';
  //   }
  // }

  // public function __set( $name, $value ) {
  //   if ( isset(self::$_model_attributes_table[$name])) {
  //     $this->_attributes[$name] = $value;
  //   } else {
  //     throw new Exception( __('That attribute does not exist to be set.','woocommerce_json_api')  . " `$name`" );
  //   }
  // }
}