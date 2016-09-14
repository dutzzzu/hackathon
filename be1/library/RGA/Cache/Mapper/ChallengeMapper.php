<?php

class RGA_Cache_Mapper_ChallengeMapper extends RGA_Model_Mapper_Abstract {

    protected $_tableName = 'RGA_Cache_Db_Challenge';

    public function get($nid = NULL) {
	$time_start = microtime(true);
	$select = $this->getDbTable()->select();
	$select->setIntegrityCheck(false)
		->from(array('n' => 'node'), array('nid' => 'n.nid', 'type' => 'n.type', 'title' => 'n.title'))
		->joinLeft(array('cd' => 'field_data_field_challenge_description'), 'cd.entity_id = n.nid', array('description' => 'cd.field_challenge_description_value'))
		->joinLeft(array('dh' => 'field_data_field_challenge_detail_header'), 'dh.entity_id = n.nid', array('detailHeader' => 'dh.field_challenge_detail_header_value'))
		->joinLeft(array('ld' => 'field_data_field_challenge_long_description'), 'ld.entity_id = n.nid', array('longDescription' => 'ld.field_challenge_long_description_value'))
		->joinLeft(array('fs' => 'field_data_field_steps'), 'fs.entity_id = n.nid', array('stepsString' => 'fs.field_steps_value'))
		->joinLeft(array('cm' => 'field_data_field_challenge_image'), 'cm.entity_id = n.nid', array('imageTitle' => 'cm.field_challenge_image_title'))
		->joinLeft(array('fm' => 'file_managed'), 'fm.fid = cm.field_challenge_image_fid', array('image' => 'fm.filename'))
		->joinLeft(array('fcd' => 'field_data_field_challenge_duration'), 'fcd.entity_id = n.nid', array('duration' => 'fcd.field_challenge_duration_value'))
		->joinLeft(array('cef' => 'field_data_field_challenge_effort'), 'cef.entity_id = n.nid', array('effort' => 'cef.field_challenge_effort_value'))
		->joinLeft(array('mn' => 'field_data_field_machine_name'), 'mn.entity_id = n.nid', array('machineName' => 'mn.field_machine_name_value'))
		->joinLeft(array('fcm' => 'field_data_field_challenge_email'), 'fcm.entity_id = n.nid', array('email' => 'fcm.field_challenge_email_value'))
		->joinLeft(array('ci' => 'field_data_field_contract_id'), 'ci.entity_id = n.nid', array('contractId' => 'ci.field_contract_id_value'))
		->joinLeft(array('cec' => 'field_data_field_challenge_email_content'), 'cec.entity_id = n.nid', array('emailContent' => 'cec.field_challenge_email_content_value'))
		->joinLeft(array('mtf' => 'field_data_field_mkt_floodlight_tag'), 'mtf.entity_id = n.nid', array('floodlightTag' => 'mtf.field_mkt_floodlight_tag_value'))
		
		
		->joinLeft(array('cdi' => 'field_data_field_challenge_developed_by_ima'), 'cdi.entity_id = n.nid', array())
		->joinLeft(array('fm2' => 'file_managed'), 'fm2.fid = cdi.field_challenge_developed_by_ima_fid', array('imageDevelopedBy' => 'fm2.filename'))
		->where('n.type = ?', 'challenge');

	$result = $this->getDbTable()->fetchAll($select);
	if ($nid !== NULL) {
	    $select->where('nid = ?', $nid);
	}

	$results = $this->getDbTable()->fetchAll($select);

	$return = array();
	foreach ($results as $result) {
	    $challenge = new RGA_Cache_Challenge();
	    $challenge->nid = $result->nid;
	    $challenge->type = $result->type;
	    $challenge->title = $result->title;
	    $challenge->description = $result->description;
	    $challenge->detailHeader = $result->detailHeader;
	    $challenge->longDescription = $result->longDescription;
	    $challenge->stepsString = $result->stepsString;
	    $challenge->imageTitle = $result->imageTitle;
	    $challenge->image = $result->image;
	    $challenge->imageDevelopedBy = $result->imageDevelopedBy;
	    $challenge->duration = $result->duration;
	    $challenge->effort = $result->effort;
	    $challenge->machineName = $result->machineName;
	    $challenge->email = $result->email;
	    $challenge->contractId = $result->contractId;
	    $challenge->emailContent = $result->emailContent;
	    $challenge->floodlightTag = $result->floodlightTag;

	    $return[] = $challenge;
	}

	$time_end = microtime(true);
	$time = $time_end - $time_start;

	echo "Did fetch in $time seconds\n";

	return $return;
    }

}
