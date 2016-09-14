<?php
abstract class RGA_Api_ResponseAbstract {
    
    protected $_data;
    protected $_request;
    protected $_marshalling_type;

    public $data;
    public $errors;
    public $status;
    public $request_uri;

    public function __construct($request, $data, $status, $errors = array(), $marshallingOptions = null) {
        $this->_data = $data;
        $this->errors = $errors;
        $this->status = $status;
        $this->_request = $request;
        $this->request_uri = $request->getRequestUri();
        $this->_marshalling_options = is_array($marshallingOptions) ? $marshallingOptions : array($marshallingOptions);
        $this->_build();

        /* todo remove the following after the refactor */
        $httpCode = "{$this->status}";
        if ($this->status && $httpCode[0] != 2) {
            throw new REST_Exception(@$this->errors[0] ?: $this->status, $this->status);
        }
    }

    abstract protected function _build();

    public function set(&$view) {

        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if ($key != 'errors') {
                if (strpos($key, '_') !== 0) {
                    $view->{$key} = $value; 
                }
            } else if ($value) {
                throw new REST_Exception($value[0], $this->status);
            }
        }

    }
}
