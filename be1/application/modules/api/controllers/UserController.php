<?php
/**
 * Created by PhpStorm.
 * User: andreiciungan
 * Date: 9/14/16
 * Time: 5:10 AM
 */

class Api_UserController extends Zend_Rest_Controller {

    private $db;
    private $historical_places = array('museum', 'church');
    private $shopping = array();
    private $night_life = array('restaurant', 'food');
    private $sightseeing = array('park', 'zoo');

    public function indexAction()
    {
        $interests = 'historical_places';
        var_dump($this->$interests); die;
        $this->getPlaces('48.8606','2.33764');
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
        $interests = isset($params['interests']) ? $params['interests'] : '';
        $ac_lat = isset($params['accomodation_lat']) ? $params['accomodation_lat'] : '';
        $ac_lng = isset($params['accomodation_lng']) ? $params['accomodation_lng'] : '';
        $arr = array(
            'name' => isset($params['name']) ? $params['name'] : '',
            'destination_lat' => isset($params['destination_lat']) ? $params['destination_lat'] : '',
            'destination_lng' => isset($params['destination_lng']) ? $params['destination_lng'] : '',
            'accomodation_lat' => $ac_lat,
            'accomodation_lng' => $ac_lng,
            'start_date' => isset($params['start_date']) ? $params['start_date'] : '',
            'end_date' => isset($params['end_date']) ? $params['end_date'] : '',
            'age' => isset($params['age']) ? $params['age'] : '',
            'gender' => isset($params['gender']) ? $params['gender'] : '',
            'fb_id' => isset($params['fb_id']) ? $params['fb_id'] : '',
            'usercategory' => implode(',',$interests)
        );
        $this->db->insert('user', $arr);

        $categories = array();
        if ($interests) {
            foreach($interests as $interest) {
                if ($interest == 'historical-places') $interest = 'historical_places';
                if ($interest == 'night-life') $interest = 'night_life';
                $categories[$interest] = $this->getPlaces($ac_lat,$ac_lng,$interest);
            }
        }

        $this->getResponse()->setBody(json_encode($categories));
        $this->getResponse()->setHttpResponseCode(200);

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
            'host'     => '192.168.88.150',
            'username' => 'root',
            'password' => 'root',
            'dbname'   => 'hackathon'
        ));
    }

    public function optionsAction() {

    }

    private function getPlaces($lat, $lng, $interests) {
        $allResults = array();
        $arr = array();
        foreach($this->$interests as $key => $interest) {
            // https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=41.896327,12.496116&radius=1500&types=food&types=restaurant&key=AIzaSyB29r2s9IHpQEYH3OsVoO_gWNwEX-OfKXE
            $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=';
            $url .= $lat . ',' . $lng;
            $url .= '&radius=1500&key=AIzaSyB29r2s9IHpQEYH3OsVoO_gWNwEX-OfKXE';
            $url .= '&type=' . $interest;


            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
            curl_setopt($ch, CURLOPT_TIMEOUT, '1000');
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result);
            foreach ($result->results as $point) {
                $allResults[$interest][] = $this->parseResult($point, $interest);
            }
        }

        return $allResults;
    }

    private function parseResult($result, $interest) {
        $google = array();
        $google['place_id'] = $result->place_id;
        $google['lat'] = $result->geometry->location->lat;
        $google['lng'] = $result->geometry->location->lng;
        $google['photo_ref'] = !empty($result->photos[0]->photo_reference) ? $result->photos[0]->photo_reference : '';
        $google['rating'] = !empty($result->rating) ? $result->rating : '';
        $google['name'] = $result->name;
        $google['subcategory'] = $interest;
        //$google['desc'] = $this->getWiki($result->name);

        return $google;
    }

    private function getWiki($name) {
        $url = "https://en.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&titles=" . urlencode($name);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_TIMEOUT, '1000');
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        $desc = '';
        if (!empty($result->query->pages)) {
            $page = (array)$result->query->pages;
            $page = reset($page);
            if (!empty($page->extract)) $desc = $page->extract;
        }
        return $desc;
    }

}