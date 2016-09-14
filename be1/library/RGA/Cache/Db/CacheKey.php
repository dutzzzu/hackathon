<?php

require_once 'Zend/Db/Table/Abstract.php';

class RGA_Cache_DB_CacheKey extends Zend_Db_Table_Abstract {

    /**
     * The default table name 
     */
    protected $_name = 'cache_site_keys';
    protected $_adapter = 'db_cache';

    protected function _setupDatabaseAdapter() {
	$this->_db = Zend_Registry::get($this->_adapter);
    }

}
