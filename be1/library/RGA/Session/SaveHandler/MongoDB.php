<?php
class RGA_Session_SaveHandler_MongoDB implements Zend_Session_SaveHandler_Interface
{
    const PRIMARY_ASSIGNMENT_SESSION_SAVE_PATH = 'sessionSavePath';
    const PRIMARY_ASSIGNMENT_SESSION_NAME      = 'sessionName';
    const PRIMARY_ASSIGNMENT_SESSION_ID        = 'sessionId';

    const MODIFIED_COLUMN   = 'modifiedColumn';
    const LIFETIME_COLUMN   = 'lifetimeColumn';
    const DATA_COLUMN       = 'dataColumn';

    const LIFETIME          = 'lifetime';
    const OVERRIDE_LIFETIME = 'overrideLifetime';

    const CSRFTOKEN     = "csrfToken";
    const DISPLAYWEAKPASSWORDWARN     = "displayWeakPasswordWarn";

    /**
     * Session table last modification time column
     *
     * @var string
     */
    protected $_modifiedColumn = null;

    /**
     * Session table lifetime column
     *
     * @var string
     */
    protected $_lifetimeColumn = null;

    /**
     * Session table data column
     *
     * @var string
     */
    protected $_dataColumn = null;

    /**
     * Session lifetime
     *
     * @var int
     */
    protected $_lifetime = false;

    /**
     * Whether or not the lifetime of an existing session should be overridden
     *
     * @var boolean
     */
    protected $_overrideLifetime = false;

    /**
     * Session save path
     *
     * @var string
     */
    protected $_sessionSavePath;

    /**
     * Session name
     *
     * @var string
     */
    protected $_sessionName;

    public $_csrf;
    public $_displayWeakPasswordWarn;


    public function __construct($config) {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        } else if (!is_array($config)) {
            /**
             * @see Zend_Session_SaveHandler_Exception
             */
            require_once 'Zend/Session/SaveHandler/Exception.php';

            throw new Zend_Session_SaveHandler_Exception(
                '$config must be an instance of Zend_Config or array of key/value pairs containing '
              . 'configuration options for Zend_Session_SaveHandler_MongoDB.');
        }

        foreach ($config as $key => $value) {
            do {
                switch ($key) {
                    case self::MODIFIED_COLUMN:
                        $this->_modifiedColumn = (string) $value;
                        break;
                    case self::LIFETIME_COLUMN:
                        $this->_lifetimeColumn = (string) $value;
                        break;
                    case self::DATA_COLUMN:
                        $this->_dataColumn = (string) $value;
                        break;
                    case self::LIFETIME:
                        $this->setLifetime($value);
                        break;
                    case self::CSRFTOKEN:
                        $this->_csrf = (string) $value;
                        break;
                    case self::DISPLAYWEAKPASSWORDWARN:
                        $this->_displayWeakPasswordWarn = (string) $value;
                        break;
                    case self::OVERRIDE_LIFETIME:
                        $this->setOverrideLifetime($value);
                        break;
                    default:
                        break 2;
                }
                unset($config[$key]);
            } while (false);
        }
    }
    /**
     * Open Session
     *
     * @param string $save_path
     * @param string $name
     * @return boolean
     */
    public function open($save_path, $name)
    {
        $this->_sessionSavePath = $save_path;
        $this->_sessionName     = $name;

        return true;
    }

    /**
     * Close session
     *
     * @return boolean
     */
    public function close()
    {
        return true;
    }

    /**
     * Read session data
     *
     * @param string $id
     * @return string
     */
    public function read($id)
    {
        $return = '';
        $row = Application_Model_Session::one(array(self::PRIMARY_ASSIGNMENT_SESSION_ID => $id));

        if ($row instanceof Application_Model_Session) {
            if ($this->_getExpirationTime($row) > time()) {
                $return = $row->{$this->_dataColumn};
            } else {
                $this->destroy($id);
            }
        }

        return $return;
    }

    /**
     * Retrieve session lifetime considering Zend_Session_SaveHandler_DbTable::OVERRIDE_LIFETIME
     *
     * @param Zend_Db_Table_Row_Abstract $row
     * @return int
     */
    protected function _getLifetime(Application_Model_Session $row){
        $return = $this->_lifetime;

        if (!$this->_overrideLifetime) {
            $return = (int) $row->{$this->_lifetimeColumn};
        }

        return $return;
    }

    /**
     * Set session lifetime and optional whether or not the lifetime of an existing session should be overridden
     *
     * $lifetime === false resets lifetime to session.gc_maxlifetime
     *
     * @param int $lifetime
     * @param boolean $overrideLifetime (optional)
     * @return RGA_Session_Savehandler_MongoDb
     */
    public function setLifetime($lifetime, $overrideLifetime = null)
    {
        if ($lifetime < 0) {
            /**
             * @see Zend_Session_SaveHandler_Exception
             */
            require_once 'Zend/Session/SaveHandler/Exception.php';
            throw new Zend_Session_SaveHandler_Exception();
        } else if (empty($lifetime)) {
            $this->_lifetime = (int) ini_get('session.gc_maxlifetime');
        } else {
            $this->_lifetime = (int) $lifetime;
        }

        if ($overrideLifetime != null) {
            $this->setOverrideLifetime($overrideLifetime);
        }

        return $this;
    }

    /**
     * Retrieve whether or not the lifetime of an existing session should be overridden
     *
     * @return boolean
     */
    public function getOverrideLifetime()
    {
        return $this->_overrideLifetime;
    }

    /**
     * Set whether or not the lifetime of an existing session should be overridden
     *
     * @param boolean $overrideLifetime
     * @return RGA_Session_Savehandler_MongoDb
     */
    public function setOverrideLifetime($overrideLifetime)
    {
        $this->_overrideLifetime = (boolean) $overrideLifetime;

        return $this;
    }

    /**
     * Retrieve session expiration time
     *
     * @param RGA_Session_Savehandler_MongoDb $row
     * @return int
     */
    protected function _getExpirationTime(Application_Model_Session $row)
    {
        return (int) $row->{$this->_modifiedColumn} + $this->_getLifetime($row);
    }

    /**
     * Write session data
     *
     * @param string $id
     * @param string $data
     * @return boolean
     */
    public function write($id, $data)
    {
        $row = Application_Model_Session::one(array(self::PRIMARY_ASSIGNMENT_SESSION_ID => $id));
        $time = time();
        if ($row instanceof Application_Model_Session) {
            $row->{$this->_modifiedColumn} = $time;
            $row->{$this->_dataColumn} = $data;
        } else {
            $row = new Application_Model_Session();
            $row->{$this->_modifiedColumn} = $time;
            $row->{self::PRIMARY_ASSIGNMENT_SESSION_ID} = $id;
            $row->{$this->_dataColumn} = $data;
            $row->{$this->_lifetimeColumn} = $this->_lifetime;
            $row->{self::PRIMARY_ASSIGNMENT_SESSION_SAVE_PATH} = $this->_sessionSavePath;
            $row->{self::PRIMARY_ASSIGNMENT_SESSION_NAME} = $this->_sessionName;
            $row->{self::CSRFTOKEN} = $this->_csrf;
            $row->{self::DISPLAYWEAKPASSWORDWARN} = $this->_displayWeakPasswordWarn;
        }
        $row->save();
        return true;
    }

    /**
     * Destroy Session - remove data from resource for
     * given session id
     *
     * @param string $id
     */
    public function destroy($id) {
        $row = Application_Model_Session::one(array('sessionId' => $id));
        if ($row instanceof Application_Model_Session) {
            $row->delete();
        }
    }

    /**
     * Garbage Collection - remove old session data older
     * than $maxlifetime (in seconds)
     *
     * @param int $maxlifetime
     */
    public function gc($maxlifetime){
        //error_log('--- Session GC running ---');
        $rows = Application_Model_Session::all(
            array($this->_modifiedColumn => array('$lt' => time() - $this->_lifetime))
        );
        if ($rows) {
            foreach ($rows as $row) {
                $row->delete();
            }
        }
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        Zend_Session::writeClose();
    }

}
