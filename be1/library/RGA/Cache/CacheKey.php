<?php

class RGA_Cache_CacheKey extends RGA_Model_Abstract {

    private $id;
    private $nid;
    private $hash;
    private $source;
    private $created;
    
    public static $_CMS_KEY = 'cms';
    public static $_SOLR_KEY = 'solr';
    public static $_MONGO_KEY = 'mongo';
    public static $_NO_TRACK = 'no_track';

    function getCreated() {
	return $this->created;
    }

    function setCreated($created) {
	$this->created = $created;
    }

    function getId() {
	return $this->id;
    }

    function getNid() {
	return $this->nid;
    }

    function getHash() {
	return $this->hash;
    }

    function setId($id) {
	$this->id = $id;
    }

    function setNid($nid) {
	$this->nid = $nid;
    }

    function setHash($hash) {
	$this->hash = $hash;
    }
    function getSource() {
        return $this->source;
    }

    function setSource($source) {
        $this->source = $source;
    }


}
