<?php
class RGA_Drupal_Client_Exception extends Exception {};
class RGA_Drupal_Client {

    private $_settings;
    private $_session;
    private $_retryCount = 0;
    private $_cache;
    const DRUPAL_LOGIN_ENDPOINT = '/user/login';
    const DRUPAL_NODE_ENDPOINT = '/node';
    const MAX_RETRIES = 3;

    public function __construct() {
        $conf = Zend_Registry::get('config');
        $this->_cache = Zend_Registry::get('cache');
        $this->_settings = $conf['drupal'];
        $this->_session = new Zend_Session_Namespace('drupal');
    }

    public function get($uri, $isAuth = false, $bypassCache = false) {
        $uri = str_replace("/?","?",$uri); //fix for "/?" present in endpoint which caused CMS connection lost error
        $cacheKey = md5(serialize(func_get_args()));
        if (($body = $this->_cache->load($cacheKey)) === false || $bypassCache) {
            if(isset($GLOBALS["profiling"])) { $callers=debug_backtrace(); $GLOBALS["profiling"]->mark("DRUPAL no cache before request",get_called_class(),$callers[1]['function'],$this->_settings['endpoint']['url'].$uri); }

            $client = $this->_getHttpClient(
                $this->_settings['endpoint']['url'] . $uri,
                Zend_Http_Client::GET
            );
        
            $cookie = $this->_auth();
            $client->setCookie($cookie['name'], $cookie['value']);
        
            $body = null;
            $response = $client->request();
            if ($response->isSuccessful()) {
                $body = json_decode($response->getBody());
                if (!$bypassCache) {
                    $this->_cache->save($body, $cacheKey, array('cms'));    
                }
                $this->parseResponseForNids($body);
                $this->activateCache($cacheKey);
            } else {
                $this->_retryOperation('get', func_get_args());
            }
            if(isset($GLOBALS["profiling"])) { $callers=debug_backtrace(); $GLOBALS["profiling"]->mark("DRUPAL no cache after request",get_called_class(),$callers[1]['function'],$this->_settings['endpoint']['url'].$uri); }

        } else { //from cache
            if(isset($GLOBALS["profiling"])) { $callers = debug_backtrace(); $GLOBALS["profiling"]->mark("DRUPAL from cache", get_called_class(), $callers[1]['function'], $this->_settings['endpoint']['url'].$uri); }
        }

        return $body;
    }
    public function post($uri, $body) {
        try {
            return $this->_save($uri, $body, Zend_Http_Client::POST);
        } catch (Exception $e) {
            $this->_retryOperation('post', func_get_args());
        }
    }
    public function put($uri, $body){
        try {
            return $this->_save($uri, $body, Zend_Http_Client::PUT);
        } catch (Exception $e) {
            $this->_retryOperation('put', func_get_args());
        }
    }

    private function _save($uri, $body, $method) {
        $res = null;
        $cookie = $this->_auth();
        $url = $this->_settings['endpoint']['url'] . $uri;
        $client = $this->_getHttpClient(
            $url,
            $method,
            array('Content-type' => 'application/json')
        );
        $client->setCookie($cookie['name'], $cookie['value']);
        $client->setRawData(json_encode($body));
        $response = $client->request();
        
        if ($response->isSuccessful()) {
            $res = json_decode($response->getBody());
        } else {
            $this->_retryOperation('save', func_get_args());
        }
        return $res;
    }

    private function _auth() {
        if (!$this->_session->cookie) {
            $client = $this->_getHttpClient(
                $this->_settings['endpoint']['url'] . self::DRUPAL_LOGIN_ENDPOINT,
                Zend_Http_Client::POST,
                array('Content-type' => 'application/json', 'Accept' => 'application/json')
            );
            $client->setRawData(json_encode($this->_settings['auth']));
            
            $response = $client->request();

            if ($response->isSuccessful()) {
                $logged_user = json_decode($response->getBody());
                
                $this->_session->cookie = array(
                    'name' => $logged_user->session_name,
                    'value' => $logged_user->sessid
                );
            } else if($this->_retryCount < self::MAX_RETRIES) {
                $this->_retryCount ++;
                return $this->_auth();
            } else {
                throw new RGA_Drupal_Client_Exception(
                    'Unable to authenticate CMS user: ' . $response->getMessage(), 
                    $response->getStatus()
                );
            }
        }

        return $this->_session->cookie;
    }

    private function _retryOperation($methodName, $args) {
        if ($this->_retryCount < self::MAX_RETRIES) {
            $this->_retryCount ++;
            unset($this->_session->cookie);  // remove credentials
            call_user_func_array(array(get_class($this), $methodName), $args); // retry recursively
        } else {
            error_log(
                'Unable to perform operation, CMS connection lost: ' . $methodName . ' (' . print_r($args,1). ')'
            );
        }
    }

    private function _getHttpClient($url, $method = 'GET', $headers = array()) {

        $client = new Zend_Http_Client(
            $url, 
            array(
                'maxredirects' => 0,
                'timeout'      => 30,
            )
        );
        if (@$this->_settings['httpauth']['user']) {
            $client->setAuth(
                $this->_settings['httpauth']['user'],
                $this->_settings['httpauth']['password'], Zend_Http_Client::AUTH_BASIC
            );
        }
        $client->setMethod($method);
        if ($headers) {
            $client->setHeaders($headers);
        }
        return $client;
    }

    protected function parseResponseForNids($body) {
        $array = (array) $body;
        foreach($array as $key => $value) {
            if($key==="nid" || $key==="target_id") $this->_arrNids[] = $value;
            elseif(is_array($value) || is_object($value)) $this->parseResponseForNids($value);
        }
    }

    protected function getNidsArray() {
        if(!empty($this->_arrNids)){
            return array_values(array_unique($this->_arrNids));
        }

        return false;
    }

    protected function activateCache($cacheKey)
    {
        $arrayNid = $this->getNidsArray();
        if(!empty($arrayNid)) {
            foreach ($arrayNid as $nid) {
                $cache = new RGA_Cache_CacheKey();
                $cache->nid = $nid;
                $cache->hash = $cacheKey;
                $cache->source = RGA_Cache_CacheKey::$_CMS_KEY;
                RGA_App_App::mapper('RGA_Cache/CacheKey')->save($cache);
            }
        }
    }
}