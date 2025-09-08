<?php

namespace Nimbbl\Api;

class NimbblApi
{
    protected static $baseUrl = 'https://api.nimbbl.tech/api/';

    protected static $apiVersion = 'v3';

    protected static $key;

    protected static $secret;

    protected static $merchantId;

    const VERSION = '3.6.9';

    /*
     * App info is to store the Plugin/integration
     * information
     */
    public static $appsDetails = [];


    /**
     * @param string $key
     * @param string $secret
     */
    public function __construct($key, $secret, $url=null, $apiVersion = null)
    {
        NimbblLogger::getInstance()->log("__construct START - key: " . substr($key, 0, 4) . "****, url: " . ($url ?? 'default'), 'DEBUG');
        
        self::$key = $key;
        self::$secret = $secret;
        if($url != null)
            self::$baseUrl = $url;
        if($apiVersion != null)
            self::$apiVersion = $apiVersion;
            
        NimbblLogger::getInstance()->log("__construct END - baseUrl: " . self::$baseUrl . ", apiVersion: " . self::$apiVersion);
    }

    /*
     *  Set Headers
     */
    public function setHeader($header, $value)
    {
        NimbblLogger::getInstance()->log("setHeader START - header: {$header}, value: {$value}");
        \Nimbbl\Api\NimbblRequest::addHeader($header, $value);
        NimbblLogger::getInstance()->log("setHeader END");
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        NimbblLogger::getInstance()->log("__get START - name: {$name}");
        $className = __NAMESPACE__ . '\\Nimbbl' . ucwords($name);

        $entity = new $className();

        NimbblLogger::getInstance()->log("__get END - created entity: {$className}");
        return $entity;
    }

    public static function getBaseUrl()
    {
        $url = self::$baseUrl;
        NimbblLogger::getInstance()->log("getBaseUrl END - returning: {$url}");
        return $url;
    }

    public static function getAPIVersion() {
        $ver = self::$apiVersion;
        NimbblLogger::getInstance()->log("getAPIVersion END - returning: {$ver}");
        return $ver;
    }

    public static function getKey()
    {
        $key = self::$key;
        $maskedKey = $key ? substr($key, 0, 4) . str_repeat('*', max(0, strlen($key) - 8)) . substr($key, -4) : 'null';
        NimbblLogger::getInstance()->log("getKey END - returning masked key: {$maskedKey}");
        return $key;
    }

    public static function getSecret()
    {
        $secret = self::$secret;
        $maskedSecret = $secret ? substr($secret, 0, 4) . str_repeat('*', max(0, strlen($secret) - 8)) . substr($secret, -4) : 'null';
        NimbblLogger::getInstance()->log("getSecret END - returning masked secret: {$maskedSecret}");
        return $secret;
    }

    public static function getTokenEndpoint()
    {
        NimbblLogger::getInstance()->log("getTokenEndpoint START");
        $baseUrl = rtrim(self::getBaseUrl(), '/');
        $apiVersion = ltrim(self::getAPIVersion(), '/');
        $endpoint = $baseUrl . '/' . $apiVersion . '/generate-token';
        NimbblLogger::getInstance()->log("getTokenEndpoint END - returning: {$endpoint}");
        return $endpoint;
    }

    public static function getFullUrl($relativeUrl)
    {
        NimbblLogger::getInstance()->log("getFullUrl START - relativeUrl: {$relativeUrl}");
        $baseUrl = rtrim(self::getBaseUrl(), '/');
        $relativeUrl = ltrim($relativeUrl, '/');
        $url = $baseUrl . '/' . $relativeUrl;
        NimbblLogger::getInstance()->log("getFullUrl END - returning: {$url}");
        return $url;
    }

    public static function setMerchantId($merchantId){
        self::$merchantId = $merchantId;
        NimbblLogger::getInstance()->log("setMerchantId END - subMerchantId: {$merchantId}");
        return true;
    }

    public static function getMerchantId(){
        $id = self::$merchantId;
        NimbblLogger::getInstance()->log("getMerchantId END - returning subMerchantId: " . ($id ?? 'null'));
        return $id;
    }
}
