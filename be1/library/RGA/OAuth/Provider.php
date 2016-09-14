<?php 
class RGA_OAuth_ProviderException extends Exception {}

use RGA_OAuth_ProviderException as ProviderException;
use Application_Model_OAuth_Consumer as Consumer;
use Application_Model_OAuth_RequestToken as RequestToken;
use Application_Model_OAuth_AccessToken as AccessToken;
use Application_Model_OAuth_Nonce as Nonce;

class RGA_OAuth_Provider
{

	const TOKEN_REQUEST = 0;
	const TOKEN_ACCESS	= 1;
	const TOKEN_VERIFY	= 2;

	private $_provider;

	public function __construct($mode)
	{
		$this->_provider = new OAuthProvider();
		$this->_provider->consumerHandler(array($this,'consumerHandler'));
		$this->_provider->timestampNonceHandler(array($this,'timestampNonceHandler'));

		if ($mode == self::TOKEN_REQUEST) {

			$this->_provider->isRequestTokenEndpoint(true);
			//enforce the presence of these parameters
			$this->_provider->addRequiredParameter("oauth_callback");
			$this->_provider->addRequiredParameter("scope");

		} else if ($mode == self::TOKEN_ACCESS) {

			$this->_provider->tokenHandler(array($this,'checkRequestToken'));

		} else if ($mode == self::TOKEN_VERIFY) {

			$this->_provider->tokenHandler(array($this,'checkAccessToken'));

		}
	}

	public function getConsumer() {
		return Consumer::one(array('consumer_key' => $this->_provider->consumer_key));
	}
	/**
	 * Uses OAuthProvider->checkOAuthRequest() which initiates the callbacks and checks the signature
	 *
	 * @return bool|string
	 */
	public function checkOAuthRequest()
	{
		try {
			$this->_provider->checkOAuthRequest();
			//error_log('Checked.');
		} catch (Exception $e) {
			// error_log('Fail: ' . $e->getMessage() . $this->_provider->reportProblem($e) . print_r($_SERVER, 1));
			return OAuthProvider::reportProblem($e);
		}
		return true;
	}

	/**
	 * Wrapper around OAuthProvider::generateToken to add sha1 hashing at one place
	 * @static
	 * @param 	bool $sha1
	 * @return 	string
	 */
	public static function generateToken()
	{
		$fp = fopen('/dev/urandom','rb');
        $entropy = fread($fp, 32);
        fclose($fp);
		$token = $entropy .= uniqid(mt_rand(), true);
		return sha1($token);
	}

	/**
	 * Generates and outputs a request token
	 * @throws P
	 */
	public function outputRequestToken()
	{
		
		$token 			= self::generateToken();
		$tokenSecret 	= self::generateToken();
		$requestToken 	= new RequestToken();

		$requestToken->token = $token;
		$requestToken->secret = $tokenSecret;
		$requestToken->date = time();
		$requestToken->consumer_key = $this->_provider->consumer_key;
		$requestToken->callback = $_GET['oauth_callback'];
		$requestToken->scope = $_GET['scope'];

		try {
			$requestToken->save();
		} catch (Exception $e) {
			error_log('Failed to save request token: ' . $e->getMessage());
			throw new ProviderException($e->getMessage());
		}

		echo "oauth_token=$token&oauth_token_secret=$tokenSecret&oauth_callback_confirmed=true";
	}

	/**
	 * Tests if the provided RequestToken meets the RFC specs and if so creates and outputs an AccessToken
	 *
	 * @throws ProviderException
	 */
	public function outputAccessToken()
	{
		
		$token 			= self::generateToken();
		$tokenSecret 	= self::generateToken();
		$accessToken 	= new AccessToken();
		$requestToken	= RequestToken::fromToken($this->_provider->token);

		$accessToken->token = $token;
		$accessToken->secret = $tokenSecret;
		$accessToken->date = time();
		$accessToken->consumer_key = $this->_provider->consumer_key;
		$accessToken->user_id = $requestToken->user_id;
		$accessToken->scope = $requestToken->scope;

		try {
			$accessToken->save();
		} catch (Exception $e) {
			error_log('Failed to save access token: ' . $e->getMessage());
			throw new ProviderException($e->getMessage());
		}

		//The access token was saved. This means the request token that was exchanged for it can be deleted.
		try {
			$requestToken->delete();
		} catch (Exception $e) {
			error_log('Failed to delete request token: ' . $e->getMessage());
			throw new ProviderException($e->getMessage());
		}

		//all is well, output token
		echo "oauth_token=$token&oauth_token_secret=$tokenSecret";
	}

	/**
	 * Returns the user Id for the currently authorized user
	 *
	 * @throws ProviderException
	 * @return int
	 */
	public function getUserId()
	{
		try {
			$accessToken = AccessToken::fromToken($this->_provider->token);
		} catch (Exception $e) {
			error_log('Failed to get user id: ' . $e->getMessage());
			throw new ProviderException("Couldn't find a user id corresponding with current token information");
		}
		return $accessToken->user_id;
	}

	/**
	 * Checks if the nonce is valid and, if so, stores it in the DataStore.
	 * Used as a callback function
	 *
	 * @param  $_provider
	 * @return int
	 */
	public static function timestampNonceHandler($_provider)
	{
		// Timestamp is off too much (5 mins+), refuse token
		$now = time();
		if ($now - $_provider->timestamp > 300) {
			error_log('OAUTH_BAD_TIMESTAMP: $now - $_provider->timestamp > 300');
			return OAUTH_BAD_TIMESTAMP;
		}

		if (Nonce::one(array('nonce' => $_provider->nonce))) {
			error_log("OAUTH_BAD_NONCE: {$_provider->nonce}");
			return OAUTH_BAD_NONCE;
		}

		$nonce = new Nonce();
		$nonce->nonce = $_provider->nonce;
		$nonce->consumer_key = $_provider->consumer_key;
		$nonce->date = $now;

		try {
			$nonce->save();
		} catch (Exception $e) {
			error_log("OAUTH_BAD_NONCE: {$nonce->nonce}");
			return OAUTH_BAD_NONCE;
		}

		return OAUTH_OK;
	}

	/**
	 * Checks if the provided consumer key is valid and sets the corresponding
	 * consumer secret. Used as a callback function.
	 *
	 * @static
	 * @param 	$_provider
	 * @return 	int
	 */
	public static function consumerHandler($_provider)
	{
		try {
			$consumer = Consumer::one(array('consumer_key' => $_provider->consumer_key));
                        if(!$consumer) { 
                            throw new ProviderException(OAUTH_CONSUMER_KEY_UNKNOWN);
                            return OAUTH_CONSUMER_KEY_UNKNOWN;
                        }    
		} catch (Exception $e) {
			error_log("OAUTH_CONSUMER_KEY_UNKNOWN: " . $e->getMessage());
			return OAUTH_CONSUMER_KEY_UNKNOWN;
		}

		$_provider->consumer_secret = $consumer->consumer_secret;
		return OAUTH_OK;
	}

	/**
	 * Checks if there is token information for the provided token and sets the secret if it can be found.
	 *
	 * @static
	 * @param 	$_provider
	 * @return 	int
	 */
	public static function checkRequestToken($_provider)
	{

		//Token can not be loaded, reject it.
		try {
			$requestToken = RequestToken::one(array('token'=>$_provider->token));
		} catch (Exception $e) {
			error_log("OAUTH_TOKEN_REJECTED: " . $e->getMessage());
			return OAUTH_TOKEN_REJECTED;
		}

		//The consumer must be the same as the one this request token was originally issued for
		if (!isset($requestToken->consumer_key) || $requestToken->consumer_key != $_provider->consumer_key) {
			error_log("OAUTH_TOKEN_REJECTED: consumer keys dont match");
			return OAUTH_TOKEN_REJECTED;
		}

		if (!$requestToken) {
			error_log("OAUTH_TOKEN_REJECTED: no request token");
			return OAUTH_TOKEN_REJECTED;
		}

		//Check if the verification code is correct.
		if ($_GET['oauth_verifier'] != $requestToken->verification_code) {
			error_log('OAUTH_VERIFIER_INVALID: $_GET[oauth_verifier] : ' . $_GET['oauth_verifier'] . ' - $requestToken->verification_code : ' . $requestToken->verification_code);
			return OAUTH_VERIFIER_INVALID;
		}

		$_provider->token_secret = $requestToken->secret;
		return OAUTH_OK;
	}

	/**
	 * Checks if there is token information for the provided access token and sets the secret if it can be found.
	 *
	 * @static
	 * @param 	$_provider
	 * @return 	int
	 */
	public static function checkAccessToken($_provider)
	{
		//Try to load the access token
		try {
			$accessToken = AccessToken::one(array('token' => $_provider->token));
		} catch (Exception $e) {
			error_log("OAUTH_TOKEN_REJECTED: " . $e->getMessage());
			return OAUTH_TOKEN_REJECTED;
		}
		//The consumer must be the same as the one this request token was originally issued for
		if (!isset($accessToken->consumer_key) || $accessToken->consumer_key != $_provider->consumer_key) {
			error_log("OAUTH_TOKEN_REJECTED: " . '$accessToken->consumer_key != $_provider->consumer_key');
			return OAUTH_TOKEN_REJECTED;
		}

		$_provider->token_secret = $accessToken->secret;
		return OAUTH_OK;
	}
}