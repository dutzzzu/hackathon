<?php 
class RGA_Drupal_Node_Builder {
	const LANGUAGE_NONE = 'und';
	
	private $_nodeData = array();
	private $_nodeProperties = array('title', 'nid', 'vid', 'language', 'status', 'type');

	public function __construct($type) {
		$this->_nodeData['type'] = $type;	
		$this->_nodeData['language'] = 'en';
	}

	public function set($field_name, $value, $key = 'value') {
		if (!isset($value)) return;
		$this->_nodeData[$field_name] = $this->_getValue($field_name, $value, $key);
		return $this;
	}

	private function _getValue($field_name, $value, $key = 'value') {
		if (!in_array($field_name, $this->_nodeProperties)) {
			if (is_array($value)) {
				$value = array(self::LANGUAGE_NONE => $value/*$col*/);
			} else {
				$value = array(self::LANGUAGE_NONE => array(array($key => $value)));	
			}
		}
		return $value;
	}

	public function getNode() {
		return (object)$this->_nodeData;
	}
}
