<?php

/**
 * general application registry class
 * should hold singleton instances, etc etc
 */
class RGA_App_App {

    const APP_SESSION_NAMESPACE = 'RGA';

    /**
     *
     * @var App
     */
    private static $_instance = null;

    /**
     *
     * @var Zend_Session_Namespace
     */
    private $_session = null;

    /**
     * used to hold mapper instances
     * @var array 
     */
    private $_mappers = array();

    private function __construct() {
	
    }

    public static function init() {
	if (self::$_instance === null) {
	    self::$_instance = new RGA_App_App();
	}
	return self::$_instance;
    }

    /**
     *
     * @return Zend_Session_Namespace
     */
    public static function session() {
	self::init();
	return self::$_instance->_getSession();
    }

    public static function mapper($name) {
	self::init();
	return self::$_instance->_getMapper($name);
    }

    private function _getSession() {
	if (null === $this->_session) {
	    $this->_session = new Zend_Session_Namespace(self::APP_SESSION_NAMESPACE);
	}

	return $this->_session;
    }

    private function _getMapper($name) {
	$parts = explode('/', $name);
	$parts[0] .= '_Mapper_';

	$last = array_pop($parts);

	if (strpos($last, 'Mapper') === false) {
	    $last .= 'Mapper';
	}
	$parts = array_map('ucfirst', $parts);
	$last = ucfirst($last);

	$name = implode('_', $parts) . $last;

	if (!isset($this->_mappers[$name])) {
	    $this->_mappers[$name] = new $name();
	}

	return $this->_mappers[$name];
    }

}
