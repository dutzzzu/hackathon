<?php

class RGA_Route_SuperpowerMlp extends Zend_Controller_Router_Route
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

        $superpower = new Application_Model_SolrLifemapSuperpower();
        $superpowerPage = $superpower->getSuperpowerMLPPageBySlug($path);

        if (count($superpowerPage) >= 1) {

            $array['superpowerpage'] = reset($superpowerPage);
            $values = $this->_defaults + $array;

            $values['controller'] = 'superpower-landing-page';
            $values['action'] = 'superpower';
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