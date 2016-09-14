<?php

namespace RGA;

class Controller_Plugin_ABTest extends \Zend_Controller_Plugin_Abstract {
    public $ab_version;

    public function preDispatch(\Zend_Controller_Request_Abstract $request) {
        $rand_version = $this->_getRandomVersion($request);
        $get_version = $request->getParam('debug-ab', false);
        $ver = (isset($get_version)  && $get_version !== FALSE) ? $get_version : $rand_version;

        $pro = $request->getParam('promote_to_homepage', false);

        if(isset($_COOKIE['ab_version']) && !$get_version) $ver = $_COOKIE['ab_version'];
        if ($ver && in_array($ver, array('a', 'b', 'c'))) {
            // First remove the old value
            setcookie(
                "ab_version",
                "",
                time() - (10 * 365 * 24 * 60 * 60)
            );
            
            // Now set the new value
            setcookie(
                "ab_version",
                $ver,
                time() + (10 * 365 * 24 * 60 * 60)
            );
            $_COOKIE['ab_version'] = $ver;
        }
    }

    private function _getRandomVersion($request) {
        $rand = mt_rand(0, 100);
        $choice = '';
        if ($rand < 49) {
            $choice = 'a';
        } else {
            $choice = 'b';
        }
        
        return $choice;
    }
}