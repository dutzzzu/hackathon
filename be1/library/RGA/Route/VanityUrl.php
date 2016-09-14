<?php

class RGA_Route_VanityUrl extends Zend_Controller_Router_Route
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

        $vanity = new Application_Model_SolrVanityUrl();
        $vanityPage = $vanity->get($path);

        if (count($vanityPage) >= 1) {
            $array['vanity_url'] = reset($vanityPage);
            $values = $this->_defaults + $array;

            $values['controller'] = 'vanity-url';
            $values['action'] = 'index';
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