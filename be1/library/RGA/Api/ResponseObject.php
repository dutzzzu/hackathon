<?php
class RGA_Api_ResponseObject extends RGA_Api_ResponseAbstract {
    
    protected function _build() {
        if ($this->_data) {
            $this->data = method_exists($this->_data, 'marshall') ? call_user_func_array(array($this->_data, 'marshall'), $this->_marshalling_options) : $this->_data;
        }
    }
}
