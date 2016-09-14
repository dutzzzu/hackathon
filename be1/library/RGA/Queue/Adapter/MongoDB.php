<?php

class RGA_Queue_Adapter_MongoDB extends Zend_Queue_Adapter_AdapterAbstract {

    /**
     * Constructor
     *
     * @param  array|Zend_Config $options
     * @param  Zend_Queue|null $queue
     * @return void
     */
    public function __construct($options, Zend_Queue $queue = null)
    {
        parent::__construct($options, $queue);
    }

    /********************************************************************
     * Queue management functions
     *********************************************************************/

    /**
     * Does a queue already exist?
     *
     * Throws an exception if the adapter cannot determine if a queue exists.
     * use isSupported('isExists') to determine if an adapter can test for
     * queue existance.
     *
     * @param  string $name
     * @return boolean
     * @throws Zend_Queue_Exception
     */
    public function isExists($name)
    {
        $id = 0;

        try {
            $id = $this->getQueueId($name);
        } catch (Zend_Queue_Exception $e) {
            return false;
        }

        return $id instanceof MongoId;
    }

    /**
     * Create a new queue
     *
     * Visibility timeout is how long a message is left in the queue "invisible"
     * to other readers.  If the message is acknowleged (deleted) before the
     * timeout, then the message is deleted.  However, if the timeout expires
     * then the message will be made available to other queue readers.
     *
     * @param  string  $name    queue name
     * @param  integer $timeout default visibility timeout
     * @return boolean
     * @throws Zend_Queue_Exception - database error
     */
    public function create($name, $timeout = null)
    {
        if ($this->isExists($name)) {
            return false;
        }

        $queue = new RGA_Queue_Adapter_MongoDB_Queue();
        $queue->queue_name = $name;
        $queue->timeout    = ($timeout === null) ? self::CREATE_TIMEOUT_DEFAULT : (int)$timeout;

        try {
            if ($queue->save()) {
                return true;
            }
        } catch (Exception $e) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception($e->getMessage(), $e->getCode(), $e);
        }

        return false;
    }

    /**
     * Delete a queue and all of it's messages
     *
     * Returns false if the queue is not found, true if the queue exists
     *
     * @param  string  $name queue name
     * @return boolean
     * @throws Zend_Queue_Exception - database error
     */
    public function delete($name)
    {
        $id = $this->getQueueId($name); // get primary key

        // if the queue does not exist then it must already be deleted.
        $queue = RGA_Queue_Adapter_MongoDB_Queue::find($id);
        if (!$queue) {
            return false;
        }

        if ($queue instanceof RGA_Queue_Adapter_MongoDB_Queue) {
            try {
                $queue->delete();
            } catch (Exception $e) {
                require_once 'Zend/Queue/Exception.php';
                throw new Zend_Queue_Exception($e->getMessage(), $e->getCode(), $e);
            }
        }

        if (array_key_exists($name, $this->_queues)) {
            unset($this->_queues[$name]);
        }

        return true;
    }

    /*
     * Get an array of all available queues
     *
     * Not all adapters support getQueues(), use isSupported('getQueues')
     * to determine if the adapter supports this feature.
     *
     * @return array
     * @throws Zend_Queue_Exception - database error
     */
    public function getQueues()
    {

        $this->_queues = array();
        foreach (RGA_Queue_Adapter_MongoDB_Queue::all() as $queue) {
            $this->_queues[$queue->queue_name] = (int)$queue->getID();
        }

        $list = array_keys($this->_queues);

        return $list;
    }

    /**
     * Return the approximate number of messages in the queue
     *
     * @param  Zend_Queue $queue
     * @return integer
     * @throws Zend_Queue_Exception
     */
    public function count(Zend_Queue $queue = null)
    {
        if ($queue === null) {
            $queue = $this->_queue;
        }

        $messages = RGA_Queue_Adapter_MongoDB_Message::find(array('queue_id' => $queue->getId()));
        
        // return count results
        return (int) count($messages);
    }

    /********************************************************************
    * Messsage management functions
     *********************************************************************/

    /**
     * Send a message to the queue
     *
     * @param  string     $message Message to send to the active queue
     * @param  Zend_Queue $queue
     * @return Zend_Queue_Message
     * @throws Zend_Queue_Exception - database error
     */
    public function send($message, Zend_Queue $queue = null)
    {

        if ($queue === null) {
            $queue = $this->_queue;
        }

        if (is_scalar($message)) {
            $message = (string) $message;
        }
        if (is_string($message)) {
            $message = trim($message);
        }

        if (!$this->isExists($queue->getName())) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception('Queue does not exist:' . $queue->getName());
        }

        $msg           = new RGA_Queue_Adapter_MongoDB_Message();
        $msg->queue_id = $this->getQueueId($queue->getName());
        $msg->created  = time();
        $msg->body     = $message;
        $msg->md5      = md5($message);
        $msg->handle   = '';
        // $msg->timeout = ??? @TODO

        try {
            $msg->save();
        } catch (Exception $e) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception($e->getMessage(), $e->getCode(), $e);
        }

        $options = array(
            'queue' => $queue,
            'data'  => $msg->export(),
        );

        $classname = $queue->getMessageClass();
        if (!class_exists($classname)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($classname);
        }

        return new $classname($options);
    }

    /**
     * Get messages in the queue
     *
     * @param  integer    $maxMessages  Maximum number of messages to return
     * @param  integer    $timeout      Visibility timeout for these messages
     * @param  Zend_Queue $queue
     * @return Zend_Queue_Message_Iterator
     * @throws Zend_Queue_Exception - database error
     */
    public function receive($maxMessages = null, $timeout = null, Zend_Queue $queue = null)
    {
        if ($maxMessages === null) {
            $maxMessages = 1;
        }
        if ($timeout === null) {
            $timeout = self::RECEIVE_TIMEOUT_DEFAULT;
        }
        if ($queue === null) {
            $queue = $this->_queue;
        }
        $microtime = microtime(true);
        $msgs      = array();

        try {
            $messages = RGA_Queue_Adapter_MongoDB_Message::all(
                array(
                    'queue_id' => $this->getQueueId($queue->getName()), 
                    '$or' => array(
                        array('handle' => ''), 
                        array(
                            'timeout' => array(
                                '$lt' => $microtime - $timeout
                            )
                        )
                    )
                )
            )->limit($maxMessages);
            
            if ($messages) {
                foreach ($messages as $message) {
                    $message->handle = md5(uniqid(rand(), true));
                    $message->timeout = $microtime;
                    $message->save();
                    $msgs[] = $message->export();
                }    
            }

            
        } catch (Exception $e) {
            require_once 'Zend/Queue/Exception.php';

            throw new Zend_Queue_Exception($e->getMessage(), $e->getCode(), $e);
        }

        $options = array(
            'queue'        => $queue,
            'data'         => $msgs,
            'messageClass' => $queue->getMessageClass(),
        );

        $classname = $queue->getMessageSetClass();
        if (!class_exists($classname)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($classname);
        }
        return new $classname($options);
    }

    /**
     * Delete a message from the queue
     *
     * Returns true if the message is deleted, false if the deletion is
     * unsuccessful.
     *
     * @param  Zend_Queue_Message $message
     * @return boolean
     * @throws Zend_Queue_Exception - database error
     */
    public function deleteMessage(Zend_Queue_Message $message)
    {
        $msg = RGA_Queue_Adapter_MongoDB_Message::one(array('handle' => $message->handle));

        if ($msg && $msg->delete()) {
            return true;
        }

        return false;
    }

    /********************************************************************
     * Supporting functions
     *********************************************************************/

    /**
     * Return a list of queue capabilities functions
     *
     * $array['function name'] = true or false
     * true is supported, false is not supported.
     *
     * @param  string $name
     * @return array
     */
    public function getCapabilities()
    {
        return array(
            'create'        => true,
            'delete'        => true,
            'send'          => true,
            'receive'       => true,
            'deleteMessage' => true,
            'getQueues'     => true,
            'count'         => true,
            'isExists'      => true,
        );
    }

    /********************************************************************
     * Functions that are not part of the Zend_Queue_Adapter_Abstract
     *********************************************************************/
    /**
     * Get the queue ID
     *
     * Returns the queue's row identifier.
     *
     * @param  string       $name
     * @return integer|null
     * @throws Zend_Queue_Exception
     */
    protected function getQueueId($name)
    {
        if (array_key_exists($name, $this->_queues)) {
            return $this->_queues[$name];
        }

        $queue = RGA_Queue_Adapter_MongoDB_Queue::one(array('queue_name' => $name));
        if ($queue === null) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception('Queue does not exist!!!: ' . $name);
        }

        $this->_queues[$name] = $queue->getId();

        return $this->_queues[$name];
    }
}