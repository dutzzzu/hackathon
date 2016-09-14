<?php
class RGA_Exercise_Mapper {

    const EXERCISE_ENDPOINT = '/node/%d';
    const EXERCISES_ENDPOINT = '/views/exercises/?field_practice=%d';
    const EXERCISES_CACHE_KEY_PATTERN = "careers_%s";
    const EXERCISE_CACHE_KEY_PATTERN = "single_career_%s";

    protected $_cache;

    public function __construct() {
        $this->_cache = Zend_Registry::get('cache');
    }
    
    public function getById($id) {
        $cacheKey = md5(sprintf(self::EXERCISE_CACHE_KEY_PATTERN, $id));
        
        if (($node = $this->_cache->load($cacheKey)) === false) {
            $drupalClient = new RGA_Drupal_Client();
            $node = $drupalClient->get(sprintf(self::EXERCISE_ENDPOINT, $id));
            $this->_cache->save($node, $cacheKey);
        }
        return RGA_Exercise_Factory::create($node);
    }

    public function getByPracticeLevel($practice, $level) {
        $practiceLevelMapper = new RGA_PracticeLevel_Mapper();
        $practice = $practiceLevelMapper->getByPracticeLevel($practice, $level);
        
        if (!$practice) {
            return null;
        }
        
        $drupalClient = new RGA_Drupal_Client();
        $nodes = $drupalClient->get(sprintf(self::EXERCISES_ENDPOINT, $practice->id));
        error_log(print_r($practice,1));
        error_log(sprintf(self::EXERCISES_ENDPOINT, $practice->id));
        if (!$nodes) {
            return null;
        }
        $exercises = array();
        foreach ($nodes as $node) {
            $exercises[] = RGA_Exercise_Factory::create($node);
        }
        return $exercises;
        // field_practice
    }
}