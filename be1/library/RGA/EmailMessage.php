<?php
namespace RGA;
use \Application_Model_User as User;

class EmailMessage {

    protected $_sender;
    protected $_recipients;
    protected $_message;
    protected $_data;
    protected $_view;
    protected $_subject;
    protected $_body;
    protected $_mailQueue;
    protected $_typeOfSharing;

    public function __construct($sender, $recipients, $message, $data, $typeOfSharing) {
        $this->_sender = $sender;
        $this->_recipients = $recipients;
        $this->_message = $message;
        $this->_data = $data;
        $this->_mailQueue = \Zend_Registry::get('MailQueue');
        $this->_typeOfSharing = $typeOfSharing;

        // Initiate view
        $viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        if (null === $viewRenderer->view) {
            $viewRenderer->initView();
        }
        
        $this->_view = $viewRenderer->view;
        $this->_view->sender = $this->_sender;
        $this->_view->recipients = $this->_recipients;
        $this->_view->message = $this->_message;
        $this->_view->data = $this->_data;
        $this->_processEmailTemplate();
    }

    public function send() {
        $conf = \Zend_Registry::get('config');
        
        $mail = new \Zend_Mail('UTF-8');
        $mail->setBodyHtml($this->_body);        
        $mail->setFrom($conf['site']['sender_email_address'], 'AARP - Life Reimagined');
        $mail->setSubject($this->_subject);

        foreach ($this->_recipients as $val) {
            $mail->addTo($val);
        }
        
        try {
            $this->_mailQueue->send($mail);
            if (is_object($this->_sender)){
                error_log("Email from {$this->_sender->email} to: {$this->_recipients}");
            }else{
                error_log("Email from {$this->_sender} to: {$this->_recipients}");
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
        
        return $this;
    }

    protected function _processEmailTemplate() {
        switch ($this->_typeOfSharing){
            case 'article':
            case 'stories_landing':
                isset($this->_sender->email) ? $str = 'in' : $str = 'out';
                $url = 'app.module/email/share-logged-'.$str.'/view';
            break;
            case 'activity_result':
                $url = 'app.module/email/share-activity-result/view';
            break;    
        }

        $tpl = explode("\n", $this->_view->backboneView($url)->render(false));
        $this->_subject = $tpl[0];
        unset($tpl[0]);
        $this->_body = implode("\n", $tpl);
    }
}