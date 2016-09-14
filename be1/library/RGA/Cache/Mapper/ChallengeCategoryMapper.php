<?php

class RGA_Cache_Mapper_ChallengeCategoryMapper extends RGA_Model_Mapper_Abstract {

    protected $_tableName = 'RGA_Cache_Db_ChallengeCategory';

    public function get($nid = NULL) {
	$time_start = microtime(true);
	$select = $this->getDbTable()->select();
	$select->setIntegrityCheck(false)
		->from(array('n' => 'node'), array('nid' => 'n.nid', 'type' => 'n.type', 'title' => 'n.title'))
		->joinLeft(array('b' => 'field_data_body'), 'b.entity_id = n.nid', array('description' => 'b.body_value'))
		->joinLeft(array('mn' => 'field_data_field_machine_name'), 'mn.entity_id = n.nid', array('machineName' => 'mn.field_machine_name_value'))
		->joinLeft(array('co' => 'field_data_field_challenge_order'), 'co.entity_id = n.nid', array('challengeOrder' => 'co.field_challenge_order_value'))
		->joinLeft(array('cs' => 'field_data_field_challenge_subtitle'), 'cs.entity_id = n.nid', array('subTitle' => 'cs.field_challenge_subtitle_value'))
		->joinLeft(array('cm' => 'field_data_field_challenge_category_image'), 'cm.entity_id = n.nid', array('imageTitle' => 'cm.field_challenge_category_image_title'))
		->joinLeft(array('fm' => 'file_managed'), 'fm.fid = cm.field_challenge_category_image_fid', array('image' => 'fm.filename'))
		
		->where('n.type = ?', 'challenge_category');
	
	//print_R($select->__toString());die;

	$result = $this->getDbTable()->fetchAll($select);
	if ($nid !== NULL) {
	    $select->where('nid = ?', $nid);
	}

	$results = $this->getDbTable()->fetchAll($select);

	$return = array();
	foreach ($results as $result) {
	    $challengeCategory = new RGA_Cache_ChallengeCategory();
	    $challengeCategory->nid = $result->nid;
	    $challengeCategory->type = $result->type;
	    $challengeCategory->title = $result->title;
	    $challengeCategory->subTitle  = $result->subTitle;
	    $challengeCategory->description = $result->description;
	    $challengeCategory->image = array('title' => $result->imageTitle, 'image' => $result->image);
	    $challengeCategory->machineName = $result->machineName;
	    $challengeCategory ->challengeOrder = $result ->challengeOrder;
//	    $challengeCategory->challengeExpertAutor = $result->challengeExpertAutor;

	    $return[] = $challengeCategory;
	}

	$time_end = microtime(true);
	$time = $time_end - $time_start;

	echo "Did fetch in $time seconds\n";

	return $return;
    }

}
