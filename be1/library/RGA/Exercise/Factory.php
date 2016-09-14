<?php
use \Application_Model_Exercise as Exercise;
class RGA_Exercise_Factory {
    public static function create($node) {
        $exercise = null;
        switch ($node->type) {
            case Exercise::TYPE_ESSAY:
                $exercise = new Application_Model_Exercise_Essay($node);
                break;
            case Exercise::TYPE_MULTIPLE_CHOICE:
                $exercise = new Application_Model_Exercise_MultipleChoice($node);
                break;
        }
        return $exercise;
    }
}