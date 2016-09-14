<?php

class RGA_Cache_ChallengeCategory extends RGA_Model_Abstract {

    private $nid;
    private $type;
    private $title;
    private $subTitle;
    private $description;
    private $image;
    private $machineName;
    private $challengeOrder;
    private $challengeExpertAutor;
    
    function getType() {
	return $this->type;
    }

    function setType($type) {
	$this->type = $type;
    }

        function getNid() {
	return $this->nid;
    }

    function getTitle() {
	return $this->title;
    }

    function getSubTitle() {
	return $this->subTitle;
    }

    function getDescription() {
	return $this->description;
    }

    function getImage() {
	return $this->image;
    }

    function getMachineName() {
	return $this->machineName;
    }

    function getChallengeOrder() {
	return $this->challengeOrder;
    }

    function getChallengeExpertAutor() {
	return $this->challengeExpertAutor;
    }

    function setNid($nid) {
	$this->nid = $nid;
    }

    function setTitle($title) {
	$this->title = $title;
    }

    function setSubTitle($subTitle) {
	$this->subTitle = $subTitle;
    }

    function setDescription($description) {
	$this->description = $description;
    }

    function setImage($image) {
	$this->image = $image;
    }

    function setMachineName($machineName) {
	$this->machineName = $machineName;
    }

    function setChallengeOrder($challengeOrder) {
	$this->challengeOrder = $challengeOrder;
    }

    function setChallengeExpertAutor($challengeExpertAutor) {
	$this->challengeExpertAutor = $challengeExpertAutor;
    }

}
