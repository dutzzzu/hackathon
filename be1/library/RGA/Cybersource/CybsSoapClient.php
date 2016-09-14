<?php

/**
 * CybsSoapClient
 *
 * An implementation of PHP's SOAPClient class for making CyberSource requests.
 */
class RGA_Cybersource_CybsSoapClient extends SoapClient
{
    private $merchantId;
    private $transactionKey;

    function __construct($options=array())
    {
        $config = Zend_Controller_Front::getInstance()->getParam('bootstrap');

        $this->_conf = $config->getOption('CYBERSOURCE');
        $required = array('MERCHANT_ID', 'TRANSACTION_KEY', 'WSDL');
        
        if (!$this->_conf) {
            throw new Exception('Unable to read application.ini.');
        }

        foreach ($required as $req) {
            if (empty($this->_conf[$req])) {
                throw new Exception($req . ' not found in application.ini.');
            }
        }

        parent::__construct($this->_conf['WSDL'], $options);
        $this->merchantId = $this->_conf['MERCHANT_ID'];
        $this->transactionKey = $this->_conf['TRANSACTION_KEY'];

        $nameSpace = "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd";

        $soapUsername = new SoapVar(
            $this->merchantId,
            XSD_STRING,
            NULL,
            $nameSpace,
            NULL,
            $nameSpace
        );

        $soapPassword = new SoapVar(
            $this->transactionKey,
            XSD_STRING,
            NULL,
            $nameSpace,
            NULL,
            $nameSpace
        );

        $auth = new stdClass();
        $auth->Username = $soapUsername;
        $auth->Password = $soapPassword; 

        $soapAuth = new SoapVar(
            $auth,
            SOAP_ENC_OBJECT,
            NULL, $nameSpace,
            'UsernameToken',
            $nameSpace
        ); 

        $token = new stdClass();
        $token->UsernameToken = $soapAuth; 

        $soapToken = new SoapVar(
            $token,
            SOAP_ENC_OBJECT,
            NULL,
            $nameSpace,
            'UsernameToken',
            $nameSpace
        );

        $security =new SoapVar(
            $soapToken,
            SOAP_ENC_OBJECT,
            NULL,
            $nameSpace,
            'Security',
            $nameSpace
        );

        $header = new SoapHeader($nameSpace, 'Security', $security, true); 
        $this->__setSoapHeaders(array($header)); 
    }

    /**
     * @return string The client's merchant ID.
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @return string The client's transaction key.
     */
    public function getTransactionKey()
    {
        return $this->transactionKey;
    }

    /**
     * Returns an object initialized with basic client information.
     *
     * @param string $merchantReferenceCode Desired reference code for the request
     * @return stdClass An object initialized with the basic client info.
     */
    public function createRequest($merchantReferenceCode)
    {
        $request = new stdClass();
        $request->merchantID = $this->merchantId;
        $request->merchantReferenceCode = $merchantReferenceCode;
        $request->clientLibrary = "CyberSource PHP 1.0.0";
        $request->clientLibraryVersion = phpversion();
        $request->clientEnvironment = php_uname();
        return $request;
    }
}
