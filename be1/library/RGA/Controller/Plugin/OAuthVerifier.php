<?php
namespace RGA;
use \RGA\Auth\Adapter as AuthAdapter;
use Application_Model_User as User;
use RGA_OAuth_Provider as Provider;

class Controller_Plugin_OAuthVerifier extends \Zend_Controller_Plugin_Abstract {
    private $_is_oauth = 0;
    private $_authentication;

    public function preDispatch(\Zend_Controller_Request_Abstract $request) {

        if ($this->getRequest()->getParam('oauth_signature', false)) {
            $this->_is_oauth = 1;
        }

        $this->_authentication = \Zend_Auth::getInstance();
        $ident = $this->_authentication->hasIdentity() ? 'true' : 'false';

        if (!$this->_authentication->hasIdentity()) {

            $resource = $request->getModuleName() . ':' . $request->getControllerName();
            $apiEndpoints = array(
                'api:error',
                'api:i18n',
                'api:auth',
                'api:user',
                'api:cache',
                'api:login-status',
                'api:tracking',
                'api:invite',
                'api:captcha',
                'api:email',
                'api:activity',
                'api:activity-result',
                'api:activity-stat',
                'api:stories',
                'api:user-meta',
                'api:newsletter-subscriptor',
                'api:event-invite',
                'api:event',
                'api:event-notification',
                'api:solrapi',
                'api:menc',
                'api:shares',
                'api:third-party-file',
                'api:challenge',
                'api:marketing-landing',
                'api:institute',
                'api:guidehub',
                'api:guidehub-resources',
                'api:wof',
                'api:quiz',
                'api:contact-form',
                'api:how-it-works',
                'api:hello-world',
                'api:event-new',
                'api:activity-app',
                'api:app-progress',
                'api:failed-auth',
                'api:help',
                'api:terms-privacy',
                'api:library',
                'api:purpose-store',
                'api:purpose-statement',
                'api:user-purpose-statement',
                'api:webhook',
                'api:lifemap-superpower',
                'api:csrf',
                'api:challenge-json',
                'api:challenge-v4-versioning',
                'api:search',
                'api:userchatdetails',
                'api:pseudo-auto-apply-subscription-coupon',
                'api:newsletter-coi-resend'
            );

            if (!in_array($resource, $apiEndpoints)) {
                $provider = new Provider(Provider::TOKEN_VERIFY);
                $response = $provider->checkOAuthRequest();
                //error_log('Check OAuth Request response (' . $resource . '): ' . print_r($response,1));
                if ($response !== true) {
                    die($response);
                }
                $this->oauth_consumer = $provider->getConsumer();
                //error_log('OAuth Consumer Scope: ' . print_r($this->oauth_consumer->scope,1));
                $id = $provider->getUserId();
                $user = User::find(new \MongoId($id));
                if (!$user) {
                    //error_log('Controller_Plugin_OAuthVerifier:35 - User not found');
                    die();
                }
                $adapter = new AuthAdapter();
                $adapter->setIdentity($user);
                $this->_authentication->authenticate($adapter);

            }

        }
    }


    public function  dispatchLoopShutdown() {
        if ($this->_is_oauth) {
            $this->_authentication->clearIdentity();
        }

    }
}
