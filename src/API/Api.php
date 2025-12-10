<?php

namespace Nimbbl\Api;

class Api
{
    protected static $baseUrl = ApiConstants::BASE_URL;

    protected static $apiVersion = ApiConstants::API_VERSION;

    protected static $key;

    protected static $secret;

    protected static $token;

    protected static $merchantId;

    protected static $logFile;

    const VERSION = SdkConstants::SDK_VERSION;

    /*
     * App info is to store the Plugin/integration
     * information
     */
    public static $appsDetails = [];


    /**
     * @param string $key
     * @param string $secret
     * @param string|null $url API base URL
     * @param string|null $apiVersion API version
     * @param string|null $token Optional Bearer token (if provided, will be used instead of Basic Auth)
     * @param string|null $logFile Optional log file path (if provided, will be used for logging)
     */
    public function __construct($key, $secret, $url=null, $apiVersion = null, $token = null, $logFile = null)
    {
        self::$key = $key;
        self::$secret = $secret;
        self::$token = $token;

        if ($url !== null) {
            // Support combined base URL with version (e.g., https://api.nimbbl.tech/api/v3)
            $trimmedUrl = rtrim($url, '/');
            if ($apiVersion === null && preg_match('#/v\d+$#', $trimmedUrl)) {
                self::$baseUrl = $trimmedUrl;
                self::$apiVersion = '';
            } else {
                self::$baseUrl = $trimmedUrl;
            }
        }

        if ($apiVersion !== null) {
            self::$apiVersion = $apiVersion;
        }
        if ($logFile !== null) {
            self::setLogFile($logFile);
        }
    }

    /*
     *  Set Headers
     */
    public function setHeader($header, $value)
    {
        Request::addHeader($header, $value);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        // Users API has been removed - throw exception if accessed
        if (strtolower($name) === 'user' || strtolower($name) === 'users') {
            throw new \Exception(ErrorMessages::USERS_API_REMOVED);
        }
        
        $className = __NAMESPACE__ . '\\' . ucwords($name);
        $entity = new $className();
        return $entity;
    }

    public static function getBaseUrl()
    {
        return self::$baseUrl;
    }

    public static function getAPIVersion() {
        return self::$apiVersion;
    }

    public static function getKey()
    {
        return self::$key;
    }

    public static function getSecret()
    {
        return self::$secret;
    }

    public static function getToken()
    {
        return self::$token;
    }

    public static function getTokenEndpoint()
    {
        $baseUrl = rtrim(self::getBaseUrl(), '/');
        // Base URL already includes /api/, so just append the endpoint path
        return $baseUrl . '/' . ApiConstants::AUTH_GENERATE_TOKEN;
    }

    public static function getFullUrl($relativeUrl)
    {
        $baseUrl = rtrim(self::getBaseUrl(), '/');
        $relativeUrl = ltrim($relativeUrl, '/');
        return $baseUrl . '/' . $relativeUrl;
    }

    public static function setMerchantId($merchantId){
        self::$merchantId = $merchantId;
        return true;
    }

    public static function getMerchantId(){
        return self::$merchantId;
    }

    /**
     * Set log file path
     * 
     * @param string $logFile Path to log file
     * @return void
     */
    public static function setLogFile($logFile)
    {
        self::$logFile = $logFile;
        // Reinitialize Logger with new log file if already instantiated
        $logger = Logger::getInstance($logFile);
    }

    /**
     * Get log file path
     * 
     * @return string|null Log file path or null if not set
     */
    public static function getLogFile()
    {
        return self::$logFile;
    }

    /**
     * Get Orders API client
     * 
     * @return Order
     */
    public function orders()
    {
        return new Order();
    }

    /**
     * Get Transactions API client
     * 
     * @return Transaction
     */
    public function transactions()
    {
        return new Transaction();
    }

    /**
     * Get Refunds API client
     * 
     * @return Refund
     */
    public function refunds()
    {
        return new Refund();
    }

    /**
     * Get Addresses API client
     * 
     * @return Address
     */
    public function addresses()
    {
        return new Address();
    }

    /**
     * Get Payments API client
     * 
     * @return Payment
     */
    public function payments()
    {
        return new Payment();
    }

    /**
     * Get Payment Links API client
     * 
     * @return PaymentLink
     */
    public function paymentLinks()
    {
        return new PaymentLink();
    }

    /**
     * Get Checkout Utilities API client
     * 
     * @return CheckoutUtilities
     */
    public function checkoutUtilities()
    {
        return new CheckoutUtilities();
    }

    /**
     * Get Authentication API client
     * 
     * @return Auth
     */
    public function auth()
    {
        return new Auth();
    }

    /**
     * Get Webhook Handler
     * 
     * @return Webhook
     */
    public function webhook()
    {
        return new Webhook();
    }
}
