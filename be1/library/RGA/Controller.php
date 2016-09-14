<?php

namespace RGA;
use \Application_Model_CmsChallengeCategory as ChallengeCategory;
use \Application_Model_Solr as Solr;
use \Application_Model_Utils as Utils;
use Application_Model_CmsWof as Wof;
use Application_Model_User as User;
use Application_Model_UserPurchaseOrder as Purchase;
use Application_Model_CmsEcommerce as CmsEcommerce;
use Application_Model_SolrRegistrationForms as SolrRegistrationForms;
use Application_Model_SolrCampaign as SolrCampaign;
use Application_Model_UserBooking as UserBooking;
use Application_Model_SolrCoach as Coach;
use Application_Model_SolrEvent as Event;
use Application_Model_AddDrPhilAccess as drPhilAccess;
use Application_Model_ChallengeV4Access as challengeV4Access;
use Application_Model_Session as Session;
use Application_Service_Chat as ServiceChat;

class Controller extends \Zend_Controller_Action {

    protected $_identity;
    protected $_auth;
    protected $_config;
    protected $_isLoggedIn = false;
    protected $_oauthSession = null;
    protected $_dr_phil_suspended = false;
    protected $_dr_phil_finished = false;
    protected $_lifemapSuspended = false;

    protected $bundle_root_path = 'activity-app/flows/';
    //SECURITY: iframe map in order to allow iframe render from external sites
    protected $iframe_map = array("activity-app"=>"index","quiz"=>"app","goal-create-activity"=>"index");

    public function init() {
        @session_start();
        if(isset($GLOBALS["profiling"])) { $callers=debug_backtrace(); $GLOBALS["profiling"]->mark("APP controller start init",get_called_class(),$callers[1]['function']); }
        $this->keepMeSignedIn();
        $_SESSION["user_ip"] = Utils::_get_ip();
        $this->setBetaBypassSessionFlag();
        $this->_auth = \Zend_Auth::getInstance();
        $hasIdentity = $this->_auth->hasIdentity();
        $this->view->aarpFrontendInterface()->setRequest($this->getRequest(),$this->iframe_map);
        $this->view->aarpFrontendInterface()->setFlashMessages($this->_helper->FlashMessenger->getMessages());
        $this->view->assets_key = 'static-' . strtolower(APPLICATION_REV); //APPLICATION_ENV == 'development' ? 'assets' : 'static-' . strtolower(str_replace('-', '', APPLICATION_REV));

        $this->checkTimeForEvents();

        if ($hasIdentity) {
            //$this->_identity = $this->_auth->getIdentity();
            //$this->_identity = User::find($this->_auth->getIdentity()->getId()); //fix for stale user session
            $this->_identity = $this->getUserIdentity(); //handle also anonymous session

            if(!$this->hasBetaAccess()) {
                $this->_redirect("/user/login?beta_access=false");
                return false;
            }

            if ($this->_identity) {
                $this->_isLoggedIn = true;
                $this->view->aarpFrontendInterface()->setSessionUser($this->_identity);
                $this->view->permissionLevel = $this->_identity->getPermissionLevel();
                $this->markNotificationAsSeen();
                $this->setOverlayNotificationData();
            }

            $this->view->aarpFrontendInterface()->setCustomModel('next_booking',$this->getNextBooking());
        }

        if ($this->_isLoggedIn) {
            $this->view->isLoggedIn = $this->_isLoggedIn;
            $this->view->sessionUser = $this->_identity;

            //the idea is to not overwrite the picture attribute in session user since it will get saved in the db
            //this way, we only make sure that if there is no picture, we actually have the default value set.
            if (!$this->view->sessionUser->picture || !isset($this->view->sessionUser->picture->file_id)) {
                $this->view->sessionUser->picture = $this->view->sessionUser->getPicture('90x90');
            }

            $this->view->isAnonymous = $this->_identity instanceof DummyUser;
            $this->setOverlayNotificationData();

        }
        else if (in_array(APPLICATION_ENV, array('staging', 'production', 'livecloud'))) {
            // cache pages for logged out users
            \Zend_Registry::get('page_cache')->start();
        }
        //SECURITY: check if the controller and action should be allowed to be rendered in an iframe
        if(!array_key_exists($this->getRequest()->getControllerName(),$this->iframe_map) || $this->getRequest()->getActionName() != $this->iframe_map[$this->getRequest()->getControllerName()])
        {

            if(APPLICATION_ENV == 'production')
            {
                $this->getResponse()->setHeader('X-Frame-Options', 'SAMEORIGIN');
            }
            //SECURITY: create a hash for sending it to API calls to identify the source also can be used on forms
            $session_id=\Zend_Session::getId();

            $csrf=Session::one(array('sessionId' => $session_id));

            $CSRF_TOKEN = hash_hmac("sha256", $session_id, time());

            if (isset($csrf) && !empty($csrf)) {
                $csrf->csrfTokenNew = array($csrf->csrfTokenNew[1],$CSRF_TOKEN);
            }else{
                $csrf->csrfTokenNew = array("",$CSRF_TOKEN);
            }

            $this->view->aarpFrontendInterface()->setCustomModel("CSRF_TOKEN", $CSRF_TOKEN);
        }


        /**
         * If a user enters a page that sets a custom redirect session variable and than navigates away
         * then remove the redirect session (if this is not done in the next normal login the user gets redirected to that value)
         */
        $redirect=$this->getRequest()->getRequestUri();

        if(!$this->_identity && strpos($redirect, 'api/') === false){
            $aarp = $this->view->aarpFrontendInterface()->getAARPObject();
            if (empty($aarp->customModels) || empty($aarp->customModels->registration_form)) {
                $aarp->customModels->registration_form = $this->getRegistrationForm();
            }

            $redirect_session = new \Zend_Session_Namespace('redirect_session');

            $no_redirect = array("/user/login","/user/sign-up","/user/link-accounts","/user/social-sign-up","/user/external","assets/","quiz/","quiz-app/","challenge/","signature/");

            $flag= true;
            foreach($no_redirect as $value) {
                if(strpos($redirect,$value)!==false) $flag = false;
            }

            if( $flag === true) {
                $redirect_session->url = false;
                $redirect_session->on_boarding=false;
            }
        }

        $this->_oauthSession = new \Zend_Session_Namespace('third_party_consumer_session');

        /**
         * If the user navigates away from the external login clear the consumer form session
         */

        if (!$this->_isLoggedIn && !in_array($this->getRequest()->getControllerName(), array('user', 'oauth', 'account', 'error'))) {
            $this->_oauthSession->consumer = $this->_oauthSession->callback = null;
        }

        $this->view->isSubscribed = false;
        if($this->_isLoggedIn){
            $pay_data = Purchase::getLastSubscription((string)$this->_identity->getId());
            $payData = $pay_data->export();
            if(!empty($payData)){
                $this->view->isSubscribed = true;
            }
        }

        $this->_config = \Zend_Registry::get('config');
        $this->view->config = $this->_config;
        $this->view->work_url = $this->_config['workreimagined']['siteUrl'];
        $this->view->doctype('XHTML1_RDFA');  // this is for suppor open graph metas
        $this->view->headTitle($this->_config['site']['title']);
        $this->view->isSecure = $this->getRequest()->getScheme() === 'https';
        $this->view->headMeta('IE=edge,chrome=1', 'X-UA-Compatible', 'http-equiv', array(), 'SET');
        $this->view->headMeta()->appendName('google-site-verification', 'CDyVwLp-5ZYP3leMuLnkn-s10suBQ-7NWWMtsB15Mu4');
        $this->_handleFacebookNotifications();

        $this->ab_version = "b"; //modified this as per LDP-10112 (leave only photo version of the homepage)  //@$_COOKIE['ab_version'];

        $this->view->challengeCategory = \Application_Model_SolrChallengeCategory::getInstance()->get_categories();
        $this->view->aarpFrontendInterface()->setCustomModel("challenge_categories", $this->view->challengeCategory);
        $this->view->private_campaigns = $this->_helper->campaign->getUserPrivateCampaigns();
        $this->view->public_campaigns = $this->_helper->campaign->getPublicCampaigns();
        if(isset($GLOBALS["profiling"])) { $callers=debug_backtrace(); $GLOBALS["profiling"]->mark("APP controller end init",get_called_class(),$callers[1]['function']); }

        /**
         *
         * If date is greater than 1st October 2016, Dr.Phil functionality will be suspended
         *
         **/
        if ($access = \Application_Model_AccessSuspended::one(array("type" => 'drphil'))) {
            if (!empty($access->emails)) {
                $suspended = false;
                foreach ($access->emails as $email) {
                    if ($email == 'all') {
                        $suspended = true;
                        break;
                    }else if ($this->_identity && strpos($this->_identity->email, $email) !== false) {
                        $suspended = true;
                        break;
                    }
                }
                if ($suspended) {
                    date_default_timezone_set('UTC');
                    $drphil_popup_dismissed = false;
                    if ($this->_identity && \Application_Model_DrPhilProgress::one(array("user_id" => (string) $this->_identity->getId(), "popup_access_suspended_dismissed" => true))) {
                        $drphil_popup_dismissed = true;
                    }
                    if (time() >= strtotime('01/01/17')) $this->_dr_phil_finished = true;
                    $this->_dr_phil_suspended = true;
                    $this->view->aarpFrontendInterface()->setCustomModel("dr_phil_suspended", $this->_dr_phil_suspended);
                    $this->view->aarpFrontendInterface()->setCustomModel("dr_phil_popup_dismissed", $drphil_popup_dismissed);
                }
            }
        }

        $access = \Application_Model_AccessSuspended::one(array("type" => 'lifemap_subscription'));

        if(!empty($access)) {

            $access = (!empty($access)) ? $access->export() : null;
            $emails = array('all');
            if ($this->_identity) {
                $emails[] = $this->_identity->email;
            }

            if (count(array_intersect($access['emails'], $emails)) > 0) {
                $this->_lifemapSuspended = TRUE;
            }
        }

        $this->view->aarpFrontendInterface()->setCustomModel("lifemapSuspended", $this->_lifemapSuspended);


    }

    private function markNotificationAsSeen()
    {
        /*
        if(!empty($_COOKIE['seenNotification']) && $_COOKIE['seenNotification']){
            switch($_COOKIE['seenNotification']){
                case 'futureexpiry':
                    $user = $this->_identity;
                    $days = $_COOKIE['days'];
                    $notificationInfo = $user->notification_counter->export();
                    foreach($notificationInfo as &$nI){
                        if($days <= $nI['from'] && $days > $nI['to']){
                            $nI['seen'] = true;
                        }
                    }
                    $user->notification_counter = $notificationInfo;
                    $user->save();
                    setcookie('seenNotification','',time()-3600);
                    setcookie('days','',time()-3600);
                    break;
            }
        }
        */
    }

    private function setOverlayNotificationData()
    {
        if($this->_lifemapSuspended) return;
        $overrideNotification = (bool) $this->getRequest()->getParam('shownotificationoverlay');
        if(!$overrideNotification) {
            if (!isset($_COOKIE['checkForOverlay']) || !$_COOKIE['checkForOverlay']) { // check to cookie that applies when logging in
                return;
            }
        }

        $user = $this->_identity;

        $data = Purchase::getLastSubscriptionExpiredCoupon((string)$this->_identity->getId());

        if (!empty($data)) {
            foreach ($data as $item) { //gets last subscription (that is the most recent one)
                $user_order = $item;
                break;
            }
        }

        $order = (!empty($user_order) ? $user_order->export() : false);

        $discount_type = null;
        $bypassUsed = null;
        if(isset($order["subscription_log"]) && is_array($order["subscription_log"])) {
            foreach ($order["subscription_log"] as $payment) {
                if(!empty($payment["discount_type"])) {
                    $discount_type = $payment["discount_type"];
                }
                $paid = $payment["amount_paid"];
            }
        }
        if(in_array($discount_type,array('bypass','trial')) && $paid==0) {
            $bypassUsed = true;
        }

        $overlayDetails = new \stdClass();
        // trial not expired yet
        if ($bypassUsed) {
            if ($user_order->next_payment_date >= time() && $user_order->is_paid) { // just making sure the payment used is bypass (probably not necessary, but nice to have)
                $daysUntilEndOfTrial = floor(($user_order->next_payment_date - time()) / (60 * 60 * 24)) + 1; // get the number of days until trial expires
                if($user_order->coupon['cc_required']) return;
                if(date('d-M',$user_order->start_date) == date('d-M',time())) return;
//                if(in_array($daysUntilEndOfTrial,array(30,31))) return;
                $expireSoonLimits = array(
                    array(
                        'from' => 32,
                        'to' => 20
                    ),
                    array(
                        'from' => 20,
                        'to' => 10
                    ),
                    array(
                        'from' => 10,
                        'to' => 4
                    ),
                    array(
                        'from' => 4,
                        'to' => 1,
                    ),
                );
                $initialNotificationCounter = array();

                foreach ($expireSoonLimits as $cLimit) {
                    $initialNotificationCounter[] = array(
                        'from' => $cLimit['from'],
                        'to' => $cLimit['to'],
                        'seen' => false,
                        'drphil_seen' => false,
                    );
                }

                if (empty($user->notification_counter)) { // initialize notification counter, if the user does not have one
                    $notificationCounter = $initialNotificationCounter;
                } else {
                    $export = $user->export();
                    $notificationCounter = $export['notification_counter'];
                }

                $show = false;
                if (!$overrideNotification) {
                    foreach($notificationCounter as &$cLimit){
                        if($daysUntilEndOfTrial < $cLimit['from'] && $daysUntilEndOfTrial >= $cLimit['to']){
                            switch($user_order->product['field_package_type']){
                                case 'drphil_package':
                                    if(empty($cLimit['drphil_seen']) || (!empty($cLimit['drphil_seen']) && !$cLimit['drphil_seen'])) $show = true;
                                    break;
                                case 'lifemap_package':
                                    if(!$cLimit['seen']) $show = true;
                                    break;
                            }
                        }
                    }

                    $user->notification_counter = $notificationCounter;
                    $user->save();

                } else {
                    $show = true;
                }

                if ($show) {
                    $overlayDetails->show = true;
                    $overlayDetails->modalTitle = 'Important reminder';
                    $overlayDetails->contentTitle = 'Don\'t leave your subscription behind';
                    // the subscription expires one day after the next_payment_date
                    $overlayDetails->content = 'Your free trial expires on '
                        . date("n/j/y", strtotime('+1 day',$user_order->next_payment_date)) .
                        '. To keep rediscovering your best life, just enter your credit card information.';
                    $overlayDetails->feedbackButton = false;
                    if($user_order->product['field_package_type'] != 'drphil_package') {
                        $leftButton = new \stdClass();
                        $leftButton->url = '/select-plan';
                        $leftButton->label = 'Change plan';
                        $overlayDetails->leftButton = $leftButton;
                    }

                    $rightButton = new \stdClass();
                    $rightButton->url = '/place-order?autocheckout=true&single_item_id='.$user_order->product_nid;
                    $rightButton->label = 'Add Credit Card';
                    $rightButton->form = new \stdClass();
                    $overlayDetails->rightButton = $rightButton;
                    $overlayDetails->type = 'futureexpiry';
                    $overlayDetails->daysuntilexpiry = $daysUntilEndOfTrial;
                    $overlayDetails->plan_type = $user_order->product['field_package_type'];
                }
            }
        }

        if(!empty($user_order)){
            if(!$user_order->is_paid){ // the user didn't pay the subscription
                // check if he used a bypass or trial coupon

                // trial expired
                $coupon = \Application_Model_Coupon::all(
                    array(
                        'coupon_type'=> array(
                            '$in' => array('bypass')
                        ),
                        'user_id' => (string) $this->_identity->getId(),
                    )
                )->sort(array('created' => -1));
                $coupon = $coupon->export();
                $coupon = reset($coupon);

                if(!$coupon['active']){
                    if ($user_order->next_payment_date <= time())  { // trial expired
                        $show = false;
                        if(empty($user->expired_notification)){ // initialize counter for expired notification
                            $user->expired_notification = 0;
                            $user->expired_notification_dr_phil = 0;
                            $user->save();
                        }

                        if(!$overrideNotification) {
                            switch($user_order->product['field_package_type']){
                                case 'drphil_package':
                                    if ($user->expired_notification_dr_phil < 3) {
                                        $show = true;
                                    }
                                    break;
                                case 'lifemap_package':
                                    if ($user->expired_notification < 3) {
                                        $show = true;
                                    }
                                    break;
                            }

                        }

                        if($show) {
                            $overlayDetails->show = true;
                            $overlayDetails->modalTitle = 'Your free trial has expired';
                            $overlayDetails->contentTitle = 'Get the most out of your subscription';
                            $overlayDetails->content = 'Your subscription expired on ' . gmdate('n/j/y',
                                    $user_order->next_payment_date) . ', but we saved everything you\'ve created so far.
                                Now\'s time to invest in rediscovering your best you. Just enter your credit card
                                information to continue with your existing plan, or choose one of our other plans';
                            $overlayDetails->feedbackButton = true;
                            $export = $user_order->export();
                            $planName = (
                            ($export['product']['field_package_type'] == 'drphil_package') ? 'Dr. Phil' : 'Lifemap'
                            );
                            $this->view->aarpFrontendInterface()->setCustomModel('planName', $planName);

                            if ($user_order->product['field_package_type'] != 'drphil_package') {
                                $leftButton = new \stdClass();
                                $leftButton->url = '/select-plan';
                                $leftButton->label = 'Change plan';
                                $overlayDetails->leftButton = $leftButton;
                            }

                            // still to do add credit card info
                            $rightButton = new \stdClass();
                            $rightButton->url = '/place-order?single_item_id=' . $user_order->product_nid;
                            $rightButton->label = 'Continue with current plan';
                            $overlayDetails->rightButton = $rightButton;
                            $overlayDetails->type = 'expired';
                            $overlayDetails->plan_type = $user_order->product['field_package_type'];
                        }
                    }
                }
            }
        }

        $this->view->aarpFrontendInterface()->setCustomModel('showFreeTrialExpirationOverlay', $overlayDetails);
    }


    private function getNextBooking() {
        date_default_timezone_set('UTC');

        $userBookings = UserBooking::all(array("user_id" => (string)\Zend_Auth::getInstance()->getIdentity()->getId(), "endDate" => array('$gte' => time()), "action" => array( '$ne' => "canceled" )))->sort(array("startDate" => 1));
        $userBookings = array_values($userBookings->export());
        if ($userBookings) {
            $next_appointment = array();
            $generalConflict = false;
            $a = $userBookings[0];
            $b = isset($userBookings[1]) ? $userBookings[1] : false;
            if($b) {
                if (
                    ($a['startDate'] <= $b['startDate'] && $a['endDate'] > $b['startDate']) ||
                    ($b['startDate'] <= $a['startDate'] && $b['endDate'] > $a['startDate'])
                ) {
                    $generalConflict = true;
                }
            }
            if(!$generalConflict){
                $user_booking = reset($userBookings);
            }
            else{
                foreach($userBookings as $booking){
                    if(empty($booking['workshopOnline'])) {
                        $user_booking = $booking;
                        break;
                    }
                }
            }
            if (!empty($user_booking)) {
                $next_appointment['startDate'] = $user_booking['startDate'];
                $next_appointment['endDate'] = $user_booking['endDate'];
                if (empty($user_booking['workshopOnline'])) {
                    $coach = new Coach();
                    $coach_data = $coach->getCoachByCalendarId($user_booking['calendarID']);
                    $next_appointment['coach_name'] = $coach_data[0]['title_s'];
                    $next_appointment['coach_avatar'] = $coach_data[0]['field_coach_avatar_small__s'];
                    if (!empty($user_booking['blueJeansMeetingId'])) {
                        $blueJeans = \Zend_Controller_Action_HelperBroker::getStaticHelper('blueJeans');
                        $next_appointment['coach_bluejeans'] = $blueJeans::URL . '/' . $user_booking['blueJeansMeetingId'];
                    } else {
                        $next_appointment['coach_bluejeans'] = $coach_data[0]['field_blue_jeans_url__s'];
                    }
                    if (strpos($next_appointment['coach_bluejeans'], '://') === false) {
                        $next_appointment['coach_bluejeans'] = 'https://' . $next_appointment['coach_bluejeans'];
                    }
                    $next_appointment['booking_id'] = $user_booking['id'];
                    $next_appointment['showCoachSessionBanner'] = empty($user_booking['hideCoachSessionBanner']) ? true : false;
                }

                $next_appointment['startsIn'] = $user_booking['startDate'] - time();
            }
            return $next_appointment;
        }

        return null;
    }


    /** Customizing the authentication-> reload on authentication success. This is needed for some pages */
    protected function reloadOnAuthentication() {
        $aarp = $this->view->aarpFrontendInterface()->getAARPObject();
        $aarp->customModels->registration_form[0]["reload_on_authentication"] = true;
    }

    /** Customizing the authentication -> redirect to a link on success. This is needed for some pages */
    protected function redirectLinkOnAuthentication($url) {
        $aarp = $this->view->aarpFrontendInterface()->getAARPObject();
        $aarp->customModels->registration_form[0]["redirect_link"] = $url;
    }

    /** Set a flag fromMLP on RegistrationForm - only for lifemap-go and drphil-go */
    protected function setFlagFromMlpOnRegistrationForm($mlp) {
        $aarp = $this->view->aarpFrontendInterface()->getAARPObject();
        $aarp->customModels->registration_form[0]["from_mlp"] = $mlp;
    }

    /** Set the Registration Form customModel for the UI */
    protected function getRegistrationForm($registration_form_nid = false) {
        $solr_registration = new SolrRegistrationForms();

        $registration_form = $solr_registration->getRegistrationForm($registration_form_nid);

        $helper_registration_forms = \Zend_Controller_Action_HelperBroker::getStaticHelper('registration_forms');
        return $helper_registration_forms->handleRegistrationForms($registration_form);
    }

    private function keepMeSignedIn() {
        $helper = \Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $helper->keepMeLogin();
    }

    protected function handleUserRedirects($params=array()) {
        $this->_auth = \Zend_Auth::getInstance();
        $this->_identity = $this->_auth->getIdentity();
        if(!$this->_identity) return "/user/login";

        $redirect = $this->_goToReferrer($params, true);

        return $redirect;
    }

    protected function setBetaBypassSessionFlag() {
        $this->_betaSession = new \Zend_Session_Namespace('bypass_beta_session');
        if($flag = $this->getRequest()->getParam("beta_bypass")) {
            if($flag=="true") $this->_betaSession->bypass = true;
            if($flag=="false") $this->_betaSession->bypass = false;
        }
    }

    protected function hasBetaAccess() {
        /*
        if(isset($this->_betaSession->bypass) && $this->_betaSession->bypass) {
            User::update(array("_id"=>$this->_identity->getId()),array("\$set"=>array("beta_access"=>true))); //will add the beta_access param with true value after a user is logged in or registered with beta_bypass=true param in URL querystring
            return true;
        }
        if(!$this->_identity->beta_access) {
            $this->_auth->clearIdentity();
            $this->_isLoggedIn = false;
            return false;
        }*/
        return true;
    }

    protected function _goToReferrer($params = array(),$do_return=false) { //for API calls redirection should check /src/library/RGA/ApiController.php -> _goToReferrer()
        $this->_auth = \Zend_Auth::getInstance();
        $hasIdentity = $this->_auth->hasIdentity();

        $session = \Zend_Registry::get('referrer_tracking_session');

        $redirect_session = new \Zend_Session_Namespace('redirect_session');
        $redirect = '/';
        if ($hasIdentity) {
            $redirect = '/dashboard';
        }

        if ($session->referrer && strpos($session->referrer, 'api/') === false) {
            $redirect = $session->referrer;
            $session->referrer = false;
        }

        if(isset($redirect_session->url) && $redirect_session->url) {
            $redirect = $redirect_session->url;
            $redirect_session->unsetAll();
        }


        if (@$this->_oauthSession->consumer->callback_url) {
            $redirect = $this->_oauthSession->consumer->callback_url;
        }

        if (@$this->_oauthSession->callback) {
            $redirect = $this->_oauthSession->callback;
        }

        if (is_array($redirect)) {
            $redirect=$this->view->url2($redirect);
        }

        if ($hasIdentity) {
            $no_redirect = array("/user/login", "/user/sign-up", "/user/link-accounts", "/user/social-sign-up");
            $default_redirect = '/dashboard';
            foreach ($no_redirect as $value) {
                if (strpos($redirect, $value) !== false) $redirect = $default_redirect;
            }
        }

        if (isset($params)) {
            $parts = explode("?", $redirect);
            foreach ($params as $k => $v) {
                if (!isset($parts[1])) {
                    $parts[1] = $k . "=" . $v;
                } else {
                    $parts[1] = implode("&", array($parts[1], $k . "=" . $v));
                }
            }
            $redirect = implode("?", $parts);
        }

        $redirect = $this->wofCheckRedirect($redirect);

        // make sure the redirect always has a forward slash
        $redirect = ltrim($redirect,'/');
        $redirect = '/'.$redirect;

        if($do_return) return $redirect;
        $this->_redirect($redirect);

    }

    public function wofCheckRedirect($redirect) {
        if (isset($_SESSION["faith_ref"]) && !empty($_SESSION["faith_ref"])) {
            $findme   = 'quiz-result';
            $pos = strpos($redirect, $findme);
            if($pos !== false){
                $redirect  = $_SESSION["faith_ref"];
                unset($_SESSION["faith_ref"]);
                return $redirect;
            }
        }
        return $redirect;
    }

    protected function _getReferrer() {
        $session = \Zend_Registry::get('referrer_tracking_session');
        if (@$this->_oauthSession->consumer->callback_url) {
            return $this->_oauthSession->consumer->callback_url;
        }
        if ($session->referrer && strpos($session->referrer, 'api/') === false) {
            return $session->referrer;
        }

        return null;
    }

    protected function _setSectionTitle($title) {
        $this->view->headTitle($title . ' - ', \Zend_View_Helper_Placeholder_Container_Abstract::PREPEND);
        $this->view->sectionTitle = $title;
    }

    protected function _getUrl() {
        return $this->view->serverUrl() . \Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
    }

    protected function _getOpenGraphDefaultImageUrl() {
        return $this->view->serverUrl() . $this->_config['site']['og']['image_url'];
    }

    protected function _setOpenGraphProperties($props) {

        $getURL = $this->_getUrl();
        if (strpos($getURL,'?') !== false){
            $aAux = explode('?', $getURL);
            $getURL = $aAux[0];
        }
        $urlvar = $getURL  . '?cmp=SN-FCBK-LR-SHARE';
        $image = $this->_getOpenGraphDefaultImageUrl();


        $props = $props + array(
                'og:site_name' => "Life Reimagined",
                'og:app_id' => "399624533537303",
                'og:title' => $this->_config['site']['title'],
                'og:url' => $urlvar,
                'og:image' => $image .'?c='. mt_rand (),
            );

        foreach($props as $attr => $value) {
            $this->view->headMeta($value, $attr, 'property', array(), 'SET');
        }
    }

    protected function _goToLogin() {
        $this->_redirect($this->view->url2(array('controller' => 'user', 'action' => 'login'), 'default'));
    }

    protected function _handleFacebookNotifications() {
        if (($ref = $this->getRequest()->getParam('ref')) && $ref === 'notif') {

            if ($request_ids = $this->getRequest()->getParam('request_ids')) {
                // example: http://phase2.aarp.local/?ref=notif&request_ids=265109263615320%2C132953183529829
                $this->_redirect('/invite/facebook/' . $request_ids);
            }

        }
    }

    /**
     * Check if a user is part of a campaign. If so, return the campaign he's part of.
     */
    public function _getUserCampaign() {
        $campaigns = \Application_Model_SolrCampaignNew::getInstance()->get_campaigns();
        $channels = array();
        if (!empty($campaigns)) {
            if (\Zend_Auth::getInstance()->hasIdentity()) {
                $id = \Zend_Auth::getInstance()->getIdentity()->getId();
                $user = User::one(array("_id" => $id));
                if (!empty($user->campaing)) {
                    foreach ($campaigns as $campaign) {
                        if ($user->campaing == $campaign['field_campaign_id']) {
                            return $campaign;
                        }
                    }
                }
                if (!empty($user->channel)) {
                    foreach ($user->channel as $channel) {
                        $channels[] = $channel;
                    }
                    $channels = array_reverse($channels);
                    foreach ($channels as $channel) {
                        foreach ($campaigns as $campaign) {
                            if ($channel == $campaign['field_channel']) {
                                return $campaign;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Checks if the user is a paying customer.
     * @param $data CmsEcommerce UserSubscription data
     * @param $user_id String ID of the user
     * @return bool representing whether the user is or isn't a paying customer
     */
    protected function getPayingStatus($data, $user_id) {
        return $this->getSubscriptionPackage($data, $user_id) != null;
    }

    /**
     * Get the Subscription that the user paid for
     * @param $data CmsEcommerce UserSubscription data
     * @param $user_id String ID of the user
     * @return Purchase subscription package or NULL
     */
    protected function getSubscriptionPackage($data, $user_id) {
        if (isset($data["field_plans"])) {
            $arrPackagesNids = array();
            foreach ($data["field_plans"] as $plan) {
                $arrPackagesNids[] = $plan["nid"];
            }
            return Purchase::getProductPaymentStatus($user_id, $arrPackagesNids);
        }
        return null;
    }

    private function getNextPaymentDate($user_id) {
        $order = Purchase::one(array('user_id' => (string)$user_id));
        if ($order) {
            return $order->next_payment_date;
        }
        return false;
    }

    public function postDispatch() {
        $helper = \Zend_Controller_Action_HelperBroker::getStaticHelper('responseParser');
        $arrProps = $helper->getObjectProps($this->view);

        foreach($arrProps as $prop) {
            if(isset($this->view->{$prop}))
                $this->view->{$prop} = $helper->parseResponseRecursive($this->view->{$prop});
        }

        if ($this->_identity) {
            \Application_Model_UserProgressLastUpdate::saveUserData((string)$this->_identity->getId());
        }
    }


    public function setSeo($nid, $defaults){


        $solr = new Solr();
        $data = $solr->_get_nodes_by_id($nid);
        $data = $data[0];

        if(isset($data["field_meta_tags"][0]["field_meta_keywords_NAME"])) {
            $keywords_arr = @$data["field_meta_tags"][0]["field_meta_keywords_NAME"];
            $keywords = array();
            if(is_array($keywords_arr)){
                foreach ($keywords_arr as $key) {
                    $key_arr = explode("|", $key);
                    $keywords[] = $key_arr[0];
                }
                $keywords = trim(implode(",", $keywords));
            }
            else $keywords = '';
        }else{
            $keywords = FALSE;
        }

        $seo_data = array();
        $seo_data['meta_title'] = (isset($data["field_meta_tags"][0]["field_meta_title"])) ? $data["field_meta_tags"][0]["field_meta_title"] : strip_tags($data[$defaults["default_meta_title"]]);
        $seo_data['meta_description'] = (isset($data["field_meta_tags"][0]["field_meta_description"])) ? $data["field_meta_tags"][0]["field_meta_description"] : strip_tags($data[$defaults["default_meta_description"]]);
        if(isset($data["field_meta_tags"][0]["field_meta_author"])) {
            $seo_data['meta_author'] = @$data["field_meta_tags"][0]["field_meta_author"];
        }
        if(isset($keywords) && strlen($keywords) > 2) {
            $seo_data['meta_keywords'] = $keywords;
        }
        if(isset($data["field_meta_tags"][0]["field_meta_canonical_url"])) {
            $seo_data['meta_canonical'] = @$data["field_meta_tags"][0]["field_meta_canonical_url"];
        }
        if(isset($data["field_og_tags"][0]["field_og_title"])) {
            $seo_data['og_title'] = @$data["field_og_tags"][0]["field_og_title"];
        }
        if(isset($data["field_og_tags"][0]["field_og_image_large"])) {
            $seo_data['og_image'] = @$data["field_og_tags"][0]["field_og_image_large"];
        }
        if(isset($data["field_og_tags"][0]["field_og_type"])) {
            $seo_data['og_type'] = @$data["field_og_tags"][0]["field_og_type"];
        }
        if(isset($data["field_og_tags"][0]["field_og_url"])) {
            $seo_data['og_url'] = @$data["field_og_tags"][0]["field_og_url"];
        }
        if(isset($data["field_og_tags"][0]["field_og_description"])) {
            $seo_data['og_description'] = @$data["field_og_tags"][0]["field_og_description"];
        }

        $meta_title = isset($seo_data['meta_title']) ? $seo_data['meta_title'] : '';
        $meta_description = isset($seo_data['meta_description']) ? $seo_data['meta_description'] : '';
        $meta_author = isset($seo_data['meta_author']) ? $seo_data['meta_author'] : '';
        $meta_keywords = isset($seo_data['meta_keywords']) ? $seo_data['meta_keywords'] : '';
        $meta_canonical = isset($seo_data['meta_canonical']) ? $seo_data['meta_canonical'] : '';
        $og_title = isset($seo_data['og_title']) ? $seo_data['og_title'] : '';
        $og_image = isset($seo_data['og_image']) ? $seo_data['og_title'] : '';
        $og_type = isset($seo_data['og_type']) ? $seo_data['og_type'] : '';
        $og_url = isset($seo_data['og_url']) ? $seo_data['og_url'] : '';
        $og_description = isset($seo_data['og_description']) ? $seo_data['og_description'] : '';

        (strlen($meta_title) > 1) ? $this->_setSectionTitle($meta_title) : FALSE;
        (strlen($meta_description) > 1) ? $this->view->headMeta()->appendName('description', $meta_description) : FALSE;
        (strlen($meta_author) > 1) ? $this->view->headMeta()->appendName('author', $meta_author) : FALSE;
        (strlen($meta_keywords) > 1) ? $this->view->headMeta()->appendName('keywords', $meta_keywords) : FALSE;

        if(isset($meta_canonical)) {
            $this->view->headLink(array(
                'rel'  => 'canonical',
                'href' => $meta_canonical
            ), 'PREPEND');
        }

        $this->_setOpenGraphProperties(array(
            'og:title' => $og_title,
            'og:image' => $og_image,
            'og:description' => $og_description,
            'og:type' => $og_type,
            'og:url' => $og_url
        ));

    }


    /**
     * @param $drphil
     * @param $challenge_v4
     * @param null $enviroment If the enviroment is not passed he will be by default null and the methid disabled
     * @throws \Exception if no param are passed this will give an exception
     */
    public function functionalityAccess($drphil, $challenge_v4,$enviroment = null){
        if ($enviroment === 'production') {
            $this->setAccess($drphil, $challenge_v4);
        } else {
            //for the moment we want to do not have acces only on production servers
            return;
        }
    }

    /**
     * @param $drphil Verify is the enviroment is or not drphil
     * @param $challlenge_v4 Verify is the enviroment is Challnege V4
     * @throws \Exception If none of the enviroment provided trow exception
     *
     * This function will be used until the final deploy of challenge V4
     * The method is to restrict the  acces on the functionality
     * Only the members from
     */
    public function setAccess($drphil, $challlenge_v4){
        if($drphil){
            $emails=drPhilAccess::all();
        } else if ($challlenge_v4) {
            $emails=challengeV4Access::all();
        } else {
            throw new \Exception('Module not provided');
        }
        if ($this->_identity) {
            $access = false;
            foreach($emails as $email){
                if($email->email == $this->_identity->email){
                    $access = true;
                }
            }
            if($access == false){
                $this->_redirect("user/login");
            }
        }
        else {
            $this->_redirect("user/login");
        }
    }

    public function getUserIdentity() {
        return $this->_auth->getIdentity(); //as per LDP-16947; after moving the session handling to Memcached, it seems the user session date is updated correctly
    }

    public function refreshUserCoiStats($email_type) {
        if(!$this->_identity) return; //only for logged-in users

        $user_object_marshalled = $this->_identity->export();
        $user_object_marshalled['id'] = $user_object_marshalled['_id']->{'$id'};

        list($coi_reminder_display,$coi_reminder_type) = \Application_Model_UserCoi::getCoiReminderDisplayStatusAndType($user_object_marshalled,$email_type);
        $this->view->aarpFrontendInterface()->pushToSessionUser("coi_reminder_display",$coi_reminder_display);
        $this->view->aarpFrontendInterface()->pushToSessionUser("coi_reminder_type",$coi_reminder_type);
    }

    public function checkTimeForEvents(){
        //show event for 4 july
        $dateA = '2016-07-03 23:59';
        $dateB = '2016-07-04 23:59';
        if((strtotime($dateA) < $_SERVER['REQUEST_TIME']) and  ($_SERVER['REQUEST_TIME'] < strtotime($dateB))){
            $this->view->aarpFrontendInterface()->setCustomModel('show_4_july', true);
        } else {
            $this->view->aarpFrontendInterface()->setCustomModel('show_4_july', false);
        }
    }

    public function getChatDetails($type, $hide = false)
    {
        $serviceChat = new ServiceChat();
        $chatDetails = $serviceChat->getChatDetails($type, $hide);
        $this->view->aarpFrontendInterface()->setCustomModel('chatDetails', $chatDetails);
        $userChatDetails = $serviceChat->getUserDetailsForChat();
        $this->view->aarpFrontendInterface()->setCustomModel('userChatDetails', $userChatDetails);
    }

}