<?php

namespace Nimbbl\Api;

use Requests;
use Requests_Auth;
use Exception;
use Requests_Hooks;
use Nimbbl\Api\Exception\NimbblException;
use Nimbbl\Api\Exception\ApiException;
use Nimbbl\Api\Exception\AuthenticationException;
use Nimbbl\Api\Exception\BadRequestException;
use Nimbbl\Api\Exception\NotFoundException;
use Nimbbl\Api\Exception\RateLimitException;
use Nimbbl\Api\Exception\ServerException;


// Requires PHP 7.4 or higher
// https://git.io/fAMVS | https://secure.php.net/manual/en/curl.constants.php
if (defined('CURL_SSLVERSION_TLSv1_1') === false) {
    define('CURL_SSLVERSION_TLSv1_1', 5);
}

/**
 * Request class to communicate to the request library
 */
class Request
{
    /**
     * Headers to be sent with every http request to the API
     * @var array
     */
    protected static $headers = array(
        'Nimbbl-API'  =>  1
    );

    /**
     * Cached authentication token
     * @var string|null
     */
    protected static $cachedToken = null;

    /**
     * Token expiration timestamp
     * @var string|null
     */
    protected static $tokenExpiresAt = null;

    /**
     * Fires a request to the API
     * @param  string   $method HTTP Verb
     * @param  string   $url    Relative URL for the request
     * @param  array $data Data to be passed along the request
     * @param  string|null $token Authentication token (required)
     * @return array Response data in array format. Not meant
     * to be used directly
     */
    public function request($method, $url, $data = array(), $token = null, $component = SdkConstants::COMPONENT_REQUEST)
    {
        try {
            $methodUpper = strtoupper($method);
            
            // For GET/DELETE requests, append query parameters to URL if data is provided
            if (($methodUpper === ApiConstants::HTTP_GET || $methodUpper === ApiConstants::HTTP_DELETE) && !empty($data)) {
                $queryString = self::buildHttpQuery($data);
                if ($queryString) {
                    $url .= (strpos($url, '?') !== false ? '&' : '?') . $queryString;
                }
                $requestBody = null; // No body for GET/DELETE
            } else {
                // For POST/PATCH/PUT, encode data as JSON
                // Use JSON_PRESERVE_ZERO_FRACTION to preserve float values (e.g., 2.00 instead of 2)
                $requestBody = !empty($data) ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) : null;
            }
            
            $url = Api::getFullUrl($url);
            $hooks = new Requests_Hooks();
            $hooks->register('curl.before_send', array($this, 'setCurlSslOpts'));
            
            $options = [
                'hook' => $hooks,
                'timeout' => 60,
            ];
            $headers = $this->getRequestHeaders();
            
            // Auth handling with priority:
            // 1. Token passed as parameter (highest priority)
            // 2. Token from Api::getToken() (set at initialization)
            // 3. Cached token (from generateToken or createOrder)
            $authToken = $token ?? Api::getToken() ?? self::getCachedToken();
            $logger = Logger::getInstance();
            
            if ($authToken !== null) {
                // Use Bearer token
                $headers['Authorization'] = 'Bearer ' . trim($authToken);
            }
            
            // Set Content-Type: application/json for requests with body (POST, PATCH, PUT)
            if ($requestBody !== null && in_array($methodUpper, [ApiConstants::HTTP_POST, ApiConstants::HTTP_PATCH, ApiConstants::HTTP_PUT])) {
                $headers['Content-Type'] = 'application/json';
            }
            
            // Always log INFO for API calls (regardless of logging configuration)
            $maskedUrl = $this->maskSensitiveInText($url);
            $requestLog = "{$methodUpper} {$maskedUrl}";
            if ($requestBody !== null) {
                $maskedBody = $this->maskSensitiveInText($requestBody);
                $requestLog .= "\nRequest Body: " . $maskedBody;
            }
            $this->alwaysLogInfo($requestLog, $component);
            
            $response = Requests::request($url, $headers, $requestBody, $methodUpper, $options);
            
            // Always log INFO for API responses (regardless of logging configuration)
            $responseLog = "HTTP {$response->status_code}";
            if (!empty($response->body)) {
                $maskedResponseBody = $this->maskSensitiveInText($response->body);
                $responseLog .= "\nResponse Body: " . $maskedResponseBody;
            }
            $this->alwaysLogInfo($responseLog, $component);
            
            // Handle empty response body (e.g., 204 No Content or empty 200)
            if (empty(trim($response->body))) {
                // Empty response with 2xx status is considered success
                if ($response->status_code >= 200 && $response->status_code < 300) {
                    return ['success' => true, 'message' => 'Operation completed successfully'];
                }
            }
            
            // Always parse response as JSON
            $result = json_decode($response->body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $safeRaw = $this->maskSensitiveInText($response->body);
                $logger->log("JSON DECODE ERROR: " . json_last_error_msg() . " - Raw body: " . substr($safeRaw, 0, 500), SdkConstants::LOG_DESERIALIZATION_ERROR, $component);
                // Surface structured error so merchants always get JSON
                $result = [
                    'error' => [
                        'nimbbl_error_code' => 'DESERIALIZATION_ERROR',
                        'nimbbl_merchant_message' => 'Unable to parse response body as JSON',
                        'raw_body' => $safeRaw
                    ]
                ];
            }
            
            // Check for error status codes and throw exceptions
            if (($response->status_code < 200) || ($response->status_code >= 300)) {
                $this->checkErrors($response);
            }
            
            // Log if response contains an error (even if status code is 200)
            if (is_array($result) && (isset($result['error']) || key_exists('error', $result))) {
            $logger->log("API request failed: " . print_r($result['error'], true), SdkConstants::LOG_ERROR, $component);
            }
            
            // Cache the token if present in the response and it's token generation
            if (isset($result['token']) && $component === SdkConstants::COMPONENT_AUTH) {
                self::cacheToken($result['token'], $result['expires_at'] ?? null);
            }
            
            return $result;
        } catch (Exception $e) {
            // Handle all exceptions and return structured JSON error to the merchant
            $logger = $logger ?? Logger::getInstance();
            $logger->log("ERROR: " . $e->getMessage(), SdkConstants::LOG_ERROR, $component);
            return [
                'error' => [
                    'nimbbl_error_code' => 'SDK_EXCEPTION',
                    'nimbbl_merchant_message' => $e->getMessage()
                ]
            ];
        }
    }

    public function setCurlSslOpts($curl)
    {
        curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_1);
    }

    /**
     * Adds an additional header to all API requests
     * @param string $key   Header key
     * @param string $value Header value
     * @return null
     */
    public static function addHeader($key, $value)
    {
        $logger = Logger::getInstance();
        $logger->log("[Request] addHeader START - key: {$key}, value: {$value}", SdkConstants::LOG_DEBUG, SdkConstants::COMPONENT_REQUEST);
        
        self::$headers[$key] = $value;
        
        $logger->log("[Request] addHeader END", SdkConstants::LOG_DEBUG, SdkConstants::COMPONENT_REQUEST);
    }

    /**
     * Returns all headers attached so far
     * @return array headers
     */
    public static function getHeaders()
    {
        $logger = Logger::getInstance();
        $logger->log("[Request] getHeaders START", SdkConstants::LOG_DEBUG, SdkConstants::COMPONENT_REQUEST);
        
        $headers = self::$headers;
        
        $logger->log("[Request] getHeaders END - headers: " . print_r($headers, true), SdkConstants::LOG_DEBUG, SdkConstants::COMPONENT_REQUEST);
        
        return $headers;
    }

    /**
     * Process the statusCode of the response and throw exception if necessary
     * @param Object $response The response object returned by Requests
     */
    protected function checkErrors($response)
    {
        $logger = Logger::getInstance();
        $body = $response->body;
        $httpStatusCode = $response->status_code;

        try {
            $body = json_decode($response->body, true);
        } catch (Exception $e) {
            $logger->log("checkErrors ERROR: " . $e->getMessage(), SdkConstants::LOG_ERROR, SdkConstants::COMPONENT_REQUEST);
            $this->throwServerError($body, $httpStatusCode);
        }

        if (($httpStatusCode < 200) or ($httpStatusCode >= 300)) {
            $this->processError($body, $httpStatusCode, $response);
        }
    }

    protected function processError($body, $httpStatusCode, $response)
    {
        $logger = Logger::getInstance();
        // Extract error information
        $error = $body['error'] ?? [];
        $code = $error['code'] ?? $error['nimbbl_error_code'] ?? null;
        // Try to get merchant message first, then consumer message, then fallback to description/message
        $description = $error['nimbbl_merchant_message'] 
            ?? $error['nimbbl_consumer_message'] 
            ?? $error['description'] 
            ?? $error['message'] 
            ?? 'API request failed';
        $requestId = $response->headers['x-request-id'] ?? $response->headers['X-Request-Id'] ?? null;
        
        $logger->log("API Error: {$description} ({$code})", SdkConstants::LOG_ERROR, SdkConstants::COMPONENT_REQUEST);
        
        // Use new exception hierarchy
        $this->handleErrorResponse($response, $requestId, $code, $description, $error);
    }

    /**
     * Handle error response and throw appropriate exception
     * 
     * @param object $response Response object
     * @param string|null $requestId Request ID
     * @param string|null $errorCode Error code
     * @param string $message Error message
     * @param array $errorData Additional error data
     * @return void
     * @throws NimbblException
     */
    protected function handleErrorResponse($response, $requestId = null, $errorCode = null, $message = 'API request failed', $errorData = [])
    {
        $httpStatusCode = $response->status_code;
        
        // Map HTTP status codes to exceptions
        switch ($httpStatusCode) {
            case 401:
                throw new AuthenticationException($message, $errorCode, $requestId, $httpStatusCode, $errorData);
                
            case 400:
            case 422:
                throw new BadRequestException($message, $errorCode, $requestId, $httpStatusCode, $errorData);
                
            case 404:
                throw new NotFoundException($message, $errorCode, $requestId, $httpStatusCode, $errorData);
                
            case 429:
                throw new RateLimitException($message, $errorCode, $requestId, $httpStatusCode, $errorData);
                
            case 500:
            case 502:
            case 503:
            case 504:
                throw new ServerException($message, $errorCode, $requestId, $httpStatusCode, $errorData);
                
            default:
                throw new ApiException($message, $errorCode, $requestId, $httpStatusCode, $errorData);
        }
    }

    protected function throwServerError($body, $httpStatusCode)
    {
        $description = "The server did not send back a well-formed response. Server response: $body";
        $logger = Logger::getInstance();
        $logger->log("Server Error: {$description}", SdkConstants::LOG_ERROR, SdkConstants::COMPONENT_REQUEST);
        
            throw new ServerException(
                $description,
                'SERVER_ERROR',
                null,
                $httpStatusCode,
                ['response' => $body]
            );
    
    }

    public function getRequestHeaders()
    {
        $uaHeader = array(
            'User-Agent' => $this->constructUa()
        );

        $headers = array_merge(self::$headers, $uaHeader);
        return $headers;
    }

    protected function constructUa()
    {
        $ua = 'Nimbbl/PHPSDK/' . Api::VERSION . ' PHP/' . phpversion();
        $ua .= ' ' . $this->getAppDetailsUa();
        return $ua;
    }

    protected function getAppDetailsUa()
    {
        $appsDetails = Api::$appsDetails;
        $appsDetailsUa = '';

        foreach ($appsDetails as $app) {
            if ((isset($app['title'])) and (is_string($app['title']))) {
                $appUa = $app['title'];

                if ((isset($app['version'])) and (is_scalar($app['version']))) {
                    $appUa .= '/' . $app['version'];
                }

                $appsDetailsUa .= $appUa . ' ';
            }
        }

        return $appsDetailsUa;
    }

    /**
     * Build HTTP query string from array
     * 
     * @param array $query Query parameters
     * @return string HTTP query string
     */
    /**
     * Builds an HTTP query string from an array of key-value pairs
     * 
     * Common utility method for building query strings used across the SDK
     * 
     * @param array $query Array of key-value pairs to be used in the query
     * @return string HTTP query string
     */
    public static function buildHttpQuery($query)
    {
        if (empty($query)) {
            return '';
        }
        $query_array = array();
        foreach ($query as $key => $key_value) {
            if ($key_value !== null) {
                $query_array[] = urlencode($key) . '=' . urlencode($key_value);
            }
        }
        return implode('&', $query_array);
    }

    /**
     * Generate a new authentication token
     * 
     * @return array Token response with 'token' and 'expires_at' keys
     */
    public function generateToken()
    {
        try {
            $tokenEndpoint = Api::getTokenEndpoint();
            $tokenRequestData = ['access_key' => Api::getKey(), 'access_secret' => Api::getSecret()];
            $tokenRequest = json_encode($tokenRequestData);
            
            // Always log INFO for token request (with masking)
            $maskedRequest = $this->maskSensitiveInText($tokenRequest);
            $this->alwaysLogInfo("POST {$tokenEndpoint}\nRequest Body: {$maskedRequest}", SdkConstants::COMPONENT_REQUEST);
            
            $tokenResponse = Requests::post($tokenEndpoint, ['Content-Type' => 'application/json'], $tokenRequest);
            $tokenResponseBody = json_decode($tokenResponse->body, true);
            
            // Always log INFO for token response (with masking)
            $tokenLog = "HTTP {$tokenResponse->status_code}";
            if (!empty($tokenResponse->body)) {
                $maskedResponse = $this->maskSensitiveInText($tokenResponse->body);
                $tokenLog .= "\nResponse Body: {$maskedResponse}";
            }
            $this->alwaysLogInfo($tokenLog, SdkConstants::COMPONENT_REQUEST);
            
            // Handle null response (e.g., when json_decode fails or response is empty)
            if ($tokenResponseBody === null) {
                $tokenResponseBody = [];
            }
            
            // Check for errors in response
            if (is_array($tokenResponseBody) && key_exists('error', $tokenResponseBody)) {
                $errorMsg = $tokenResponseBody['error']['nimbbl_merchant_message'] ?? $tokenResponseBody['error']['message'] ?? 'Authentication failed';
                $logger->log("Authentication failed: " . ($tokenResponseBody['error']['nimbbl_error_code'] ?? 'Unknown error'), SdkConstants::LOG_ERROR, SdkConstants::COMPONENT_REQUEST);
                throw new NimbblException(
                    $errorMsg,
                    $tokenResponseBody['error']['nimbbl_error_code'] ?? 'AUTH_ERROR',
                    (int)$tokenResponse->status_code
                );
            }
            
            // If status code is not 200, treat as error
            if ($tokenResponse->status_code !== 200) {
                $errorMessage = is_array($tokenResponseBody) && isset($tokenResponseBody['error']) 
                    ? ($tokenResponseBody['error']['message'] ?? 'Unknown error')
                    : 'HTTP ' . $tokenResponse->status_code . ' - ' . ($tokenResponse->body ?: 'Empty response');
                
                $logger->log("Authentication failed: HTTP {$tokenResponse->status_code} - {$errorMessage}", SdkConstants::LOG_ERROR, SdkConstants::COMPONENT_REQUEST);
                throw new NimbblException($errorMessage, 'HTTP_' . $tokenResponse->status_code, (int)$tokenResponse->status_code);
            }
            
            // Cache the token if present in response
            if (isset($tokenResponseBody['token'])) {
                self::cacheToken($tokenResponseBody['token'], $tokenResponseBody['expires_at'] ?? null);
            }
            
            return $tokenResponseBody;
        } catch (Exception $e) {
            if (!($e instanceof NimbblException)) {
                $logger->log("Token generation " . ErrorMessages::ERROR_PREFIX_GENERAL . $e->getMessage(), SdkConstants::LOG_ERROR, SdkConstants::COMPONENT_REQUEST);
            }
            throw $e;
        }
    }

    /**
     * Cache authentication token
     * 
     * @param string $token Token to cache
     * @param string|null $expiresAt Token expiration timestamp
     * @return void
     */
    public static function cacheToken($token, $expiresAt = null)
    {
        self::$cachedToken = $token;
        self::$tokenExpiresAt = $expiresAt;
    }

    /**
     * Get the currently cached token (if any)
     * Checks if token is still valid before returning
     * 
     * @return string|null The cached token or null if not cached or expired
     */
    public static function getCachedToken()
    {
        // Check if cached token is still valid (with 60 second buffer)
        if (self::$cachedToken !== null && self::$tokenExpiresAt !== null) {
            $expiresTimestamp = strtotime(self::$tokenExpiresAt);
            $currentTimestamp = time();
            $bufferSeconds = 60; // Consider expired 60 seconds before actual expiration
            
            if ($expiresTimestamp > ($currentTimestamp + $bufferSeconds)) {
                return self::$cachedToken;
            } else {
                // Token expired, clear cache
                self::$cachedToken = null;
                self::$tokenExpiresAt = null;
            }
        }
        
        return self::$cachedToken;
    }

    /**
     * Clear the cached authentication token
     * Useful when you want to force a new token generation
     * 
     * @return void
     */
    public static function clearTokenCache()
    {
        self::$cachedToken = null;
        self::$tokenExpiresAt = null;
    }

    /**
     * Always log INFO messages for API calls, regardless of logging configuration.
     * This ensures API call information is always visible.
     * 
     * @param string $message Log message
     * @param string $component Component name
     * @return void
     */
    private function alwaysLogInfo($message, $component = SdkConstants::COMPONENT_REQUEST)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = sprintf(
            '[%s][%s %s][%s][%s:%d][%s]: %s',
            $timestamp,
            SdkConstants::SDK_NAME,
            SdkConstants::SDK_VERSION,
            'INFO',
            $component,
            0,
            '-',
            $message
        ) . PHP_EOL;

        // Always write to error log (for web server environments)
        if (php_sapi_name() !== 'cli') {
            error_log($logMessage);
        }

        // Always print to stdout if CLI
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }

        // Also write to log file if logger is configured
        try {
            $logger = Logger::getInstance();
            $logger->log($message, SdkConstants::LOG_INFO, $component);
        } catch (\Exception $e) {
            // Ignore logger errors, we've already output to stdout/error_log
        }
    }

    /**
     * Mask sensitive data in text (JSON strings, URLs, etc.)
     * Uses CentralMasker for consistent masking across the SDK.
     * 
     * @param string $text Text that may contain sensitive data
     * @return string Text with sensitive data masked
     */
    private function maskSensitiveInText($text)
    {
        if (empty($text)) {
            return $text;
        }

        // Try to decode as JSON first
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // Recursively mask sensitive fields in JSON
            $masked = $this->maskSensitiveInArray($decoded);
            return json_encode($masked, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        }

        // If not JSON, mask patterns in plain text (URLs, query strings, etc.)
        $masked = $text;
        
        // Mask tokens/keys in URLs (e.g., ?token=xxx, &access_key=xxx)
        $sensitiveUrlParams = ['token', 'access_key', 'access_secret', 'refresh_token', 'api_key', 'api_secret'];
        foreach ($sensitiveUrlParams as $field) {
            $pattern = '/([?&]' . preg_quote($field, '/') . '=)([^&\s"]+)/i';
            $masked = preg_replace_callback($pattern, function($matches) use ($field) {
                $value = urldecode($matches[2]);
                if ($field === 'token' || $field === 'refresh_token') {
                    return $matches[1] . urlencode(CentralMasker::maskToken($value));
                } elseif ($field === 'access_secret' || $field === 'api_secret') {
                    return $matches[1] . urlencode(CentralMasker::maskAccessSecret($value));
                } else {
                    return $matches[1] . urlencode(CentralMasker::maskHeaderValue($value));
                }
            }, $masked);
        }

        // Mask card numbers in plain text (16 digits, possibly with spaces/dashes)
        $masked = preg_replace_callback('/(\d[-\s]?){13,19}/', function($matches) {
            $card = preg_replace('/[-\s]/', '', $matches[0]);
            if (strlen($card) >= 13 && strlen($card) <= 19) {
                return CentralMasker::maskCard($card);
            }
            return $matches[0];
        }, $masked);

        return $masked;
    }

    /**
     * Recursively mask sensitive fields in array/object using CentralMasker.
     * 
     * @param mixed $data Data structure to mask
     * @return mixed Masked data structure
     */
    private function maskSensitiveInArray($data)
    {
        if (is_array($data)) {
            $masked = [];
            foreach ($data as $key => $value) {
                $keyLower = strtolower($key);
                
                // Apply appropriate masking based on field name
                if (is_string($value)) {
                    if (strpos($keyLower, 'card_no') !== false || strpos($keyLower, 'card_number') !== false || strpos($keyLower, 'cardnum') !== false) {
                        $masked[$key] = CentralMasker::maskCard($value);
                    } elseif (strpos($keyLower, 'cvv') !== false || strpos($keyLower, 'cvc') !== false) {
                        $masked[$key] = CentralMasker::maskCvv();
                    } elseif (strpos($keyLower, 'expiry_month') !== false || strpos($keyLower, 'card_expiry_mm') !== false || strpos($keyLower, 'expiryMonth') !== false) {
                        $masked[$key] = CentralMasker::maskExpiryMonth();
                    } elseif (strpos($keyLower, 'expiry_year') !== false || strpos($keyLower, 'card_expiry_yy') !== false || strpos($keyLower, 'expiryYear') !== false) {
                        $masked[$key] = CentralMasker::maskExpiryYear();
                    } elseif (strpos($keyLower, 'expiry') !== false && strpos($keyLower, 'month') === false && strpos($keyLower, 'year') === false) {
                        $masked[$key] = CentralMasker::maskExpiryDate();
                    } elseif (strpos($keyLower, 'cryptogram') !== false) {
                        $masked[$key] = CentralMasker::maskCryptogram($value);
                    } elseif ($keyLower === 'token' || $keyLower === 'refresh_token') {
                        $masked[$key] = CentralMasker::maskToken($value);
                    } elseif ($keyLower === 'access_secret' || $keyLower === 'api_secret') {
                        $masked[$key] = CentralMasker::maskAccessSecret($value);
                    } elseif (strpos($keyLower, 'upi_id') !== false || $keyLower === 'vpa' || strpos($keyLower, 'upi_va') !== false || strpos($keyLower, 'payer_vpa') !== false) {
                        $masked[$key] = CentralMasker::maskVpaId($value);
                    } elseif (strpos($keyLower, 'first_name') !== false || strpos($keyLower, 'firstname') !== false || 
                              strpos($keyLower, 'last_name') !== false || strpos($keyLower, 'lastname') !== false ||
                              strpos($keyLower, 'card_holder') !== false || strpos($keyLower, 'payer_name') !== false ||
                              ($keyLower === 'name' && strpos($keyLower, 'account') === false)) {
                        $masked[$key] = CentralMasker::maskName($value);
                    } elseif (strpos($keyLower, 'mobile_number') !== false || strpos($keyLower, 'mobile_no') !== false || 
                              strpos($keyLower, 'phone') !== false || strpos($keyLower, 'customer_phone') !== false) {
                        $masked[$key] = CentralMasker::maskMobileNumber($value);
                    } elseif (strpos($keyLower, 'email') !== false || strpos($keyLower, 'email_id') !== false || strpos($keyLower, 'customer_email') !== false) {
                        $masked[$key] = CentralMasker::maskEmail($value);
                    } elseif (strpos($keyLower, 'address_1') !== false || strpos($keyLower, 'address1') !== false || 
                              strpos($keyLower, 'address2') !== false || strpos($keyLower, 'street') !== false || 
                              strpos($keyLower, 'landmark') !== false) {
                        $masked[$key] = CentralMasker::maskAddressLine($value);
                    } elseif (strpos($keyLower, 'area') !== false || strpos($keyLower, 'city') !== false) {
                        $masked[$key] = CentralMasker::maskCityArea($value);
                    } elseif (strpos($keyLower, 'pincode') !== false || strpos($keyLower, 'zipcode') !== false) {
                        $masked[$key] = CentralMasker::maskPincode($value);
                    } elseif (strpos($keyLower, 'account_number') !== false) {
                        $masked[$key] = CentralMasker::maskAccountNumber($value);
                    } elseif (strpos($keyLower, 'ifsc') !== false) {
                        $masked[$key] = CentralMasker::maskIfscCode($value);
                    } elseif (strpos($keyLower, 'pan_card') !== false) {
                        $masked[$key] = CentralMasker::maskPanCard($value);
                    } elseif (strpos($keyLower, 'access_key') !== false || strpos($keyLower, 'api_key') !== false) {
                        $masked[$key] = CentralMasker::maskHeaderValue($value);
                    } else {
                        $masked[$key] = $value;
                    }
                } elseif (is_array($value) || is_object($value)) {
                    $masked[$key] = $this->maskSensitiveInArray($value);
                } else {
                    $masked[$key] = $value;
                }
            }
            return $masked;
        }
        return $data;
    }
}
