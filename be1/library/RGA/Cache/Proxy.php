<?php
class RGA_Cache_Proxy {
    protected $_cache;
    protected $_frontend;
    protected $_backend;
    protected $_backendOptions;
    protected $_frontendOptions;
    protected $_namespace;
    
    const NAMESPACE_KEY = 'NS_key_';
    public function __construct($frontend, $backend, $frontendOptions = array(), $backendOptions = array()) {
        
        $this->_frontend = $frontend;
        $this->_backend = $backend;
        $this->_backendOptions = $backendOptions;
        $this->_frontendOptions = $frontendOptions;
        $this->_namespace = $backend == 'Memcached' ? $this->_backendOptions['namespace'] : 'RGA_';
        $this->_cache = Zend_Cache::factory(
            $frontend,
            $backend,
            $this->_frontendOptions,
            $this->_backendOptions
        );
    }

    public function __call($name, $arguments) {
        $response = null;
        if ($this->_backend == 'Memcached' && method_exists($this, "_{$name}")) {
            $response = call_user_func_array(array($this, "_{$name}"), $arguments);
        }  else {
            $response = call_user_func_array(array($this->_cache, $name), $arguments);
        }
        return $response;
    }

    public function _load($id, $doNotTestCacheValidity = false) {
        return $this->_cache->load($this->_wrapId($id), $doNotTestCacheValidity);
    }

    public function _test($id) {
        return $this->_cache->test($this->_wrapId($id));
    }

    public function _save($data, $id, $tags = array(), $specificLifetime = false) {
        return $this->_cache->save($data, $this->_wrapId($id), $tags, $specificLifetime);
    }

    public function _remove($id) {
        return $this->_cache->remove($this->_wrapId($id));
    }

    public function _clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array()) {   
        //$this->_invalidateNamespace();
        $this->_cache->clean($mode,$tags);
    }

    private function _wrapId($id) {
        //$ns = $this->_getNamespaceValue();
        $ns = date("d"); //for some reason retrieving an additional namespace doesn't really work, will set a current day int value, to auto-expire the cache once a day
        return "{$this->_namespace}{$ns}_{$id}";
    }

    private function _invalidateNamespace() {
        //$ns = (int) $this->_getNamespaceValue();
        //$this->_cache->save($ns+1, $this->_getNamespaceKey());
        $this->_cache->clean();
    }

    private function _getNamespaceValue() {
        $nskey = $this->_getNamespaceKey();
        $ns = $this->_cache->load($nskey);   
        if(!$ns) {
            $ns = mt_rand(1, 10000);
            $this->_cache->save($ns, $nskey);
        }
        return $ns;
    }

    private function _getNamespaceKey() {
        return  $this->_namespace . self::NAMESPACE_KEY;
    }


}