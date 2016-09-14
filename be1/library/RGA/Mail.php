<?php
namespace RGA;
use \Application_Model_User as User;
use \Application_Model_Notification as Notification;
class Mail {

    protected $_type;
    protected $_sender;
    protected $_recipient;
    protected $_notification;
    protected $_data;
    protected $_view;
    protected $_subject;
    protected $_body;
    protected $_mailQueue;


    public function __construct(User $sender, User $recipient, Notification $notification, $data = array()) {
        $this->_type = $notification->type;
        $this->_sender = $sender;
        $this->_recipient = $recipient;
        $this->_notification = $notification;
        $this->_data = $data;
        $this->_mailQueue = \Zend_Registry::get('MailQueue');
        $viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        if (null === $viewRenderer->view) {
            $viewRenderer->initView();
        }
        
        $this->_view = $viewRenderer->view;
        $this->_view->sender = $this->_sender;
        $this->_view->recipient = $this->_recipient;
        $this->_view->notification = $this->_notification;
        $this->_view->data = $this->_data;
        $this->_processEmailTemplate();

    }

    public function send() {
        $mail = new \Zend_Mail();
        $mail->setBodyHtml($this->_body);
        $conf = \Zend_Registry::get('config');
        $mail->setFrom($conf['site']['sender_email_address'], 'AARP - Life Reimagined');
        

        $mail->addTo($this->_recipient->email, $this->_recipient->getFullName());
        $mail->setSubject($this->_subject);
        try {
            $this->_mailQueue->send($mail);
            error_log("Email from {$this->_sender->email} to: {$this->_recipient->getFullName()}<{$this->_recipient->email}>");
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
        
        return $this;
    }

    protected function _processEmailTemplate() {
        $tpl = explode("\n", $this->_view->backboneView('app.module/email/' . str_replace('_', '-', $this->_type) . '/view')->render(false));
        $this->_subject = $tpl[0];
        unset($tpl[0]);
        $this->_body = implode("\n", $tpl);
    }
}
