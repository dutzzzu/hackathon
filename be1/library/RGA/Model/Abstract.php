<?php

/**
 * Base class for all application Models
 * each model should inherit from this
 */
class RGA_Model_Abstract {

    public $extraData = array();

    //public static $extraProperties = array();
    public function __construct(array $options = null) {
	if (is_array($options)) {
	    $this->setOptions($options);
	}
    }

    public function setOptions(array $options) {
	$methods = get_class_methods($this);

	foreach ($options as $key => $value) {
	    $method = 'set' . $this->_toCamelCase($key);

	    if (in_array($method, $methods)) {
		$this->$method($value);
	    }
	}

	return $this;
    }

    public function __set($name, $value) {
	$method = 'set' . ucfirst($name);

	if ('mapper' == $name || !method_exists($this, $method)) {
	    throw new Exception('Invalid Model property: ' . $name);
	}

	return $this->$method($value);
    }

    public function __get($name) {
	$method = 'get' . ucfirst($name);

	if ('mapper' == $name || !method_exists($this, $method)) {
	    throw new Exception('Invalid Model property: ' . $name);
	}

	return $this->$method();
    }

    public function toArray($filter = null) {
	$data = array();

	$reflection = new ReflectionClass($this);
	foreach ($reflection->getProperties($filter) as $property) {
	    $name = str_replace('_', '', $property->name);
	    $data[$this->_fromCamelCase($name)] = $this->{$name};
	}

	return $data;
    }

    /**
     * $name should have the following format: _firstName (underscore first, then camelCase)
     * @param type $name 
     */
    protected function _fromCamelCase($str) {
	$str = str_replace("_", "", $str);

	$str[0] = strtolower($str[0]);
	$func = create_function('$c', 'return "_" . strtolower($c[1]);');

	return preg_replace_callback('/([A-Z])/', $func, $str);
    }

    /**
     * convert database-style naming (first_name) to firstName
     * @param array $str
     * @return type 
     */
    protected function _toCamelCase($str, $capitalizeFirst = true) {
	if (true === $capitalizeFirst) {
	    $str[0] = strtoupper($str[0]);
	}

	$func = create_function('$c', 'return strtoupper($c[1]);');
	return preg_replace_callback('/_([a-z])/', $func, $str);
    }

    public function copy(RGA_Model_Abstract $model) {
	$reflection = new ReflectionClass($this);
	foreach ($reflection->getProperties() as $property) {
	    $model->{$property->name} = $this->{$property->name};
	}
    }

}
