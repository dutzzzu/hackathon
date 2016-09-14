<?php
class RGA_Drupal_Image_Helper {

    public static function getImageUrl($url, $style = false) {
        $conf = Zend_Registry::get('config');
        $drupalUrl = $conf['drupal']['baseurl'];
        $replacement = $style ? $drupalUrl . '/sites/default/files/styles/' . $style . '/public/' : $drupalUrl . '/sites/default/files/';
        if($conf['cdn']['enabled'])
        {
            $helper = \Zend_Controller_Action_HelperBroker::getStaticHelper('fileCdn');
            $replacement = $helper->parseFileUrl($replacement);
        }
        return str_replace('public://', $replacement, $url);
    }

    public static function getToutImageUrl($url, $style = false, $alt = '', $title = '') {
        $conf = Zend_Registry::get('config');
        $drupalUrl = $conf['drupal']['baseurl'];
        $replacement = $style ? $drupalUrl . '/sites/default/files/' : $drupalUrl . '/sites/default/files/';
        if($conf['cdn']['enabled'])
        {
            $helper = \Zend_Controller_Action_HelperBroker::getStaticHelper('fileCdn');
            $replacement = $helper->parseFileUrl($replacement);
        }
        $img_src = str_replace('public://', $replacement, $url);
        return array("img_src" => $img_src, "img_alt" => $alt, "img_title" => $title);
    }

    public static function getBiosImageUrl($url, $style = false, $alt = '', $title = '') {
        $conf = Zend_Registry::get('config');
        $drupalUrl = $conf['drupal']['baseurl'];
        $replacement = $style ? $drupalUrl . '/sites/default/files/' : $drupalUrl . '/sites/default/files/';
        if($conf['cdn']['enabled'])
        {
            $helper = \Zend_Controller_Action_HelperBroker::getStaticHelper('fileCdn');
            $replacement = $helper->parseFileUrl($replacement);
        }
        $img_src = str_replace('public://', $replacement, $url);
        return /*array("img_src" => */$img_src/*, "img_alt" => $alt, "img_title" => $title)*/;
    }

    public static function getSimpleImageUrl($url, $img_type="") {
        $conf = Zend_Registry::get('config');
        $drupalUrl = $conf['drupal']['baseurl'].'/sites/default/files/';
        if($conf['cdn']['enabled'])
        {
            $helper = \Zend_Controller_Action_HelperBroker::getStaticHelper('fileCdn');
            $drupalUrl = $helper->parseFileUrl($drupalUrl);
        }
        $img_src = str_replace('public://', $drupalUrl, $url);
        return preg_replace("/^(.*)\.([a-zA-Z]+)$/","\\1$img_type.\\2",$img_src);
    }    
}