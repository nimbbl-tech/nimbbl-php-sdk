<?php

namespace Nimbbl\Api\RestClient;

use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\ErrorMessages;
use Nimbbl\Api\Log\Logger;
use Nimbbl\Api\Services\Order;
use Nimbbl\Api\Services\Transaction;
use Nimbbl\Api\Services\Addresses;
use Nimbbl\Api\Services\Payment;
use Nimbbl\Api\Services\PaymentLink;
use Nimbbl\Api\Services\CheckoutUtilities;
use Nimbbl\Api\Services\Auth;
use Nimbbl\Api\Services\Refund;
use Nimbbl\Api\Common\SignatureVerifier;

class NimbblClient
{
    protected static $baseUrl = ApiConstants::BASE_URL;

    protected static $apiVersion = ApiConstants::API_VERSION;

    protected static $key;

    protected static $secret;

    protected static $token;

    protected static $merchantId;

    protected static $logFile;

    /**
     * Enable encryption for outgoing request payloads.
     * Mirrors .NET SDK `encryptPayload` flag (ENCRYPT_PAYLOAD).
     *
     * @var bool
     */
    protected static $encryptPayload = false;

    /**
     * Prevent duplicate "ApiClient initialized" logs.
     * In the .NET sample app, this is effectively logged once at application startup.
     *
     * @var bool
     */
    protected static $initLogEmitted = false;

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
     * @param bool $encryptPayload Optional: enable encryption for outgoing payloads (default false)
     */
    public function __construct($key, $secret, $url = null, $apiVersion = null, $token = null, $logFile = null, $encryptPayload = false)
    {
        self::$key = $key;
        self::$secret = $secret;
        self::$token = $token;
        self::$encryptPayload = (bool) $encryptPayload;

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

        // Match .NET SDK log sequence: emit an initialization DEBUG line
        try {
            if (!self::$initLogEmitted) {
                $logger = Logger::getInstance(self::$logFile);
                $logger->debug("ApiClient initialized - encryptPayload: " . (self::$encryptPayload ? "True" : "False"));
                self::$initLogEmitted = true;
            }
        } catch (\Throwable $e) {
            // ignore logging failures
        }
    }

    /**
     * Check whether outgoing payload encryption is enabled.
     *
     * @return bool
     */
    public static function isEncryptPayloadEnabled()
    {
        return (bool) self::$encryptPayload;
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

        $className = 'Nimbbl\\Api\\Services\\' . ucwords($name);
        if (class_exists($className)) {
            return new $className();
        }

        throw new \Exception("Service $name not found.");
    }

    public static function getBaseUrl()
    {
        return self::$baseUrl;
    }

    public static function getAPIVersion()
    {
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
        // If apiVersion is intentionally blank (combined endpoint already includes /vX),
        // avoid double-prefixing the version segment.
        if (self::$apiVersion === '') {
            return $baseUrl . '/generate-token';
        }
        return $baseUrl . '/' . ApiConstants::AUTH_GENERATE_TOKEN;
    }

    public static function getFullUrl($relativeUrl)
    {
        $baseUrl = rtrim(self::getBaseUrl(), '/');
        $relativeUrl = ltrim($relativeUrl, '/');

        // When using a combined endpoint that already contains /vX, strip the leading version
        // from relative URLs defined with ApiConstants (which already include the version).
        if (self::$apiVersion === '' && preg_match('#/v\\d+$#', $baseUrl)) {
            $relativeUrl = preg_replace('#^v\\d+/#', '', $relativeUrl);
        }

        return $baseUrl . '/' . $relativeUrl;
    }

    public static function setMerchantId($merchantId)
    {
        self::$merchantId = $merchantId;
        return true;
    }

    public static function getMerchantId()
    {
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
        // Match .NET sample behavior: use dated log file naming.
        $resolved = Logger::resolveLogFilePath($logFile);
        self::$logFile = $resolved;
        // Reinitialize Logger with new log file if already instantiated
        Logger::getInstance($resolved);
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
     * @return Addresses
     */
    public function addresses()
    {
        return new Addresses();
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
     * Get Signature Verifier Utils
     * 
     * @return SignatureVerifier
     */
    public function signatureVerifier()
    {
        return new SignatureVerifier();
    }


}
