<?php 
use \Application_Model_PracticeLevel as PracticeLevel;
class RGA_PracticeLevel_Mapper {

    protected $_cache;

    const PRACTISE_LEVELS_CACHE_KEY = 'practice_level_%d';
    const PRACTICE_LEVEL_CACHE_KEY = 'practice_level_taxonomy_terms_%d';
    const TAXONOMY_VOCABULARY_ENDPOINT = '/taxonomy_vocabulary?parameters[machine_name]=%s'; // %s environment
    const TAXONOMY_TERM_ENDPOINT = '/taxonomy_term?parameters[vid]=%d'; // %d vocabulary Id
    const TAXONOMY_TERM_ENDPOINT_FULL = '/taxonomy_term/%d'; // %d vocabulary Id

    public function __construct() {
        $this->_cache = Zend_Registry::get('cache');
    }
    /**
    * retrieves the list of available environments from CMS
    */
    private  function _getPractiseLevels() {
        $cacheKey = md5(self::PRACTISE_LEVELS_CACHE_KEY);
        if (($levels = $this->_cache->load($cacheKey)) === false) {
            $drupalClient = new RGA_Drupal_Client();
            $plVocabulary = $drupalClient->get(sprintf(self::TAXONOMY_VOCABULARY_ENDPOINT, 'practice_level'));
            $levels = $drupalClient->get(sprintf(self::TAXONOMY_TERM_ENDPOINT, $plVocabulary[0]->vid));
            $this->_cache->save($levels, $cacheKey);
        }
        return $levels;
    }

    public function getPractiseLevels() {
        $nodes = $this->_getPractiseLevels();
        $levels = array();
        foreach ($nodes as $node) {
            $levels[] = $this->getById($node->tid);
        }
        return $levels;
    }


    public function getByPracticeLevel($practise, $level) {
        $practise = ucfirst(strtolower($practise));
        $name = sprintf("%s Level %d", $practise, (int) $level);
        $pls = $this->_getPractiseLevels();
        $envId = null;
        foreach ($pls as $pl) {     
            if ($pl->name == $name) {
                $plId = $pl->tid;
                break;
            }
        }
        return $plId ? $this->getById($plId) : null;
    }

    public function getById($id) {
        $cacheKey = md5(sprintf(self::PRACTICE_LEVEL_CACHE_KEY, $id));
        
        if (($node = $this->_cache->load($cacheKey)) === false) {
            $drupalClient = new RGA_Drupal_Client();
            $node = $drupalClient->get(sprintf(self::TAXONOMY_TERM_ENDPOINT_FULL, $id));
            $this->_cache->save($node, $cacheKey);
        }
        return new PracticeLevel($node);
    }
}
