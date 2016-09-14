<?php

class RGA_Route_DynamicRoute extends Zend_Controller_Router_Route
{
    private $routes;
    private $controller;
    private $action;

    public function __construct($route, $defaults = array())
    {
        $this->routes = $route;
//        $this->_route = trim($route, $this->_urlDelimiter);
        $this->_defaults = (array)$defaults;
    }

    private function getRoute($result, $path)
    {
        if(!empty($result)) {
            $key = key($result);
            $result = reset($result);

            foreach ($this->routes as $route) {
                if (in_array($key, $route['entity']['content_type'])) {
                    $this->controller = $route['controller'];
                    $this->action = $route['action'];
                    if (!in_array('*', array_values($route['field-map']))) {
                        foreach ($route['field-map'] as $k => $v) {
                            $this->values[$k] = $result[$v];
                        }
                    } else {
                        $key = array_keys($route['field-map']);
                        $key = reset($key);
                        $this->values[$key] = $result;
                    }
                    if(!empty($route['postProcess'])) {
                        if (method_exists($this, $route['postProcess'])) {
                            $this->{$route['postProcess']}($route, $path);
                        }
                    }
                }
            }

            if (!empty($this->controller) && !empty($this->action)) {
                return true;
            }
        }

        return false;
    }

    public function match($path, $partial = true)
    {

        if ($path instanceof Zend_Controller_Request_Http) {
            $path = $path->getPathInfo();
        }

        $path = trim($path, $this->_urlDelimiter);
        $completeUrl = $path;
        $pathBits = explode($this->_urlDelimiter, $path);

        if (empty($path)) {
            return false;
        }

        if (count($pathBits) > 1) {
            $path = $pathBits[0];
        }

        $test = new Application_Model_SolrDynamicRoute($this->routes);
        $result = $test->checkForSlug($path);

        $assignRoute = $this->getRoute($result, $completeUrl);

        if (!$assignRoute) {
            return false;
        }

        $values['controller'] = $this->controller;
        $values['action'] = $this->action;

        $values = array_merge($values, $this->values);

        return $values;
    }

    public function assemble($data = array(), $reset = false, $encode = false)
    {
        /*
         * @TODO : return campaign data
         * should be in $data['campaign'] kind of thing
         */
    }

    private function generalCampaignPostProcess($values, $path)
    {
        $pathBits = explode($this->_urlDelimiter, $path);
        if (!empty($pathBits[2]) && $pathBits[2] == 'quiz-result') {
            $this->values['nid'] = $pathBits[1];
            $this->values['qid'] = $pathBits[3];
            $this->controller = 'quiz-me';
            $this->action = 'results';
        }

        $campaign = reset($this->values);

        if(!empty($campaign['field_campaign_slug__s']) && strtolower($campaign['field_campaign_slug__s']) == strtolower($path)){
            $this->controller = 'general-campaign-landing-page';
            $this->action = 'index';
        }

        if(!empty($campaign['field_lifemap_go_slug__s'])) {
            if (strtolower($campaign['field_lifemap_go_slug__s']) == strtolower($path)) {
                $this->controller = 'lifemap-mlp';
            }
        }

        if (!empty($campaign['field_drphil_go_slug__s'])) {
            if (strtolower($campaign['field_drphil_go_slug__s']) == strtolower($path)) {
                $this->controller = 'drphil-go';
                $this->action = "index";
            }
        }

        /*if(isset($campaign['field_drphil_campaign__s']) && $campaign['field_drphil_campaign__s'] == 1 ) {

            $this->controller = 'drphil-go';
            $this->action = "index";

        }*/

    }

    private function superpowerPostProcess($values, $path){
        $superpower = reset($this->values);

        if(strtolower($superpower['field_slug_superpower_t']) == strtolower($path)){
            $this->action = 'superpower';
        }
    }

    private function registrationAndPaymentPostProcess($values,$path){
      $_SESSION['skip_coi'] = true;
      $pathBits = explode($this->_urlDelimiter, $path);
        if (!empty($pathBits[1]) && $pathBits[1] == 'register') {
            $this->action = 'register';
        }

        if (!empty($pathBits[2]) && $pathBits[2] == 'confirm') {
            $this->action = 'register-confirm';
        }
    }
}