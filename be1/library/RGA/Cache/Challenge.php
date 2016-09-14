<?php

class RGA_Cache_Challenge extends RGA_Model_Abstract {

    private $nid;
    private $type;
    private $title;
    private $description;
    private $detailHeader;
    private $longDescription;
    private $stepsString;
    private $imageTitle;
    private $image;
    private $imageDevelopedBy;
    private $duration;
    private $effort;
    private $machineName;
    private $email;
    private $contractId;
    private $emailContent;
    private $floodlightTag;
    
    function getNid() {
	return $this->nid;
    }

    function getType() {
	return $this->type;
    }

    function getTitle() {
	return $this->title;
    }

    function getDescription() {
	return $this->description;
    }

    function getDetailHeader() {
	return $this->detailHeader;
    }

    function getLongDescription() {
	return $this->longDescription;
    }

    function getStepsString() {
	return $this->stepsString;
    }

    function getImageTitle() {
	return $this->imageTitle;
    }

    function getImage() {
	return $this->image;
    }

    function getImageDevelopedBy() {
	return $this->imageDevelopedBy;
    }

    function getDuration() {
	return $this->duration;
    }

    function getEffort() {
	return $this->effort;
    }

    function getMachineName() {
	return $this->machineName;
    }

    function getEmail() {
	return $this->email;
    }

    function getContractId() {
	return $this->contractId;
    }

    function getEmailContent() {
	return $this->emailContent;
    }

    function getFloodlightTag() {
	return $this->floodlightTag;
    }

    function setNid($nid) {
	$this->nid = $nid;
    }

    function setType($type) {
	$this->type = $type;
    }

    function setTitle($title) {
	$this->title = $title;
    }

    function setDescription($description) {
	$this->description = $description;
    }

    function setDetailHeader($detailHeader) {
	$this->detailHeader = $detailHeader;
    }

    function setLongDescription($longDescription) {
	$this->longDescription = $longDescription;
    }

    function setStepsString($stepsString) {
	$this->stepsString = $stepsString;
    }

    function setImageTitle($imageTitle) {
	$this->imageTitle = $imageTitle;
    }

    function setImage($image) {
	$this->image = $image;
    }

    function setImageDevelopedBy($imageDevelopedBy) {
	$this->imageDevelopedBy = $imageDevelopedBy;
    }

    function setDuration($duration) {
	$this->duration = $duration;
    }

    function setEffort($effort) {
	$this->effort = $effort;
    }

    function setMachineName($machineName) {
	$this->machineName = $machineName;
    }

    function setEmail($email) {
	$this->email = $email;
    }

    function setContractId($contractId) {
	$this->contractId = $contractId;
    }

    function setEmailContent($emailContent) {
	$this->emailContent = $emailContent;
    }

    function setFloodlightTag($floodlightTag) {
	$this->floodlightTag = $floodlightTag;
    }



}
