<?php
/**
 * Created by PhpStorm.
 * User: andreiciungan
 * Date: 9/14/16
 * Time: 5:10 AM
 */

class Api_UserController extends Zend_Rest_Controller {

    private $db;

    public function indexAction()
    {
        $this->getResponse()->setBody('123131312');
        $this->getResponse()->setHttpResponseCode(200);
    }

    public function getAction()
    {
        $this->getResponse()->setBody('Foo!');
        $this->getResponse()->setHttpResponseCode(200);
    }

    public function postAction()
    {
        $this->_response->setHeader('Vary', 'Accept');
        // Cross-Origin Resource Sharing (CORS)
        // TODO: probably should be an environment setting?
        $this->_response->setHeader('Access-Control-Max-Age', '86400');
        $this->_response->setHeader('Access-Control-Allow-Origin', '*');
        $this->_response->setHeader('Access-Control-Allow-Credentials', 'true');
        $this->_response->setHeader('Access-Control-Allow-Headers', 'Authorization, X-Authorization, Origin, Accept, Content-Type, X-Requested-With, X-HTTP-Method-Override');

        $this->initDB();
        $params = $this->getRequest()->getParams();
        $arr = array(
            'name' => isset($params['name']) ? $params['name'] : '',
            'destination_lat' => isset($params['destination_lat']) ? $params['destination_lat'] : '',
            'destination_lng' => isset($params['destination_lng']) ? $params['destination_lng'] : '',
            'accomodation_lat' => isset($params['accomodation_lat']) ? $params['accomodation_lat'] : '',
            'accomodation_lng' => isset($params['accomodation_lng']) ? $params['accomodation_lng'] : '',
            'start_date' => isset($params['start_date']) ? $params['start_date'] : '',
            'end_date' => isset($params['end_date']) ? $params['end_date'] : '',
            'age' => isset($params['age']) ? $params['age'] : '',
            'gender' => isset($params['gender']) ? $params['gender'] : '',
            'fb_id' => isset($params['fb_id']) ? $params['fb_id'] : '',
            'usercategory' => isset($params['usercategory']) ? $params['usercategory'] : ''
        );
        error_log('aaaa');
        $this->db->insert('user', $arr);
        /*$sql = 'SELECT * FROM user';

        $result = $this->db->fetchAll($sql);
        var_dump($result); die;*/
    }

    public function putAction()
    {}

    public function deleteAction()
    {}

    private function initDB() {
        $this->db = Zend_Db::factory('Pdo_Mysql', array(
            'host'     => '192.168.88.148',
            'username' => 'root',
            'password' => 'root',
            'dbname'   => 'hackathon'
        ));
    }

    public function optionsAction() {

    }

}