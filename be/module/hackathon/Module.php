<?php
namespace hackathon;

use Zend\Db\TableGateway\TableGateway;
use Zend\Mvc\MvcEvent;
use ZF\Apigility\Provider\ApigilityProviderInterface;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Sql;


class Module implements ApigilityProviderInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
      if(isset($_POST['accomodation_lat']) && isset($_POST['accomodation_lng'])){
        $accLat = $_POST['accomodation_lat'];
        $accLng = $_POST['accomodation_lng'];
        $this->getPlaces($accLat,$accLng);
      }

      // Set CORS headers to allow all requests
      $headers = $event->getResponse()->getHeaders();
      $headers->addHeaderLine('Access-Control-Allow-Origin: *');
      $headers->addHeaderLine('Access-Control-Allow-Methods: PUT, GET, POST, PATCH, DELETE, OPTIONS');
      $headers->addHeaderLine('Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept');
    }

    public function getPlaces($lat,$lng){


      $adapterInsert = new Adapter(array(
        'driver'   => 'Pdo_mysql',
        'database' => 'hackathon',
        'username' => 'root',
        'password' => 'root'
      ));

//      $subcategoryTable = new TableGateway('subcategories', $adapterInsert);
//
//      $subcategoryTable->insert(array('type'=>'restaurant'));

      $tableToBeInserted = new TableGateway('places', $adapterInsert);

//insert with select
      $object = new \stdClass();
      $object->lat = 'dsadada';
      $object->lng = 'dsadada';
      //$tableToBeInserted->insert($object);
      $sel = new Sql($adapterInsert);
      $s = $sel->insert('places');
      $data = array(
        'lat'=>'1111',
        'lng'=>'2222'

      );
      $s->values($data);
      $statement = $sel->prepareStatementForSqlObject($s);
      $result= $statement->execute();

      return;
      // https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=41.896327,12.496116&radius=1500&types=food&types=restaurant&key=AIzaSyB29r2s9IHpQEYH3OsVoO_gWNwEX-OfKXE
      $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=';
      $url .= $lat.','.$lng;
      $url .= '&radius=1500&types=food&types=restaurant&key=AIzaSyB29r2s9IHpQEYH3OsVoO_gWNwEX-OfKXE';

      $s = curl_init();
      curl_setopt($s,CURLOPT_URL,$url);
      curl_setopt($s,CURLOPT_HTTPHEADER,array('Expect:'));
      curl_setopt($s,CURLOPT_TIMEOUT,'1000');
      curl_setopt($s,CURLOPT_MAXREDIRS,10);
      curl_setopt($s,CURLOPT_RETURNTRANSFER,true);
//      curl_setopt($s,CURLOPT_FOLLOWLOCATION,$this->_followlocation);
//      curl_setopt($s,CURLOPT_COOKIEJAR,$this->_cookieFileLocation);
//      curl_setopt($s,CURLOPT_COOKIEFILE,$this->_cookieFileLocation);

      $inCeva = curl_exec($s);
      curl_close($s);
      echo '<pre>'.print_r($inCeva,true);
      exit();
    }

    public function getAutoloaderConfig()
    {
        return [
            'ZF\Apigility\Autoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src',
                ],
            ],
        ];
    }
}
