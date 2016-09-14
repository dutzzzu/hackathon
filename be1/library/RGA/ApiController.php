<?php

use Application_Model_AddDrPhilAccess as drPhilAccess;

class RGA_ApiController extends \REST_Controller
{
    protected $_identity;
    protected $_auth;
    protected $_isLoggedIn = false;
    protected $_requestParams;
    protected $_modelClassName = null;
    protected $_maxPhotoSize = 2; //in MB (note that if the photos will have to be much larger, the server settings will have to be modified as well, eg. upload_max_filesize = 8M in php.ini)
    protected $_mimeTypeWhitelist = array("image/jpeg","image/png","image/gif","image/pjpeg","image/x-png");

    public function init()
    {
        if(isset($GLOBALS["profiling"])) { $callers=debug_backtrace(); $GLOBALS["profiling"]->mark("API controller start init",get_called_class(),$callers[1]['function']); }
        $this->_auth = \Zend_Auth::getInstance();
        $hasIdentity = $this->_auth->hasIdentity();
       
        if ($hasIdentity) {
            $this->_identity = $this->_auth->getIdentity();
            if ($this->_identity) {
                $this->_isLoggedIn = true;
            }
        }
        
        if ($this->_isLoggedIn) {
            $this->isLoggedIn = $this->_isLoggedIn;
        } else {
            $this->getResponse()->notAllowed();
        }
        
        // extract request params
        $this->_requestParams = $this->getRequest()->getParams();
    }

    public function indexAction()
    {
        throw new REST_Exception('Resource not available', REST_Response::NOT_FOUND);
    }
     
    public function getAction()
    {
        $id = new MongoId($this->_getParam('id'));
        $model = call_user_func_array(array($this->_modelClassName, 'find'), array($id));
        $this->_setResponseData($model);
    }
     
    public function postAction()
    {
        if (!$this->_handleFileUploadButtonRequest()) {
            $this->_handleFileUploadButtonRequest();
            $body = $this->getRequest()->getRawBody();
            $emptyModel = new $this->_modelClassName(); // this won't work for inherited models
            $model = call_user_func_array(array($this->_modelClassName, 'fromJSON'), array($body, &$emptyModel));
            if ($model instanceof $this->_modelClassName) {
                $model->user_id = (string) $this->_identity->getId();
                // TODO check for permissions
                $model->save();
                $this->_setResponseData($model);
            } else {
                throw new REST_Exception(null, REST_Response::NOT_ACCEPTABLE);
            }
        }
    }
     
    public function putAction() {
        if (!$this->_handleFileUploadButtonRequest()) {
            $body = $this->getRequest()->getRawBody();
            $old_model_id = new \MongoId($this->_getParam('id'));
            $old_model = call_user_func_array(array($this->_modelClassName, 'find'), array($old_model_id));
            if ($old_model instanceof $this->_modelClassName) {
                $old_model_user_id = $old_model->user_id;
                $modelClassName = get_class($old_model);
                $emptyModel = new $modelClassName();
                $model = call_user_func_array(array($this->_modelClassName, 'fromJSON'), array($body, &$emptyModel));
                $model->user_id = $old_model_user_id; // The ownership continue being the same
                $model->setId($old_model_id);
                $model->save();
                $this->_setResponseData($model);
            } else {
                throw new REST_Exception('Invalid resource, or resource not found', REST_Response::BAD_REQUEST);
            }
        }
    }

    public function patchAction()
    {
        $id = $this->_getParam('id');
        $model = call_user_func_array(array($this->_modelClassName, 'find'), array($id));
        if ($model instanceof $this->_modelClassName) {
            if ($body = json_decode($this->getRequest()->getRawBody(), 1)) {
                if (is_array($body) || is_object($body)) {
                    foreach ($body as $key => $value) {
                        $model->{$key} = $value;
                    }
                }
                // TODO check for permissions
                $model->save();
            }
            $this->_setResponseData($model);
        } else {
            throw new REST_Exception('Invalid resource, or resource not found', REST_Response::BAD_REQUEST);
        }
    }
     
    public function deleteAction()
    {
        $id = new MongoId($this->_getParam('id'));
        $model = call_user_func_array(array($this->_modelClassName, 'find'), array($id));
        if ($model instanceof $this->_modelClassName) {
            // TODO check for permissions
            $model->delete();
            $this->_setResponseData(null);
        } else {
            throw new REST_Exception('Invalid resource, or resource not found', REST_Response::BAD_REQUEST);
        }
    }


    protected function _handleFileUploadButtonRequest() {
        if ($this->_getParam('file-upload-button-call')) {
            $attribute = $this->_getParam('attribute');
            $file = $this->_getFile($attribute);
            $windowEvent = $this->_getParam('window-event');

            if ($file->size > $this->_maxPhotoSize*1000*1024) {
                $response = array('success' => false, 'data' => array('message' => 'File size exceeds 2MB.', 'code' => 'exceeded_max_file_size'));
            } elseif (!in_array($file->mime, $this->_mimeTypeWhitelist)) {
                $response = array('success' => false, 'data' => array('message' => 'Invalid file type', 'code' => 'invalid_file_type', 'accept' => $this->_mimeTypeWhitelist));
            } else {
                try {
                    $fileModel = new Application_Model_File();
                    $fileModel->user_id = $this->_identity->getId()->{'$id'};
                    $fileModel->mime = $file->mime;
                    $fileModel->size = $file->size;
                    $fileModel->name = $file->name;
                    $fileModel->extension = $file->extension;
                    $fileModel->content = $file->content;
                    $fileModel->save();

                    $model = null;
                    if ($this->_getParam('id')) { // PUT
                        $id = new \MongoId($this->_getParam('id'));
                        $model = call_user_func_array(array($this->_modelClassName, 'find'), array($id));
                    } else { // POST

                        $body = $this->_getParam('body', "");
                        $emptyModel = new $this->_modelClassName(); // this won't work for inherited models
                        $model = call_user_func_array(array($this->_modelClassName, 'fromJSON'), array($body, &$emptyModel));
                        if ($model instanceof $this->_modelClassName) {
                            $model->user_id = (string)$this->_identity->getId();
                        }
                    }

                    if ($model) {
                        // TODO check for permissions
                        $model->$attribute = array('file_id' => $fileModel->getId()->{'$id'});
                        $model->save();
                        $response = array('success' => true, 'data' => $model->marshall());
                        $this->getResponse()->accepted();
                    } else {
                        $response = array('success' => false, 'data' => array('message' => 'Error when trying to save the resource.'));
                        $this->getResponse()->serverError();
                    }
                } catch (Exception $e) {
                    $response = array('success' => false, 'data' => array('message' => print_r($e, 1) . 'Invalid resource, or resource not found'));
                    $this->getResponse()->badRequest();
                }
            }

            $json_response = json_encode($response);
//            $inArray = array_search($fileModel->extension, array("jpg", "jpeg", "gif", "png") );
            $this->getResponse()->setHeader('Content-Type', 'text/html', true);
            $this->getResponse()->appendBody(
<<<HTML
<!doctype html>
<html>
<script type="text/javascript">
    var w = window.parent;
    w.$(w).trigger('{$windowEvent}', [{$json_response}]);
</script>
</html>
HTML
            );
            return true;
        }
        return false;
    }

    protected function _getFile($paramName) {
        $file = $this->getRequest()->getParam($paramName);
        if ($file['error']) {
            error_log('Error trace \$file: ' . print_r($file, true));
            throw new \Exception("Error when trying to get an uploaded file. - API::Controller");
        }

        // some servers doesn't auto recognize the mime attribute
        if (!$file['type'] && function_exists('finfo_open')) {
            $mime = finfo_open(FILEINFO_MIME);
            if ($mime === false) {
                throw new \Exception('Unable to open finfo');
            }
            $filetype = finfo_file($mime, $file['tmp_name']);
            finfo_close($mime);
            if ($filetype === false) {
                throw new \Exception('Unable to recognise filetype');
            }
            $file['type'] = strtok($filetype, ";");
        } elseif (!$file['type']) {
            $file['type'] = 'application/octet-stream';
        }

        return (object) array(
            'mime' => $file['type'],
            'size' => $file['size'],
            'name' => $file['name'],
            'extension' => strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)),
            'content' => $file['content']
        );
    }

    protected function _paginateCollection($model) {
        $ipp = isset($this->_requestParams['ipp']) ? (int) $this->_requestParams['ipp'] : 10;
        $page = isset($this->_requestParams['page']) ? (int) $this->_requestParams['page'] : 1;
        $offset = isset($this->_requestParams['offset']) ? (int) $this->_requestParams['offset'] : 0;
        $skip = ($page -1) * $ipp + $offset;
        $sort = array('created' => -1);
        return $model->sort($sort)->skip($skip)->limit($ipp);
    }

    protected function _nsTranslate($ns, $token) {
        $langs = Zend_Registry::get('file_cache')->load('i18n_languages');
        return $langs['en'][$ns][$token];
    }

    protected function _getParams($allowedParams = array(), $throwException = true) {
        $params = $this->getRequest()->getParams();
        foreach ($params as $key => $value) {
            if (!in_array($key, $allowedParams) || !isset($value)) {
                unset($params[$key]);
            }        
        }
        if (!$params && $throwException) {
            throw new REST_Exception('Missing params, expecting at least one of these: ' . implode(', ', $allowedParams), REST_Response::BAD_REQUEST);
        }
        return $params;
    }

    /*
     *  This method handles the happy path, all responses are 2xx
     */
    protected function _setResponseData($responseData = null, $marshallingType = null, $consistent_http_answer=false) {
        $request = $this->getRequest();

        $isResponseDataEmpty = false;
        if (!isset($responseData)) {
            $isResponseDataEmpty = true;
        } elseif (is_array($responseData) && empty($responseData)) {
            $isResponseDataEmpty = true;
        } elseif (($responseData instanceof Shanty_Mongo_Iterator_Cursor || $responseData instanceof Shanty_Mongo_Collection) && !$responseData->count()) {
            $isResponseDataEmpty = true;
        }

        switch ($request->getActionName()) {
            case 'index':
                $status = REST_Response::OK;
                break;
            case 'get':
                if (!$isResponseDataEmpty) {
                    $status = REST_Response::OK;
                } else {
                    if(!$consistent_http_answer)
                    throw new REST_Exception('Resource not found', REST_Response::NOT_FOUND);
                }
                break;
            case 'post':
                if (!$isResponseDataEmpty) {
                    $status = REST_Response::CREATED;
                } else {
                    $status = REST_Response::NO_CONTENT;
                }
                break;
            case 'put':
            case 'patch':
                if (!$isResponseDataEmpty) {
                    $status = REST_Response::ACCEPTED;
                } else {
                    $status = REST_Response::NO_CONTENT;
                }
                break;
            case 'delete':
                $status = REST_Response::OK;
                break;
            default:
                $status = REST_Response::OK;
        }
        
        if($consistent_http_answer) $status = REST_Response::OK;
        $responseData = $this->parseOutput($responseData);

        $response = null;
        if ($responseData instanceof Shanty_Mongo_Document) {
            $response = new RGA_Api_ResponseObject($this->getRequest(), $responseData, $status, null, $marshallingType);
        } elseif ($responseData instanceof Shanty_Mongo_Collection || $responseData instanceof Shanty_Mongo_Iterator_Cursor) {
            $response = new RGA_Api_ResponseCollection($this->getRequest(), $responseData, $status, null, $marshallingType);
        } elseif (is_object($responseData)) {
            $response = new RGA_Api_ResponseObject($this->getRequest(), $responseData, $status, null, $marshallingType);
        } elseif (is_array($responseData)) {
            $response = new RGA_Api_ResponseCollection($this->getRequest(), $responseData, $status, null, $marshallingType);
        } else {
            $response = new RGA_Api_ResponseObject($this->getRequest(), null, $status, null);
        }
        $this->getResponse()->setHttpResponseCode($status);
        $response->set($this->view);
    }

    protected function handleUserRedirects($params=array()) {

        $redirect = $this->_goToReferrer($params);

        return $redirect;
    }    
    
    protected function _goToReferrer($params = array()) { //for frontend controller redirection should check /src/library/RGA/Controller.php -> _goToReferrer()
        $this->_auth = \Zend_Auth::getInstance();
        $hasIdentity = $this->_auth->hasIdentity();
        
        $this->_oauthSession = new \Zend_Session_Namespace('third_party_consumer_session');
        $session = \Zend_Registry::get('referrer_tracking_session');
        $redirect_session = new \Zend_Session_Namespace('redirect_session');                        
        $redirect = '/';
        if ($hasIdentity) {
            $redirect = '/dashboard';
        }

        if ($session->referrer && strpos($session->referrer, 'api/') === false) {
            $redirect = $session->referrer;
            $session->referrer = false;
        }
        
        if(isset($redirect_session->url) && $redirect_session->url) {
            $redirect = $redirect_session->url;
            $redirect_session->unsetAll();
        }        

        if (@$this->_oauthSession->consumer->callback_url) {
            $redirect = $this->_oauthSession->consumer->callback_url;
        }

        if (@$this->_oauthSession->callback) {
            $redirect = $this->_oauthSession->callback;
        }

        if($hasIdentity) {
            $no_redirect = array("/user/login","/user/sign-up","/user/link-accounts","/user/social-sign-up");
            $default_redirect = '/dashboard';
            foreach($no_redirect as $value) {
                if(strpos($redirect,$value)!==false) $redirect = $default_redirect;
            }
        }
        
        if(isset($params)) {
        	$parts=explode("?",$redirect);
        	foreach ($params as $k=>$v){
        		if(!isset($parts[1])){
        			$parts[1]=$k."=".$v;
        		}else{
        			$parts[1]=implode("&",array($parts[1],$k."=".$v));
        		}
        	}
        	$redirect=implode("?",$parts);
        }
        $redirect = $this->wofCheckRedirect($redirect);
        
        return $redirect;
    }
    
    public function wofCheckRedirect($redirect) {
	if (isset($_SESSION["faith_ref"]) && !empty($_SESSION["faith_ref"])) {
	    $findme   = 'quiz-result';
	    $pos = strpos($redirect, $findme);
	    if($pos !== false){
            $redirect  = $_SESSION["faith_ref"];
		    unset($_SESSION["faith_ref"]);
                    $redirect = ltrim($redirect,'/');
                    $redirect = '/'.$redirect;
		    return $redirect;
	    }
	}
	return $redirect;
    }

    protected function parseOutput($responseData) {
        $helper = \Zend_Controller_Action_HelperBroker::getStaticHelper('responseParser');

        $arrProps = $helper->getObjectProps($responseData);
        if(isset($arrProps) && is_array($arrProps)) foreach($arrProps as $prop) {
            if(isset($responseData->{$prop}))
                $responseData->{$prop} = $helper->parseResponseRecursive($responseData->{$prop});
        }

        if(is_array($responseData)) {
            foreach($responseData as &$value) {
                $value = $helper->parseResponseRecursive($value);
            }
        }
        return $responseData;
    }
    public function drPhilAccess(){

        $emails=drPhilAccess::all();
        if ($this->_identity) {
            $access = false;
            foreach($emails as $email){
                if($email->email == $this->_identity->email){
                    $access = true;
                }
            }
            if($access == false){
                throw new REST_Exception('Resource not available', REST_Response::NOT_FOUND);
            }
        }
        else
        {
            throw new REST_Exception('Resource not available', REST_Response::NOT_FOUND);
        }
    }

    public function postDispatch() {
        if ($this->_identity) {
            \Application_Model_UserProgressLastUpdate::saveUserData((string)$this->_identity->getId());
        }
    }

    /*

    REFACTOR CHECKLIST

        ** |-ActivityController.php*
        ** |-ActivityResultController.php*
        ** |-ActivityStatController.php*
        ** |-ArchetypeController.php*
        |-AuthController.php*
        ** |-CacheController.php*
        ** |-ConversationController.php*
        ** |-DiagnosticController.php*
        |-ErrorController.php*
        ** |-FileController.php*
        ** |-HowToController.php*
        ** |-I18nController.php*
        ** |-IndexController.php*
        ** |-InspirationController.php*
        ** |-InviteController.php*
        ** |-LikeController.php*
        ** |-MeController.php*
        ** |-MenteeController.php*
        ** |-MentorController.php*
        ** |-MessageController.php*
        ** |-NotificationController.php*
        ** |-OauthConsumerController.php*
        ** |-StatsController.php*
        ** |-TipController.php*
        ** |-TrackingController.php*
        ** `-UserController.php*

    */

}
