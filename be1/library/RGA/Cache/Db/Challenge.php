<?php

require_once 'Zend/Db/Table/Abstract.php';

class RGA_Cache_DB_Challenge extends Zend_Db_Table_Abstract {

    /**
     * The default table name 
     */
    protected $_name = 'field_data_field_challenge';
    protected $_adapter = 'db_cms';

    protected function _setupDatabaseAdapter() {
	$this->_db = Zend_Registry::get($this->_adapter);
    }

}
