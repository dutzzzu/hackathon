<?php

namespace RGA;
use \Application_Model_User as User;
class Mail_Queue {

    private $_queue; 

    public function __construct($queueOptions = array()) {

        $this->_queue = new \Zend_Queue(new \RGA_Queue_Adapter_MongoDB($queueOptions), $queueOptions);
    }

    public function send(\Zend_Mail $email, $overrideSettings = false) {
        return $this->_queue->send(
            base64_encode(gzcompress(serialize($email)))
        );
    }   

    public function receive($count=10) {

        $messages = $this->_queue->receive($count);
        
        foreach($messages as $message) {
            
            $email = unserialize(gzuncompress(base64_decode($message->body)));

            try {
                $email->send();
                error_log('Email sent ' . print_r($email));
            }  catch(\Zend_Mail_Exception $e) {
                $msg = $e->getMessage();
                $str = $e->__toString();
                $trace =  preg_replace('/(\d\d?\.)/', '\1\r', $str);
                error_log($e->getMessage());
            }
            $this->_queue->deleteMessage($message);
        }   
    }   
}
