<?php 
class RGA_Controller_Plugin_Ssl extends Zend_Controller_Plugin_Abstract {

    /**
     * Check the application.ini file for security settings.
     * If the url requires being secured, r ebuild a secure url
     * and redirect.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        $rebuildUrl = false;
        $module = $request->module;
        $controller = $request->controller;
        $action = $request->action;
        $config = Zend_Registry::get('config');

        // keep the scheme for the webservice module.
        if ($module == 'webservice') {
            return;
        }

        if (@$config['modules'][$module]['require_ssl']
            || @$config['modules'][$module][$controller]['require_ssl']
            || @$config['modules'][$module][$controller][$action]['require_ssl']){
            
            $rebuildUrl = true;
        }
        $this->_rebuildUrl($request, $rebuildUrl);
    }

    /**
     * Rebuild url if needed according to the given schema
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param boolean $secure if set to true rebuild url to a secure schema
     * @return void
     */
    protected function _rebuildUrl( Zend_Controller_Request_Abstract $request, $secure = true){

        $server = $request->getServer();
        $hostname = @$server['HTTP_HOST'];
        if ($secure) {
            $rebuild = false;
            if (APPLICATION_ENV == 'production') {
                $rebuild = !getenv('REQUEST_IS_SECURE');
            } else {
                $rebuild = $server['SERVER_PORT'] == 80;
            }

            if ($rebuild) {
                //url scheme is not secure so we rebuild url with secure scheme
                $newUrl = "https://" . $hostname . $request->getPathInfo();
                $query = $request->getQuery();
                if ($query) {
                    $newUrl .= '?' . http_build_query($query);
                }
            }    
        }
        if (@$newUrl) {
            $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
            $redirector->setGoToUrl($newUrl);
            $redirector->redirectAndExit();  
        }   
    }
}