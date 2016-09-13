<?php
namespace Application\Event;

use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventInterface;
use Zend\Mvc\MvcEvent;

class GoogleListener implements ListenerAggregateInterface {
  protected $listeners = array();


  public function attach(EventManagerInterface $events, $priority = 1) {
    $this->doEvent();
  }

  public function detach(EventManagerInterface $events) {
    foreach ($this->listeners as $index => $listener) {
      if ($events->detach($listener)) {
        unset($this->listeners[$index]);
      }
    }
  }

  public function doEvent() {
    die('motherfucker');
  }
}