<?php 
namespace WCAPI;
require_once(dirname(__FILE__) . "/BaseHelpers.php");
class ProductAttribute {
	public $attrs;
	public function __construct( $attrs = null ) {
		if ( $attrs == null )
			return;
		if ( is_string( $attrs['value']) ) {
			$attrs['value'] = array_map('trim',explode('|',$attrs['value']));
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
		$attrs['value'] = implode('|',$attrs['value']);
		return array("$name" => $attrs);
	}
	public function asApiArray() {
		$name = strtolower($this->attrs['name']);
		return array("$name" => $this->attrs);
	}
	public function toWPBool($b) {
		return $b == true ? 'yes' : 'no';
	}
	public function toRealBool( $b ) {
		return $b == 'yes' ? true : false;
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
}