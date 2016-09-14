<?php

namespace RGA;

use Application_Model_User as User;

class CurrentUser
{

    private static $user = null;

    /**
     * Get the logged in data user
     *
     * @param bool $reload refresh the user data from the database or not
     * @return User
     */
    public static function getUser($reload = false)
    {
        if (self::$user === null || $reload) {
            $auth = \Zend_Auth::getInstance();
            if ($auth->getIdentity() != null) {
                if ($reload) {
                    $userId = $auth->getIdentity()->getId();
                    if (!empty($userId)) {
                        self::$user = User::one(array('_id' => $userId));
                    }
                } else {
                    self::$user = $auth->getIdentity();
                }
            }
        }

        return self::$user;
    }
}