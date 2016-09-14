<?php

/**
 * base class for all application mappers
 * all mappers should inherit from this
 */
class RGA_Model_Mapper_Abstract {

    protected $_tableName = null;

    /**
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $_dbTable = null;

    /**
     *
     * @param Zend_Db_Table_Abstract $dbTable
     * @return App_Model_Mapper_Abstract 
     */
    public function setDbTable($dbTable) {
	if (is_string($dbTable)) {
	    $dbTable = new $dbTable();
	}

	if (!$dbTable instanceof Zend_Db_Table_Abstract) {
	    throw new Exception('Invalid table data gateway provided');
	}
	$this->_dbTable = $dbTable;
	return $this;
    }

    /**
     *
     * @return Zend_Db_Table_Abstract
     */
    public function getDbTable() {
	if (null === $this->_dbTable) {
	    $this->setDbTable($this->_tableName);
	}
	return $this->_dbTable;
    }

    public function getAdapter() {
	return $this->getDbTable()->getAdapter();
    }

}
