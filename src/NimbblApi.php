<?php

namespace Nimbbl\Api;

class NimbblApi
{
    protected static $baseUrl = 'https://api.nimbbl.tech/api/';

    protected static $key;

    protected static $secret;

    protected static $merchantId;

    /*
     * App info is to store the Plugin/integration
     * information
     */
    // public static $appsDetails = array();

    const VERSION = '1.0.0';

    /*
     * App info is to store the Plugin/integration
     * information
     */
    public static $appsDetails = [];

    /**
     * @param string $key
     * @param string $secret
     */
    public function __construct($key, $secret, $url=null)
    {
        self::$key = $key;
        self::$secret = $secret;
        if($url != null)
            self::$baseUrl = $url;
    }

    /*
     *  Set Headers
     */
    public function setHeader($header, $value)
    {
        Request::addHeader($header, $value);
    }

    // public function setAppDetails($title, $version = null)
    // {
    //     $app = array(
    //         'title' => $title,
    //         'version' => $version
    //     );

    //     array_push(self::$appsDetails, $app);
    // }

    // public function getAppsDetails()
    // {
    //     return self::$appsDetails;
    // }

    // public function setBaseUrl($baseUrl)
    // {
    //     self::$baseUrl = $baseUrl;
    // }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $className = __NAMESPACE__ . '\\Nimbbl' . ucwords($name);

        $entity = new $className();

        return $entity;
    }

    public static function getBaseUrl()
    {
        return self::$baseUrl;
    }

    public static function getKey()
    {
        return self::$key;
    }

    public static function getSecret()
    {
        return self::$secret;
    }

    public static function getTokenEndpoint()
    {
        return self::getBaseUrl() . 'v2/generate-token';
    }

    public static function getFullUrl($relativeUrl)
    {
        return self::getBaseUrl() . $relativeUrl;
    }

    public static function setMerchantId($merchantId){
        self::$merchantId = $merchantId;
        return true;
    }

    public static function getMerchantId(){
        return self::$merchantId;
    }
}
