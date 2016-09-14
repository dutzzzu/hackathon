<?php

class RGA_Cache_Mapper_CacheKeyMapper extends RGA_Model_Mapper_Abstract {

    protected $_tableName = 'RGA_Cache_Db_CacheKey';

    public function get($nid = NULL, $source = NULL) {
        $select = $this->getDbTable()->select()->setIntegrityCheck(false);
        $select->from(array('c' => 'cache_site_keys'), array('*'));

        if ($nid !== NULL) {
            $select->where('nid = ?', $nid);
        }
        if ($source !== NULL) {
            $select->where('source = ?', $source);
        }

        $results = $this->getDbTable()->fetchAll($select);

        $return = array();
        foreach ($results as $result) {
            $cache = new RGA_Cache_CacheKey();
            $cache->id = $result->id;
            $cache->nid = $result->nid;
            $cache->hash = $result->hash;
            $cache->created = $result->created;

            $return[] = $cache;
        }

        return $return;
    }

    public function getAllCount() {
        $count = $this->getAdapter()->query("SELECT count(*) as total,
            sum(case when source = 'solr' then 1 else 0 end) solr,
            sum(case when source = 'mongo' then 1 else 0 end) mongo,
            sum(case when source = 'cms' then 1 else 0 end) cms,
            sum(case when source = 'no_track' then 1 else 0 end) no_track
            FROM cache_site_keys")->fetch();
        return $count;
    }

    public function save(RGA_Cache_CacheKey $obj) {
        try {
            if ($obj->created == null)
                $obj->created = $_SERVER['REQUEST_TIME'];

            if ($obj->id != null) {
                $id = $this->getDbTable()->update($obj->toArray(ReflectionProperty::IS_PRIVATE), 'id = ' . $obj->id);
            } else {
                $id = $this->getDbTable()->insert($obj->toArray(ReflectionProperty::IS_PRIVATE));
            }
            return $id;
        } catch (Exception $ex) {
            return $ex;
        }
    }

    public function deleteByObjectHash(RGA_Cache_CacheKey $obj) {
        try {
            $variable = $this->getAdapter()->quoteInto('hash = ?', $obj->hash);
            $this->getDbTable()->delete($variable);
        } catch (Exception $ex) {
            return $ex;
        }
    }

    public function deleteBySource($source) {
        try {
            $variable = $this->getAdapter()->quoteInto('source = ?', $source);
            $this->getDbTable()->delete($variable);
        } catch (Exception $ex) {
            return $ex;
        }
    }

    public function deteleAll() {
        try {
            $this->getAdapter()->query("delete FROM cache_site_keys")->fetch();
        } catch (Exception $ex) {
            return $ex;
        }
    }

    public function deploy() {
        try {
            Zend_Registry::get('db_cms')->query('CREATE DATABASE  IF NOT EXISTS `cache_system`;');
            $this->getAdapter()->getConnection()->exec('CREATE DATABASE  IF NOT EXISTS `cache_system`;');
            $this->getAdapter()->query('DROP TABLE IF EXISTS `cache_site_keys`;');
            $this->getAdapter()->query('CREATE TABLE `cache_site_keys` (
                                        `id` int(11) NOT NULL AUTO_INCREMENT,
                                        `nid` int(11) NOT NULL,
                                        `hash` varchar(45) NOT NULL,
                                        `created` varchar(45) NOT NULL,
                                        `source` varchar(45) DEFAULT NULL,
                                        PRIMARY KEY (`id`)
                                      ) ENGINE=InnoDB DEFAULT CHARSET=latin1;');
            foreach (array('cache', 'page_cache', 'file_cache') as $ck) {
                Zend_Registry::get($ck)->clean();
            }
        } catch (Exception $ex) {
            return $ex;
        }
        return true;
    }

}
