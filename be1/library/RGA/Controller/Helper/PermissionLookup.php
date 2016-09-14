<?php

namespace RGA;

class Controller_Helper_PermissionLookup
    extends \Zend_Controller_Action_Helper_Abstract {

    const AUTHENTICATION_CONTROLLER = 'user';
    const AUTHENTICATION_ACTION = 'login';
    const REGISTRATION_ACTION = 'sign-up';
    const SITE_DISABLED_CONTROLLER = 'coming-soon';
    const SITE_DISABLED_ACTION = 'index';

    /**
     * @var RGA_Acl
     */
    protected $_acl;

    protected $_resource;

    public function __construct() {
        $this->_acl = new Acl();
    }

    public function init() {
        $config = \Zend_Registry::get('config');
        if ($config['site']['is_disabled'] && $this->getRequest()->getControllerName() != self::SITE_DISABLED_CONTROLLER) {
            \Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoRoute(
                array(
                    'controller' => self::SITE_DISABLED_CONTROLLER,
                    'action' => self::SITE_DISABLED_ACTION,
                ),
                ''
            );
        }

        $this->_resource = $this->getRequest()->getModuleName() . ':' . $this->getRequest()->getControllerName();

        if (!$this->_acl->has($this->_resource)) {
            $this->_acl->add(new \Zend_Acl_Resource($this->_resource));
        }
    }
    
    protected function bypassBeta() {
        $betaSession = new \Zend_Session_Namespace('bypass_beta_session');                
        if(isset($betaSession->bypass)) return $betaSession->bypass;
        return false;
    }

    public function  preDispatch() {
        
        $session = \Zend_Registry::get('referrer_tracking_session');
        $identity = \Zend_Auth::getInstance()->getIdentity();
        $role = Acl::ROLE_ANONYMOUS;
        if ($identity) {
            $role = $identity instanceof \Application_Model_DummyUser ? Acl::ROLE_TMP : Acl::ROLE_REGISTERED ;
            if ($identity->getPermissionLevel() > 0) {
                $role = Acl::ROLE_ADMIN;
            }
        }

        $privilege = $this->getRequest()->getActionName();


        if (!$this->_acl->isAllowed($role, $this->_resource, $privilege)) {

            //if($role==Acl::ROLE_ANONYMOUS_TEMP && $this->bypassBeta() && $this->_acl->isAllowed(Acl::ROLE_ANONYMOUS, $this->_resource, $privilege)) return;

            $no_redirect = array("/user/login","/user/sign-up","/user/link-accounts","/user/social-sign-up","assets/");
            $redirect=$this->getRequest()->getRequestUri();
            $flag= true;
            foreach($no_redirect as $value) {
                if(strpos($redirect,$value)!==false) $flag = false;
            }
            if(strpos($redirect, 'api/') === false && $flag === true) {
                    $session->referrer = $redirect;
            }
            
            if (!$identity) {
                if($param=$this->checkCMPparam()) { //logic for handling redirection to login with cmp tracking
                    $session->referrer = str_replace($param,"",$this->getRequest()->getRequestUri());                    
                    \Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoUrl(self::AUTHENTICATION_CONTROLLER."/".self::AUTHENTICATION_ACTION.$param);
                    return; 
                }    
                \Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')
                    ->gotoRoute(
                        array(
                            'controller' => self::AUTHENTICATION_CONTROLLER,
                            'action' => self::AUTHENTICATION_ACTION
                        ),
                        'default'
                    );
            }
        }
    }
    
    private function checkCMPparam() {
        if($uri=$this->getRequest()->getRequestUri()) {
            $arrTemp = explode("cmp=",$uri);
            if(count($arrTemp)==2) {
                return "?cmp=".$arrTemp[1];
            }
            return false;
        }
        return false;
    }
}
