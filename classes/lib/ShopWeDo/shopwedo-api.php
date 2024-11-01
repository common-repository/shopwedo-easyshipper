<?php 
/**
 * @link       https://www.shopwedo.com
 * @since      1.1.6
 *
 * @package    woocommerce-shopwedo
 */

/**
 * ShopWeDo API class
 * 
 * Made for the plugin. (alpha)
 * 
 * @link       https://admin.shopwedo.com/developer/
 * @version    1.0b
 */

class ShopWeDoApi
{

	CONST WEBSERVICE_ENDPOINT = 'https://admin.shopwedo.com/api/';

	/**
	 * The shopId
	 * @access private
	 * @var string
	 */
	private $shopId;

	/**
	 * The shopKey
	 * @access private
	 * @var string
	 */
	private $shopKey;


	/**
	 * The method
	 * @access private
	 * @var string
	 */
	private $method;
	private $available_methods = array('pudo');

    private $_lastResponseCode = null;
    private $_lastResponseUrl = null;
    private $_lastResponse = null;
	  

    /**
     * Login
     */
    public function __construct($shopId, $shopKey, $method=null)
    {
        $this->setShopId($shopId);
        $this->setShopKey($shopKey);

        $this->setMethod($method);
    }

    public function get($params=array())
    {
    	if($this->_validateParameters($params)){
            try {
                return $this->_doRequest($params);
            } catch (Exception $e) {
                return array('error'=>$e->getMessage());
            }
    	}
    	return array('error'=>'Couldn\'t request due to invalid parameters.');
    }


    public function getUrl()
    {
    	return self::WEBSERVICE_ENDPOINT.$this->getMethod();
    }

    public function setMethod($method)
    {
    	if(
    		$this->method != $method
    	){
	      $this->method = in_array(strtolower($method), $this->available_methods) ? strtolower($method) : null;
	    }
    }

    public function getMethod()
    {
    	return $this->method;
    }

    public function setShopId($shopId)
    {
    	if($this->shopId != $shopId)
	    {
	      $this->shopId = $shopId;
	    }
    }


    public function getShopId()
    {
    	return $this->shopId;
    }


    public function setShopKey($shopKey)
    {
    	if($this->shopKey != $shopKey)
	    {
	      $this->shopKey = $shopKey;
	    }
    }

    public function getShopKey()
    {
    	return $this->shopKey;
    }

    private function _authObject()
    {
        $shopId = $this->getShopId();
        $shopKey = $this->getShopKey();
        $timestamp = time();
        $salt = uniqid();
        $token = hash_hmac('sha512', $shopId.$shopKey.$timestamp.$salt, $shopKey);
        return json_encode(array(
            'shopid' => $shopId,
            'timestamp' => $timestamp,
            'salt' => $salt,
            'token' => $token
        ));
    }

    public function _doRequest($data=null)
    {
        if( 
            (is_array($data) || is_object($data))
            && $jsonizedData = json_encode($data)
        ) {
            

            $this->_resetLastResponse();

            $postFields = array(
                'auth' => $this->_authObject(),
                'data' => $jsonizedData,
            );


            $url = $this->getUrl();
            $this->_setLastResponseUrl($url);

            $options = array(
                CURLOPT_URL => $url,

                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $postFields,

                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_RETURNTRANSFER => 1,

                CURLOPT_TIMEOUT => 10,
                CURLOPT_FAILONERROR => true,
                CURLOPT_FRESH_CONNECT => true,

                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
            );

            $ch = curl_init();
            curl_setopt_array($ch, $options);

            $ch_result = curl_exec($ch);
            if($response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                $this->_setLastResponseCode($response_code);    
            } else {
                $this->_setLastResponseCode();
            }

            curl_close($ch);

            

            if($decodedResult = json_decode($ch_result, true)){
                return $decodedResult;
            } else {
                return array('error'=>json_last_error());
            }

        }
        return array('error'=>'Request failed.');
    }


    private function _setLastResponseCode($status=null)
    {
        $this->_lastResponseCode = $status;
    }

    public function getLastResponseCode()
    {
        return $this->_lastResponseCode;
    }

    private function _setLastResponseUrl($url=null)
    {
        $this->_lastResponseUrl = $url;
    }

    public function getLastResponseUrl()
    {
        return $this->_lastResponseUrl;
    }

    private function _setLastResponse($response=null)
    {
        $this->_lastResponse = $response;
    }

    public function getLastResponse()
    {
        return $this->_lastResponse;
    }

    private function _resetLastResponse()
    {
        $this->_setLastResponseCode();
        $this->_setLastResponseUrl();
        $this->_setLastResponse();
    }

    private function _validateParameters($params=null)
    {
        $valid = false;
        switch ($this->getMethod()) {
            case 'pudo':
                // mandatory = zip,country
                if(!empty($params['address']['zip']) && !empty($params['address']['country'])){
                    $valid = true;
                }
                break;
        }
        return $valid;
    }


}