<?php

namespace RGA;

class Controller_Plugin_i18n extends \Zend_Controller_Plugin_Abstract {

    public $i18nDir;
    public $supportedLanguages;
    protected $_languages;
    protected $_lang;

    public function __construct($i18nDir, array $supportedLangs) {
        $this->i18nDir = $i18nDir;
        $this->supportedLanguages = $supportedLangs;
        $this->_languages = \Zend_Registry::get('languages');
    }

    public function dispatchLoopStartup(\Zend_Controller_Request_Abstract $request) {
        $this->setLang($request->getParam('lang'));

        $request_name = $request->getModuleName();

        if ( !$request_name ) {
            $request_name = 'app';
        }

        $namespace = $request_name . '.module/_' .
            $request->getControllerName() . '/_' .
            $request->getActionName() . '/' .
            'view';

        $translations = @$this->_languages[$this->_lang][$namespace] ?: array(
            'token' => 'translation'
        );

        // foreach ($this->_languages as $key => $value) {
        //     var_dump($key);
        //     echo " ";
        // }
        
        // echo "<br/>\n";
        // var_dump($this->_lang);
        // echo "<br/>\n";
        // var_dump($translations);
        // echo "<br/>'\n";
        // var_dump($this->_languages[$this->_lang][$namespace]);
        // echo "<br/>'\n";
        // var_dump($request_name);
        // die();

        $locale = new \Zend_Locale($this->_lang);
        $translate = new \Zend_Translate('array', $translations, $this->_lang);
        \Zend_Registry::set('locale', $locale);
        \Zend_Registry::set('Zend_Translate', $translate);
    }

    public function preDispatch(\Zend_Controller_Request_Abstract $request) {
    }

    public function setLang($lang) {
        if (!in_array($lang, $this->supportedLanguages)) {
            $this->_lang = 'en';
        } else {
            $this->_lang = $lang;
        }
    }

    public function getLang() {
        return $this->_lang;
    }

}
