<?php
namespace hackathon;

use Zend\Mvc\MvcEvent;
use ZF\Apigility\Provider\ApigilityProviderInterface;

class Module implements ApigilityProviderInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
      $eventManager        = $event->getApplication()->getEventManager();
      $sharedManager = $eventManager->getSharedManager();
      $sm = $event->getApplication()->getServiceManager();

      $sharedManager->attach('Zend\Mvc\Controller\AbstractActionController',  'fetchAll', function($e)
      use ($sm) {
        $controller = $e->getTarget();
        $controller->getEventManager()->attachAggregate($sm->get('GoogleListener'));
      }, 2);

      // Set CORS headers to allow all requests
      $headers = $event->getResponse()->getHeaders();
      $headers->addHeaderLine('Access-Control-Allow-Origin: *');
      $headers->addHeaderLine('Access-Control-Allow-Methods: PUT, GET, POST, PATCH, DELETE, OPTIONS');
      $headers->addHeaderLine('Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept');
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
