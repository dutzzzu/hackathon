<?php

namespace RGA\Auth\Adapter;

use RGA\Auth\Identity\LifeReimagined as Identity;
//use \RGA\OAuth2\Consumer;
use \Zend_Auth_Result as Result;
use \Zend_Registry as Registry;
//use Application_Model_UserMapper as UserMapper;
//use Application_Model_User as User;

class LifeReimagined implements \Zend_Auth_Adapter_Interface {

    private $_username;
    private $_password;
    private $_userMapper;
    private $_user;

    /**
     * Sets the email and password code for authentication
     *
     * @return void
     */
    public function __construct($username = '', $password = '', UserMapper $userMapper = null) {
        
        $this->_username = $username;
        $this->_password = $password;
        $this->_userMapper = $userMapper;
    }
    
    public function setIdentity(User $user) {
        $this->_user = $user;
    }

    public function authenticate() {
        /*
        if ($this->_user instanceof User) {
            return new Result(Result::SUCCESS, new Identity($this->_user), array());
        }
        $code = Result::FAILURE;
        $identity = null;
        $user = $this->_userMapper->getByUsername($this->_username, new User());
        if ($user && $user->getId()) {
            
            // calculate the password
            $p = hash_hmac('sha1', $this->_password, $user->getSalt());
            
            if ($user->getPassword() === $p) {
                $identity = new Identity($user);
                $code = Result::SUCCESS;
                
            } else {
                error_log("Failed to authenticate!");
            }
        }
        $result = new Result($code, $identity, array());
        return $result;
        */
    }

}
