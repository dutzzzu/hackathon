<?php 
class RGA_Api_ResponseCollection extends RGA_Api_ResponseAbstract {
    
    public $count;
    protected function _build() {
        $this->data = array();

        if ($this->_data) {
            foreach ($this->_data as $obj) {
                $this->data[] = method_exists($obj, 'marshall') ? call_user_func_array(array($obj, 'marshall'), $this->_marshalling_options) : $obj;
            }   
        }
        
        $this->count = count($this->data);
        if ($this->_data instanceof Shanty_Mongo_Iterator_Cursor) {
            $this->total = $this->_data->count();
        } else {
            $this->total = $this->count;
        }
        
    }
}
