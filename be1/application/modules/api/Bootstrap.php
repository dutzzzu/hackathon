<?php
/**
 * Description of Bootstrap
 */
class Api_Bootstrap extends Zend_Application_Module_Bootstrap
{
    protected function _isOnlyApiBootstrap() {
        return (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false);
    }

    public function _initREST()
    {
        $frontController = \Zend_Controller_Front::getInstance();

        if ($this->_isOnlyApiBootstrap())
        {
            // register the RestHandler plugin
            $frontController->registerPlugin(new \REST_Controller_Plugin_RestHandler($frontController));

            // add REST contextSwitch helper
            $contextSwitch = new \REST_Controller_Action_Helper_ContextSwitch();
            \Zend_Controller_Action_HelperBroker::addHelper($contextSwitch);

            // add restContexts helper
            $restContexts = new \REST_Controller_Action_Helper_RestContexts();
            \Zend_Controller_Action_HelperBroker::addHelper($restContexts);

            // set custom request object
            $frontController->setRequest(new REST_Controller_Request_Http);
            $frontController->setResponse(new REST_Response);

            // add the REST route for the API module only
            $restRoute = new Zend_Rest_Route($frontController, array(), array('api'));
            $frontController->getRouter()->addRoute('rest', $restRoute);
        }
    }

    public function _initRouting()
    {
        $frontController = \Zend_Controller_Front::getInstance();

        if ($this->_isOnlyApiBootstrap())
        {

        }
    }

    /*public function _initBasicAuth() {
        $fc = Zend_Controller_Front::getInstance();
        $fc->registerPlugin(
            new RGA\Controller_Plugin_BasicHttpAuth()
        );
    }*/
}
