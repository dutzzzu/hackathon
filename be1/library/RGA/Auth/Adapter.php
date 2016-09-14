<?php

namespace RGA\Auth;
use \Zend_Auth_Result as Result;
use \Zend_Registry as Registry;
use Application_Model_User as User;
use Application_Model_Session as Session;

class Adapter implements \Zend_Auth_Adapter_Interface {

    private $_email;
    private $_password;
    private $_user;

    /**
     * Sets the email and password code for authentication
     *
     * @return void
     */
    public function __construct($email = '', $password = '') {
        
        $this->_email = $email;
        $this->_password = $password;
    }
    
    public function setIdentity(User $user, $updateLastLogin = true) {

        // update the user with the last login time
        if (!$user instanceof Application_Model_DummyUser && $updateLastLogin) {
            User::update(array("_id"=>$user->getId()),array('$set'=>array("lastlogin"=>time()))); //using direct update instead of the model save, because of model hooks, we don't want this to be called in a loop
        }
        //usleep(600000); //why???
        // set the identity.
        $this->_user = $user;
    }

    public function authenticate() {

        if ($this->_user instanceof User) {
            // here we add the identity (second param)
            return new Result(Result::SUCCESS, $this->_user, array());
        }
        $code = Result::FAILURE;
        $identity = null;
        $user = User::one(array('email' => $this->_email));
        if ($user && $user->getId()) {
            
            // calculate the password
            $p = hash_hmac('sha1', $this->_password, $user->salt);
            
            if ($user->password === $p) {
                if(!User::validatePassword($this->_password))
                {
                    $user->old_password_display_warn = true;
                }
                $identity = $user;
                $code = Result::SUCCESS;
                
            } else {
                error_log("Failed to authenticate!");
            }
        }
        $result = new Result($code, $identity, array());
        return $result;
    }

}
