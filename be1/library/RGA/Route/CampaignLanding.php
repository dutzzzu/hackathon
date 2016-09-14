<?php

class RGA_Route_CampaignLanding extends Zend_Controller_Router_Route
{
    public function __construct($route, $defaults = array())
    {
        $this->_route = trim($route, $this->_urlDelimiter);
        $this->_defaults = (array)$defaults;
    }

    public function match($path, $partial = false)
    {
        if ($path instanceof Zend_Controller_Request_Http) {
            $path = $path->getPathInfo();
        }

        $path = trim($path, $this->_urlDelimiter);
        $pathBits = explode($this->_urlDelimiter, $path);

        if(empty($path)){
            return false;
        }

        if (count($pathBits) != 1) {
            return false;
        }

        $campaign = new Application_Model_SolrCampaign();
        $foundCampaign = $campaign->getCampaignByLandingSlug($path);

        if (count($foundCampaign) >= 1) {
	    $array['campaign'] = reset($foundCampaign);
            $values = $this->_defaults + $array;

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
}