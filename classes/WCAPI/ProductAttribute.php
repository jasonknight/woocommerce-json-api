<?php 
namespace WCAPI;
require_once(dirname(__FILE__) . "/BaseHelpers.php");
require_once(dirname(__FILE__) . "/Category.php");
class ProductAttribute {
	public $attrs;
	public $product_id;
	public function __construct( $attrs = null, $product_id = null ) {
		if ( $attrs == null )
			return;
		$this->product_id = $product_id;
		if ( is_string( $attrs['value'])  ) {
			$attrs['value'] = array_map('trim',explode('|',$attrs['value']));
		}
		if ( intval($attrs['is_taxonomy']) == 1 && $this->product_id) {
			$cat = new Category();
			$cattrs = woocommerce_get_product_terms( $this->product_id, $attrs['name'], 'all' );
			$attrs['value'] = array();
			foreach ( $cattrs as $catt) {
				$cat->fromDatabaseResult( Helpers::std2a($catt) );
				$attrs['value'][] = $cat->asApiArray();
			}
			
		}
		$attrs['is_visible'] = $this->toWPBool($attrs['is_visible']);
		$attrs['is_variation'] = $this->toWPBool( $attrs['is_variation']);
		$attrs['is_taxonomy'] = $this->toWPBool( $attrs['is_taxonomy']);
		$this->attrs = $attrs;
		Helpers::debug( "ProductAttribute::__construct" . var_export($attrs,true));
	}
	public function getForDb() {
		$name = strtolower($this->attrs['name']);
		$attrs = $this->attrs;
		$attrs['is_visible'] = $this->toRealBool($attrs['is_visible']);
		$attrs['is_variation'] = $this->toRealBool( $attrs['is_variation']);
		$attrs['is_taxonomy'] = $this->toRealBool( $attrs['is_taxonomy']);
		if ( ! 'yes' == $attrs['is_taxonomy'] ) {
			$attrs['value'] = implode('|',$attrs['value']);
		} else {
			$this->createOrUpdateAttribute( $attrs );
		}
		return array("$name" => $attrs);
	}
	public function asApiArray() {
		$name = strtolower($this->attrs['name']);
		return array("$name" => $this->attrs);
	}
	public function toWPBool($b) {
		if ( $b == 1 || $b === 'yes') {
			$ret = 'yes';	
		} else {
			$ret = 'no';
		}
		Helpers::debug("toWpBool b:$b ret:$ret");
		return $ret;
	}
	public function toRealBool( $b ) {
		return $b == 'yes' ? 1 : 0;
	}
	public function getSupportedAttributes() {
		return array(
	        "name" => array('name' => 'name', 'type' => 'string', 'sizehint' => 5),
	        "value" => array('name' => 'value', 'type' => 'array', 'sizehint' => 10),
	        "position"=> array('name' => 'position', 'type' => 'number', 'sizehint' => 1),
	        "is_visible" => array('name' => 'is_visible', 'type' => 'bool', 'values' => array('yes','no'), 'sizehint' => 1),
	        "is_variation"=> array('name' => 'is_variation', 'type' => 'bool', 'values' => array('yes','no'), 'sizehint' => 1),
	        "is_taxonomy"=> array('name' => 'is_taxonomy', 'type' => 'bool', 'values' => array('yes','no'), 'sizehint' => 1),
        );
	}
	public function createOrUpdateAttribute( $attrs ) {
		return;
		$fromto = array(
			'term_id' => 'term_id',
			'name' => 'name',
			'slug' => 'slug',
			'term_group' => 'group_id',
			'term_taxonomy_id' => 'taxonomy_id',
			'description' => 'description',
			'parent' => 'parent_id',
			'count' => 'count',

		);
		$new_attrs = array();


		foreach ( $fromto as $key=>$nkey) {
			if ( isset($attrs[$key]) )
				$new_attrs[$nkey] = $attrs[$key];
		}


		$cat = new Category();


		if ( isset( $new_attrs['id']) && !empty($new_attrs['id']) ) {
			$cat->update($new_attrs);
		} else {
			$cat->create($new_attrs);
		}


	} // end createOrUpdateAttribute
}