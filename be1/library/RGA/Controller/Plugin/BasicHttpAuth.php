<?php

namespace RGA;
use \RGA\Auth\Adapter as AuthAdapter;
class Controller_Plugin_BasicHttpAuth
    extends \Zend_Controller_Plugin_Abstract {

    const API_KEY_HEADER = 'x-lr-api-key';
    const API_AUTHORIZATION_HEADER = 'x-lr-authorization';


    public function preDispatch(\Zend_Controller_Request_Abstract $request) {
        $authHeader = $request->getHeader(self::API_AUTHORIZATION_HEADER, false);
        $apiKey = $request->getHeader(self::API_KEY_HEADER, false);
        $login = explode(':',base64_decode($authHeader));

        if ($apiKey && $authHeader && count($login) == 2) {
            if (!$this->_isApiKeyValid($apiKey, $login[0])) {
                return;
            }
            $auth = \Zend_Auth::getInstance();
            $adapter = new AuthAdapter(
                $login[0],
                $login[1]
            );
            $result = $auth->authenticate($adapter);
        }
    }

    protected function _isApiKeyValid($apiKey, $userEmail) {
        return true;
    }
}