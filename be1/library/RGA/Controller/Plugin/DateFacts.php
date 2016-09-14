<?php

namespace RGA;

class Controller_Plugin_DateFacts extends \Zend_Controller_Plugin_Abstract {

    public $dateFactsDirectory;

    public function __construct($directory) {
        $this->dateFactsDirectory = $directory;
    }

    public function dispatchLoopStartup(\Zend_Controller_Request_Abstract $request) {

    }

    public function preDispatch(\Zend_Controller_Request_Abstract $request) {
    }
}
