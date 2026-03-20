<?php

namespace Nimbbl\Api\RestClient;

use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\Common\SdkConstants;
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

    protected static $merchantId;

    protected static $logFile;

    /**
     * Whether to override log filename (use static name without date suffix).
     * When true, log file names are used as-is.
     * When false (default), log files will have _ddMMyyyy suffix.
     *
     * @var bool
     */
    protected static $overrideLogFilename = false;

    /**
     * Enable encryption for outgoing request payloads.
     *
     * @var bool
     */
    protected static $encryptPayload = false;

    /**
     * Prevent duplicate "ApiClient initialized" logs.
     * Logged once at application startup.
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
     * @param string $key Access key
     * @param string $secret Access secret
     * @param string|null $url API base URL
     * @param string|null $logFile Optional log file path (if provided, will be used for logging)
     * @param bool $encryptPayload Optional: enable encryption for outgoing payloads (default false)
     * @param bool $debugLogging Optional: enable DEBUG-level SDK logs (default false)
     * @param bool $overrideLogFilename Optional: override log filename to use static name without date suffix (default false)
     */
    public function __construct(string $key, string $secret, ?string $url = null, ?string $logFile = null, bool $encryptPayload = false, bool $debugLogging = false, bool $overrideLogFilename = false)
    {
        // NimbblClient uses static properties internally (global process state).
        // Prevent silent data corruption if a second instance is created with different credentials/config.
        $prospectiveBaseUrl = self::$baseUrl;
        $prospectiveApiVersion = self::$apiVersion;
        if ($url !== null) {
            $normalizedUrl = rtrim($url, '/');
            if (preg_match('#/v\d+$#', $normalizedUrl)) {
                $prospectiveBaseUrl = $normalizedUrl;
                $prospectiveApiVersion = '';
            } else {
                $prospectiveBaseUrl = $normalizedUrl;
            }
        }

        if (self::$key !== null) {
            if (
                self::$key !== $key ||
                self::$secret !== $secret ||
                self::$baseUrl !== $prospectiveBaseUrl ||
                self::$apiVersion !== $prospectiveApiVersion ||
                self::$encryptPayload !== (bool) $encryptPayload
            ) {
                throw new \RuntimeException(
                    'NimbblClient configuration is global/static. Create a single NimbblClient per PHP process (or ensure identical credentials/base URL/encryption settings).'
                );
            }
        }

        self::$key = $key;
        self::$secret = $secret;
        self::$encryptPayload = (bool) $encryptPayload;
        self::$overrideLogFilename = (bool) $overrideLogFilename;

        if ($debugLogging) {
            Logger::enableDebugLogging();
        } else {
            Logger::disableDebugLogging();
        }

        // IMPORTANT: Initialize Logger early with the override flag set correctly
        // This ensures all subsequent Logger::getInstance() calls use the same instance and file naming behavior
        if ($logFile !== null) {
            self::$logFile = $logFile;
            Logger::getInstance($logFile, $overrideLogFilename);
        }

        if ($url !== null) {
            // Support combined base URL with version (e.g., https://api.nimbbl.tech/api/v3)
            $normalizedUrl = rtrim($url, '/');
            if (preg_match('#/v\d+$#', $normalizedUrl)) {
                self::$baseUrl = $normalizedUrl;
                self::$apiVersion = '';
            } else {
                self::$baseUrl = $normalizedUrl;
            }
        }

        try {
            if (!self::$initLogEmitted) {
                $logger = Logger::getInstance();
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
    public static function isEncryptPayloadEnabled(): bool
    {
        return (bool) self::$encryptPayload;
    }

    /**
     * Set custom header for requests
     * 
     * @param string $header Header name
     * @param string $value Header value
     * @return void
     */
    public function setHeader(string $header, string $value): void
    {
        Request::addHeader($header, $value);
    }

    /**
     * Magic getter for dynamic service instantiation
     * 
     * @param string $name Service name (e.g., 'order', 'payment')
     * @return mixed Service instance
     * @throws \Exception If service not found
     */
    public function __get(string $name)
    {
        $className = 'Nimbbl\\Api\\Services\\' . ucwords($name);
        if (class_exists($className)) {
            return new $className();
        }

        throw new \Exception("Service $name not found.");
    }

    /**
     * Get API base URL
     * 
     * @return string
     */
    public static function getBaseUrl(): string
    {
        return self::$baseUrl;
    }

    /**
     * Get API version
     * 
     * @return string
     */
    public static function getAPIVersion(): string
    {
        return self::$apiVersion;
    }

    /**
     * Get access key
     * 
     * @return string|null
     */
    public static function getKey(): ?string
    {
        return self::$key;
    }

    /**
     * Get access secret
     * 
     * @return string|null
     */
    public static function getSecret(): ?string
    {
        return self::$secret;
    }

    /**
     * Get token generation endpoint URL
     * 
     * @return string
     */
    public static function getTokenEndpoint(): string
    {
        $baseUrl = rtrim(self::getBaseUrl(), '/');
        // If apiVersion is intentionally blank (combined endpoint already includes /vX),
        // avoid double-prefixing the version segment.
        if (self::$apiVersion === '') {
            return $baseUrl . '/generate-token';
        }
        return $baseUrl . '/' . ApiConstants::AUTH_GENERATE_TOKEN;
    }

    /**
     * Get fully qualified URL
     * 
     * @param string $relativeUrl Relative URL path
     * @return string Full URL
     */
    public static function getFullUrl(string $relativeUrl): string
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

    /**
     * Set merchant ID
     * 
     * @param string $merchantId Merchant ID
     * @return bool
     */
    public static function setMerchantId(string $merchantId): bool
    {
        self::$merchantId = $merchantId;
        return true;
    }

    /**
     * Get merchant ID
     * 
     * @return string|null
     */
    public static function getMerchantId(): ?string
    {
        return self::$merchantId;
    }

    /**
     * Set log file path
     * 
     * @param string $logFile Path to log file
     * @return void
     */
    public static function setLogFile(string $logFile): void
    {
        Logger::getInstance($logFile, self::$overrideLogFilename);
        self::$logFile = $logFile;
    }

    /**
     * Get log file path
     * 
     * @return string|null Log file path or null if not set
     */
    public static function getLogFile(): ?string
    {
        return self::$logFile;
    }

    /**
     * Set whether to override log filename
     * 
     * @param bool $overrideLogFilename True to override log filename (no date suffix), false for auto-dating
     * @return void
     */
    public static function setOverrideLogFilename(bool $overrideLogFilename): void
    {
        self::$overrideLogFilename = $overrideLogFilename;
        // Reinitialize Logger with new setting
        if (self::$logFile !== null) {
            Logger::getInstance(self::$logFile, $overrideLogFilename);
        }
    }

    /**
     * Get whether log filename is overridden (uses static name without date suffix)
     * 
     * @return bool True if log filename is overridden, false if auto-dating is enabled
     */
    public static function isOverrideLogFilenameEnabled(): bool
    {
        return self::$overrideLogFilename;
    }

    /**
     * Get Orders API client
     * 
     * @return Order
     */
    public function orders(): Order
    {
        return new Order();
    }

    /**
     * Get Transactions API client
     * 
     * @return Transaction
     */
    public function transactions(): Transaction
    {
        return new Transaction();
    }

    /**
     * Get Refunds API client
     * 
     * @return Refund
     */
    public function refunds(): Refund
    {
        return new Refund();
    }

    /**
     * Get Addresses API client
     * 
     * @return Addresses
     */
    public function addresses(): Addresses
    {
        return new Addresses();
    }

    /**
     * Get Payments API client
     * 
     * @return Payment
     */
    public function payments(): Payment
    {
        return new Payment();
    }

    /**
     * Get Payment Links API client
     * 
     * @return PaymentLink
     */
    public function paymentLinks(): PaymentLink
    {
        return new PaymentLink();
    }

    /**
     * Get Checkout Utilities API client
     * 
     * @return CheckoutUtilities
     */
    public function checkoutUtilities(): CheckoutUtilities
    {
        return new CheckoutUtilities();
    }

    /**
     * Get Authentication API client
     * 
     * @return Auth
     */
    public function auth(): Auth
    {
        return new Auth();
    }

    /**
     * Get Signature Verifier Utils
     * 
     * @return SignatureVerifier
     */
    public function signatureVerifier(): SignatureVerifier
    {
        return new SignatureVerifier();
    }


}
