<?php

namespace RGA;
use \Exception as Exception;
use \RGA\Marshallable_Interface as Marshallable;
use Application_Model_UserProgressLastUpdate as Progress;
use ReflectionClass as ReflectionClass;
class Model extends \Shanty_Mongo_Document implements Marshallable {

	const PRIVACY_LEVEL_PRIVATE = 0;
	const PRIVACY_LEVEL_SB = 1;
	const PRIVACY_LEVEL_PUBLIC = 2;

    public function marshall() {
        $doc = $this->export();
        $doc['id'] = $doc['_id']->{'$id'};
        unset($doc['_id']);
        unset($doc['_type']);
        return $doc;
    }

    protected function preUpdate() {
        $this->updated = time();
        $this->setProgress();
    }  

    protected function preInsert() {
        $this->created = time();
        $this->setProgress();
    }

    public function updateChallengeSupportItem($data,$item_type) {
        if(!isset($data["user_challenge_id"]) || !isset($data["step_nid"]) || !isset($data["activity_nid"])) return false;
        $ucid = $data["user_challenge_id"];
        $step_nid = $data["step_nid"];
        $activity_nid = $data["activity_nid"];
        $user_challenge = \Application_Model_UserChallenge::find(new \MongoId($ucid));
        $arrChallenge = $user_challenge->export();
        $arrChallenge["complete_status"]["step_".$step_nid]["activity_".$activity_nid][$item_type]["completed"] = true;
        $user_challenge->complete_status = $arrChallenge["complete_status"];

        $user_challenge->save();

        return true;
    }

    public static function isCaseForIdidIt($from_flags,$uc_id=false,$c_nid=false,$s_nid=false,$a_nid=false) {
        $response = true;
        if($from_flags["user_challenge_id"] != $uc_id) $response = false;
        if($from_flags["challenge_nid"] != $c_nid) $response = false;
        if($from_flags["step_nid"] != $s_nid) $response = false;
        if($from_flags["activity_nid"] != $a_nid) $response = false;

        return $response;
    }

    protected function setProgress() {
        $arrBlackList = array("user_progress_last_update"); //don't trigger an update if operation is done in these collections

        $collection = $this->getConfigAttribute("collection");
        if($collection) {
            if (isset($this->user_id) && !in_array($collection,$arrBlackList)) {
                Progress::setTimestamp($this->user_id, $collection);
            }
            if ($collection == "user") { //comment this if changes in user collection shouldn't trigger an update
                Progress::setTimestamp($this->getId(), $collection);
            }
        }
    }

    public function getPublicAtributes(){
        $reflector = new ReflectionClass(get_called_class());
        $properties = $reflector->getProperties();
        foreach($properties as $k => $prop){
            if($prop->isPublic() && strpos($prop->getName(), 'prop_') !== false){
                $data[] = $prop->getValue();
            }
        }
        return $data;
    }

    public function validateArrayKey($array, $key) {
        if(isset($array[$key]) && !empty($array[$key])){
            return $array[$key];
        }
    }
}
