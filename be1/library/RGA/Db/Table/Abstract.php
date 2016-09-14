<?php 

namespace RGA;

abstract class Db_Table_Abstract extends \Zend_Db_Table_Abstract {
    
     protected $_rw = null;

     protected function _fetch(\Zend_Db_Table_Select $select)
     {
     	$this->_rw = $this->getDefaultAdapter();
        $this->_setAdapter('slave');
        try {
            $data = parent::_fetch($select);    
        } catch (\Exeption $e) {
            error_log('Failed to use slave database:' . $e->getMessage());
            $this->_setAdapter($this->_rw);
            $this->_rw = null;
            $data = parent::_fetch($select);
        }
        $this->_setAdapter($this->_rw);
        $this->_rw = null;
        return $data;
    }

}
