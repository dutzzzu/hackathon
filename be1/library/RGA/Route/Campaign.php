<?php

class RGA_Route_Campaign extends Zend_Controller_Router_Route
{
    public function __construct($route, $defaults = array())
    {
        $this->_route = trim($route, $this->_urlDelimiter);
        $this->_defaults = (array)$defaults;
    }

    public function match($path, $partial = true)
    {
        if ($path instanceof Zend_Controller_Request_Http) {
            $path = $path->getPathInfo();
        }

        $path = trim($path, $this->_urlDelimiter);
        $pathBits = explode($this->_urlDelimiter, $path);

        if (empty($path)) {
            return false;
        }

        if (count($pathBits) > 1) {
            $path = $pathBits[0];
        }

        $campaign = new Application_Model_SolrCampaign();
        $foundCampaign = $campaign->getCampaignBySlug($path);

        if (count($foundCampaign) >= 1) {
            $array['campaign'] = reset($foundCampaign);
            $values = $this->_defaults + $array;

            $values['controller'] = 'general-campaign';
            $values['action'] = 'index';

            if(!empty($pathBits[2]) && $pathBits[2] == 'quiz-result'){
                $values['nid'] = $pathBits[1];
                $values['qid'] = $pathBits[3];
                $values['controller'] = 'quiz-me';
                $values['action'] = 'results';
            }
            if($arrValues=$this->checkIfLifemapGo($path,$values)) $values = array_merge($values,$arrValues); //if path is matched as being path from campaign for lifemap-go, redirects to lifemap-go controller with campaign data

            return $values;
        }

        return false;
    }

    public function assemble($data = array(), $reset = false, $encode = false)
    {
        /*
         * @TODO : return campaign data
         * should be in $data['campaign'] kind of thing
         */
    }

    private function checkIfLifemapGo($path,$values) {
        $arrReturn = array();
        if(isset($values["campaign"]) && !empty($values["campaign"]["field_lifemap_go_slug__s"]) && $values["campaign"]["field_lifemap_go_slug__s"]==$path) {
            $arrReturn["controller"] = "lifemap-mlp";
            return $arrReturn;
        }
        return false;
    }
}