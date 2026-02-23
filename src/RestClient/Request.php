<?php

namespace Nimbbl\Api\RestClient;

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
use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\ErrorMessages;
use Nimbbl\Api\Common\HttpStatusCodes;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\ErrorCodes;
use Nimbbl\Api\Log\Logger;
use Nimbbl\Api\Common\Encryption;
use Nimbbl\Api\Common\CentralMasker;

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
        'Nimbbl-API' => 1
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
    * @param  string|null $token Optional authentication token
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
                // Encryption is handled in the respective service classes (Order, Refund, Transaction, CheckoutUtilities)
                $requestBody = !empty($data) ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) : null;
            }

            $url = NimbblClient::getFullUrl($url);

            $options = [
                'timeout' => ApiConstants::DEFAULT_HTTP_TIMEOUT_SECONDS,
            ];
            $headers = $this->getRequestHeaders();

            // Auth handling with priority:
            // 1. Token passed as parameter (highest priority)
            // 2. Cached token (from generateToken or auth response)
            $authToken = $token ?? self::getCachedToken();
            $logger = Logger::getInstance();

            // Set Content-Type: application/json for requests with body (POST, PATCH, PUT)
            if ($requestBody !== null && in_array($methodUpper, [ApiConstants::HTTP_POST, ApiConstants::HTTP_PATCH, ApiConstants::HTTP_PUT])) {
                $headers['Content-Type'] = 'application/json; charset=utf-8';
            }

            // Log request
            $maskedUrl = $this->maskSensitiveInText($url);
            $requestLog = "{$methodUpper} {$maskedUrl}";

            // Log request headers (with masking for sensitive values)
            $maskedHeaders = $this->maskSensitiveInHeaders($headers);
            // Hide internal SDK headers from log output.
            unset($maskedHeaders['Nimbbl-API']);
            $requestLog .= "\nRequest Headers: " . $this->formatJsonForLog($maskedHeaders);

            if ($requestBody !== null) {
                $maskedBody = CentralMasker::maskBody($requestBody);
                $formattedBody = $maskedBody;
                $requestLog .= "\nRequest Body: " . $formattedBody;
            }
            $this->logInfoWithSdkCallerContext($requestLog, $component);

            // DEBUG: Raw JSON Request (before sending)
            $callerInfo = $this->resolveSdkCallerContext($component);
            if ($requestBody !== null) {
                $logger->log(
                    "Raw JSON Request (before sending):\n" . $requestBody,
                    Logger::LOG_DEBUG,
                    $callerInfo['module'],
                    $callerInfo['line'],
                    $callerInfo['function']
                );
            }

            // Auto-generate merchant token if needed (for all non-auth requests)
            // Since nimbbl_api supports merchant tokens for all endpoints, we can auto-generate merchant tokens
            if ($component !== SdkConstants::COMPONENT_AUTH && $authToken === null) {
                try {
                    $tokenResponse = $this->generateToken();
                    if (isset($tokenResponse[JsonKeys::TOKEN]) && !empty($tokenResponse[JsonKeys::TOKEN])) {
                        $authToken = $tokenResponse[JsonKeys::TOKEN];
                        // Cache the token for reuse
                        self::cacheToken($authToken, $tokenResponse[JsonKeys::EXPIRES_AT] ?? null);
                    } else {
                        throw new NimbblException(
                            "Failed to auto-generate merchant token: token not found in response.",
                            ErrorCodes::AUTH_ERROR,
                            null,
                            HttpStatusCodes::UNAUTHORIZED,
                            []
                        );
                    }
                } catch (\Exception $ex) {
                    if ($ex instanceof AuthenticationException) {
                        throw $ex;
                    }
                    throw new AuthenticationException(
                        "No valid token available and failed to generate merchant token: " . $ex->getMessage(),
                        ErrorCodes::AUTH_ERROR,
                        null,
                        HttpStatusCodes::UNAUTHORIZED,
                        []
                    );
                }
            }

            // Set Authorization header if we have a token (update after potential auto-generation)
            if ($authToken !== null) {
                $headers['Authorization'] = 'Bearer ' . trim($authToken);
            }

            $response = Requests::request($url, $headers, $requestBody, $methodUpper, $options);

            // INFO: HTTP status line + response body
            $statusText = $this->getHttpStatusText((int) $response->status_code);
            $responseLog = "{$response->status_code} {$statusText} for {$maskedUrl}";
            if (!empty($response->body)) {
                $maskedResponseBody = CentralMasker::maskBody($response->body);
                $responseLog .= "\nResponse Body: " . $maskedResponseBody;
            }
            $this->logInfoWithSdkCallerContext($responseLog, $component);

            // If HTTP status is success but body carries an error envelope, surface it (before decryption)
            // ThrowIfErrorEnvelope is called before decryption
            $isSuccessStatusCode = ($response->status_code >= 200 && $response->status_code < 300);
            if ($isSuccessStatusCode && !empty($response->body)) {
                $this->throwIfErrorEnvelope($response->body, $callerInfo);
            }

            // DEBUG: Raw JSON Response (before deserialization)
            if (!empty($response->body)) {
                $logger->log(
                    "Raw JSON Response (before deserialization):\n" . $response->body,
                    Logger::LOG_DEBUG,
                    $callerInfo['module'],
                    $callerInfo['line'],
                    $callerInfo['function']
                );
            } else {
                $logger->log(
                    "Response body is NULL",
                    Logger::LOG_DEBUG,
                    $callerInfo['module'],
                    $callerInfo['line'],
                    $callerInfo['function']
                );
            }

            // Decrypt encrypted_response (if present) ONLY for success status codes
            // Decryption happens after error envelope check, only for success responses
            if ($isSuccessStatusCode && !empty($response->body)) {
                $decrypted = $this->decryptEncryptedResponseBodyIfPresent($response->body);
                if ($decrypted !== null) {
                    $response->body = $decrypted;
                    $logger->log(
                        "Successfully decrypted encrypted response",
                        Logger::LOG_INFO,
                        $callerInfo['module'],
                        $callerInfo['line'],
                        $callerInfo['function']
                    );
                }
            }

            // Handle empty response body (e.g., 204 No Content or empty 200)
            if (empty(trim($response->body ?? ''))) {
                // Empty response with 2xx status is considered success
                if ($isSuccessStatusCode) {
                    return [
                        JsonKeys::SUCCESS => true,
                        JsonKeys::MESSAGE => ErrorMessages::MESSAGE_OPERATION_COMPLETED_SUCCESSFULLY
                    ];
                }
            }

            // Always parse response as JSON
            $result = json_decode($response->body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $rawForError = $response->body;
                try {
                    $errorMsg = "Failed to deserialize response:\n" . json_last_error_msg();
                    if ($rawForError !== null) {
                        $errorMsg .= "\n\nRaw JSON that failed:\n" . substr($rawForError, 0, 500);
                    } else {
                        $errorMsg .= "\n\nResponse body was NULL";
                    }
                    $logger->error($errorMsg);
                } catch (\Throwable $t) {
                    // ignore logging failures
                }
                throw new ApiException(
                    ErrorMessages::MESSAGE_UNABLE_TO_PARSE_JSON,
                    ErrorCodes::DESERIALIZATION_ERROR,
                    null,
                    (int) $response->status_code,
                    [JsonKeys::RAW_BODY => $rawForError]
                );
            }

            // Check for error status codes and throw exceptions
            // Error responses are handled in checkErrors which also decrypts encrypted error responses
            if (!$isSuccessStatusCode) {
                $this->checkErrors($response);
            }

            // Cache the token if present in the response and it's token generation
            if (isset($result[JsonKeys::TOKEN]) && $component === SdkConstants::COMPONENT_AUTH) {
                self::cacheToken($result[JsonKeys::TOKEN], $result[JsonKeys::EXPIRES_AT] ?? null);
            }

            return $result;
        } catch (Exception $e) {
            // Propagate exceptions (do not swallow into a generic error array)
            try {
                $logger = $logger ?? Logger::getInstance();
                $logger->exception("ERROR: " . $e->getMessage(), $e);
            } catch (\Throwable $t) {
                // ignore logging failures
            }
            throw $e;
        }
    }

    /**
     * Adds an additional header to all API requests
     * @param string $key   Header key
     * @param string $value Header value
     * @return null
     */
    public static function addHeader($key, $value)
    {
        self::$headers[$key] = $value;
    }

    /**
     * Returns all headers attached so far
     * @return array headers
     */
    public static function getHeaders()
    {
        $headers = self::$headers;
        return $headers;
    }

    /**
     * Detects error envelope in a 2xx response and throws mapped exception.
     */
    private function throwIfErrorEnvelope($responseBody, $callerInfo = null)
    {
        if (empty($responseBody)) {
            return;
        }

        try {
            $decoded = json_decode($responseBody, true);
            if (!is_array($decoded) || !isset($decoded[JsonKeys::ERROR])) {
                return;
            }

            $errorObj = $decoded[JsonKeys::ERROR];
            if (!is_array($errorObj)) {
                return;
            }

            $merchantMessage = $errorObj[JsonKeys::ERROR_MERCHANT_MESSAGE] ?? null;
            $consumerMessage = $errorObj[JsonKeys::ERROR_CONSUMER_MESSAGE] ?? null;
            $errorCode = $errorObj[JsonKeys::ERROR_CODE] ?? null;
            $message = $merchantMessage ?? $consumerMessage ?? $errorCode ?? ErrorMessages::MESSAGE_UNKNOWN_ERROR;

            // Map as BadRequest when HTTP is 2xx but error payload present
            throw new BadRequestException($message, $errorCode, null, HttpStatusCodes::BAD_REQUEST, $errorObj);
        } catch (BadRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            // If not JSON or parsing fails, ignore
        }
    }

    /**
     * Process the statusCode of the response and throw exception if necessary
     * Handles encrypted error responses
     * @param object $response The response object returned by Requests
     */
    private function checkErrors($response)
    {
        $logger = Logger::getInstance();
        $body = $response->body;
        $httpStatusCode = $response->status_code;
        $callerInfo = $this->resolveSdkCallerContext(SdkConstants::COMPONENT_REQUEST);

        // Check if error response is encrypted and decrypt if needed
        if (!empty($body)) {
            $decoded = json_decode($body, true);
            if (is_array($decoded) && isset($decoded[JsonKeys::ENCRYPTED_RESPONSE]) && is_string($decoded[JsonKeys::ENCRYPTED_RESPONSE])) {
                try {
                    $encryption = new Encryption(NimbblClient::getSecret());
                    $decryptedJson = $encryption->decrypt($decoded[JsonKeys::ENCRYPTED_RESPONSE], true);
                    $body = $decryptedJson;
                    $response->body = $decryptedJson;

                    $logger->log(
                        "Successfully decrypted encrypted error response",
                        Logger::LOG_INFO,
                        $callerInfo['module'],
                        $callerInfo['line'],
                        $callerInfo['function']
                    );
                } catch (\Exception $decryptEx) {
                    $logger->exception("Failed to decrypt encrypted error response: " . $decryptEx->getMessage(), $decryptEx);
                    // Continue with original responseBody - let error handling proceed
                }
            }
        }

        try {
            // decrypt(..., true) returns array; body may already be array after decryption
            if ( is_array( $body ) ) {
                // already decoded (e.g. from decrypted error response)
            } elseif ( is_string( $body ) ) {
                $decoded = json_decode( $body, true );
                $body = is_array( $decoded ) ? $decoded : array();
            } else {
                $body = array();
            }
        } catch (\Throwable $e) {
            $logger->exception("checkErrors ERROR: " . $e->getMessage(), $e);
            $this->throwServerError( is_string( $body ) ? $body : '', $httpStatusCode );
        }

        if (($httpStatusCode < 200) or ($httpStatusCode >= 300)) {
            $this->processError($body, $httpStatusCode, $response);
        }
    }

    private function processError($body, $httpStatusCode, $response)
    {
        $logger = Logger::getInstance();
        // Extract error information
        $error = $body[JsonKeys::ERROR] ?? [];
        $code = $error[JsonKeys::CODE] ?? $error[JsonKeys::ERROR_CODE] ?? null;
        // Try to get merchant message first, then consumer message, then fallback to description/message
        $description = $error[JsonKeys::ERROR_MERCHANT_MESSAGE]
            ?? $error[JsonKeys::ERROR_CONSUMER_MESSAGE]
            ?? $error[JsonKeys::DESCRIPTION]
            ?? $error[JsonKeys::MESSAGE]
            ?? ErrorMessages::MESSAGE_API_REQUEST_FAILED;
        $requestId = $response->headers['x-request-id'] ?? $response->headers['X-Request-Id'] ?? null;

        $logger->error("API Error: {$description} ({$code})");

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
    private function handleErrorResponse($response, $requestId = null, $errorCode = null, $message = null, $errorData = [])
    {
        $message = $message ?? ErrorMessages::MESSAGE_API_REQUEST_FAILED;
        $httpStatusCode = $response->status_code;

        // Map HTTP status codes to exceptions
        switch ($httpStatusCode) {
            case HttpStatusCodes::UNAUTHORIZED:
                throw new AuthenticationException($message, $errorCode, $requestId, $httpStatusCode, $errorData);

            case HttpStatusCodes::BAD_REQUEST:
            case HttpStatusCodes::UNPROCESSABLE_ENTITY:
                throw new BadRequestException($message, $errorCode, $requestId, $httpStatusCode, $errorData);

            case HttpStatusCodes::NOT_FOUND:
                throw new NotFoundException($message, $errorCode, $requestId, $httpStatusCode, $errorData);

            case HttpStatusCodes::TOO_MANY_REQUESTS:
                throw new RateLimitException($message, $errorCode, $requestId, $httpStatusCode, $errorData);

            case HttpStatusCodes::INTERNAL_SERVER_ERROR:
            case HttpStatusCodes::BAD_GATEWAY:
            case HttpStatusCodes::SERVICE_UNAVAILABLE:
            case HttpStatusCodes::GATEWAY_TIMEOUT:
                throw new ServerException($message, ErrorCodes::SERVER_ERROR, $requestId, $httpStatusCode, $errorData);

            default:
                throw new ApiException($message, $errorCode, $requestId, $httpStatusCode, $errorData);
        }
    }

    private function throwServerError($body, $httpStatusCode)
    {
        $description = "The server did not send back a well-formed response. Server response: $body";
        $logger = Logger::getInstance();
        $logger->error("Server Error: {$description}");

        throw new ServerException(
            $description,
            'SERVER_ERROR',
            null,
            $httpStatusCode,
            [JsonKeys::RESPONSE => $body]
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

    private function constructUa()
    {
        $ua = 'Nimbbl/PHPSDK/' . NimbblClient::VERSION . ' PHP/' . phpversion();
        $ua .= ' ' . $this->getAppDetailsUa();
        return $ua;
    }

    private function getAppDetailsUa()
    {
        $appsDetails = NimbblClient::$appsDetails;
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
            $logger = Logger::getInstance();
            $tokenEndpoint = NimbblClient::getTokenEndpoint();
            $tokenRequestData = [JsonKeys::ACCESS_KEY => NimbblClient::getKey(), JsonKeys::ACCESS_SECRET => NimbblClient::getSecret()];
            $tokenRequest = json_encode($tokenRequestData);

            // INFO: Token request (match request() formatting: POST + headers + body)
            $maskedRequest = $this->maskSensitiveInText($tokenRequest);
            $tokenHeaders = $this->getRequestHeaders();
            $tokenHeaders['Content-Type'] = 'application/json; charset=utf-8';
            $maskedTokenHeaders = $this->maskSensitiveInHeaders($tokenHeaders);
            unset($maskedTokenHeaders['Nimbbl-API']);
            $tokenReqLog = "POST {$tokenEndpoint}"
                . "\nRequest Headers: " . $this->formatJsonForLog($maskedTokenHeaders)
                . "\nRequest Body: {$maskedRequest}";
            $this->logInfoWithSdkCallerContext($tokenReqLog, SdkConstants::COMPONENT_REQUEST);

            // DEBUG: Raw JSON Request (before sending)
            $callerInfo = $this->resolveSdkCallerContext(SdkConstants::COMPONENT_REQUEST);
            $logger->log(
                "Raw JSON Request (before sending):\n" . $maskedRequest,
                Logger::LOG_DEBUG,
                $callerInfo['module'],
                $callerInfo['line'],
                $callerInfo['function']
            );

            $tokenOptions = [
                'timeout' => ApiConstants::DEFAULT_HTTP_TIMEOUT_SECONDS,
            ];

            $tokenResponse = Requests::post(
                $tokenEndpoint,
                $tokenHeaders,
                $tokenRequest,
                $tokenOptions
            );
            $tokenResponseBody = json_decode($tokenResponse->body, true);

            // INFO: Token response
            $statusText = $this->getHttpStatusText((int) $tokenResponse->status_code);
            $tokenLog = "{$tokenResponse->status_code} {$statusText} for {$tokenEndpoint}";
            if (!empty($tokenResponse->body)) {
                $maskedResponse = $this->maskSensitiveInText($tokenResponse->body);
                $tokenLog .= "\nResponse Body: {$maskedResponse}";
            }
            $this->logInfoWithSdkCallerContext($tokenLog, SdkConstants::COMPONENT_REQUEST);

            // DEBUG: Raw JSON Response (before deserialization)
            if (!empty($tokenResponse->body)) {
                $maskedResponseOnly = $this->maskSensitiveInText($tokenResponse->body);
                $logger->log(
                    "Raw JSON Response (before deserialization):\n" . $maskedResponseOnly,
                    Logger::LOG_DEBUG,
                    $callerInfo['module'],
                    $callerInfo['line'],
                    $callerInfo['function']
                );
            }

            // Handle null response (e.g., when json_decode fails or response is empty)
            if ($tokenResponseBody === null) {
                $tokenResponseBody = [];
            }

            // Check for errors in response
            if (is_array($tokenResponseBody) && key_exists(JsonKeys::ERROR, $tokenResponseBody)) {
                $errorMsg = $tokenResponseBody[JsonKeys::ERROR][JsonKeys::ERROR_MERCHANT_MESSAGE]
                    ?? $tokenResponseBody[JsonKeys::ERROR][JsonKeys::MESSAGE]
                    ?? ErrorMessages::MESSAGE_AUTHENTICATION_FAILED;
                $logger->error(ErrorMessages::MESSAGE_AUTHENTICATION_FAILED . ": " . ($tokenResponseBody[JsonKeys::ERROR][JsonKeys::ERROR_CODE] ?? ErrorMessages::MESSAGE_UNKNOWN_ERROR));
                throw new NimbblException(
                    $errorMsg,
                    $tokenResponseBody[JsonKeys::ERROR][JsonKeys::ERROR_CODE] ?? ErrorCodes::AUTH_ERROR,
                    null,
                    (int) $tokenResponse->status_code
                );
            }

            // If status code is not 200, treat as error
            if ($tokenResponse->status_code !== HttpStatusCodes::OK) {
                $errorMessage = is_array($tokenResponseBody) && isset($tokenResponseBody[JsonKeys::ERROR])
                    ? ($tokenResponseBody[JsonKeys::ERROR][JsonKeys::MESSAGE] ?? ErrorMessages::MESSAGE_UNKNOWN_ERROR)
                    : 'HTTP ' . $tokenResponse->status_code . ' - ' . ($tokenResponse->body ?: 'Empty response');

                $logger->error("Authentication failed: HTTP {$tokenResponse->status_code} - {$errorMessage}");
                throw new NimbblException($errorMessage, 'HTTP_' . $tokenResponse->status_code, null, (int) $tokenResponse->status_code);
            }

            // Cache the token if present in response
            if (isset($tokenResponseBody[JsonKeys::TOKEN])) {
                self::cacheToken($tokenResponseBody[JsonKeys::TOKEN], $tokenResponseBody[JsonKeys::EXPIRES_AT] ?? null);
            }

            return $tokenResponseBody;
        } catch (Exception $e) {
            if (!($e instanceof NimbblException)) {
                $logger->exception("Token generation " . ErrorMessages::ERROR_PREFIX_GENERAL . $e->getMessage(), $e);
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
        // Check if cached token is still valid (with expiration threshold buffer)
        if (self::$cachedToken !== null && self::$tokenExpiresAt !== null) {
            $expiresTimestamp = strtotime(self::$tokenExpiresAt);
            $currentTimestamp = time();
            $bufferSeconds = ApiConstants::TOKEN_EXPIRATION_THRESHOLD_SECONDS; // Consider expired within threshold before actual expiration

            if ($expiresTimestamp > ($currentTimestamp + $bufferSeconds)) {
                return self::$cachedToken;
            } else {
                // Token expired, clear cache
                self::clearTokenCache();
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
     * Log INFO messages for API calls.
     *
     * @param string $message Log message
     * @param string $component Component name
     * @return void
     */
    private function logInfoWithSdkCallerContext($message, $component = SdkConstants::COMPONENT_REQUEST)
    {
        $callerInfo = $this->resolveSdkCallerContext($component);
        try {
            $logFile = NimbblClient::getLogFile();
            $logger = Logger::getInstance($logFile);
            $logger->log($message, Logger::LOG_INFO, $callerInfo['module'], $callerInfo['line'], $callerInfo['function']);
        } catch (\Exception $e) {
            // Ignore logger errors
        }
    }

    /**
     * Return SDK caller info for consistent module/line/function in request/response debug logs.
     *
     * @return array{module:string,line:int,function:string}
     */
    private function resolveSdkCallerContext($component = SdkConstants::COMPONENT_REQUEST)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 32);
        $requestFile = basename(__FILE__);
        $skipFunctions = [
            'resolveSdkCallerContext',
            'logInfoWithSdkCallerContext',
            'getHttpStatusText',
            'getRequestHeaders',
            'maskSensitiveInText',
            'maskSensitiveInHeaders',
            'maskSensitiveInArray',
        ];
        $sdkSrcDir = realpath(dirname(__FILE__, 2));

        for ($i = 0; $i < count($backtrace); $i++) {
            $frame = $backtrace[$i];
            $filePath = $frame['file'] ?? null;
            $file = $filePath ? basename($filePath) : null;
            $fn = $frame['function'] ?? '';
            if ($file && $file === $requestFile) {
                continue;
            }
            // Check if file is in SDK src directory first
            if ($sdkSrcDir && $filePath) {
                $real = realpath($filePath);
                if ($real && strpos($real, $sdkSrcDir . DIRECTORY_SEPARATOR) === 0) {
                    // Get the enclosing method from the next frame
                    $enclosingMethod = $fn;
                    if (isset($backtrace[$i + 1])) {
                        $nextFrame = $backtrace[$i + 1];
                        $enclosingMethod = $nextFrame['function'] ?? $fn;
                    }
                    return [
                        'module' => basename($filePath),
                        'line' => $frame['line'] ?? 0,
                        'function' => $enclosingMethod ?: '-',
                    ];
                }
            }
            // Only skip functions for non-SDK files
            if ($fn !== '' && in_array($fn, $skipFunctions, true)) {
                continue;
            }
        }

        // Fallback: first non-Request.php frame
        $caller = [];
        foreach ($backtrace as $frame) {
            $filePath = $frame['file'] ?? null;
            $file = $filePath ? basename($filePath) : null;
            $fn = $frame['function'] ?? '';
            if ($file && $file !== $requestFile && ($fn === '' || !in_array($fn, $skipFunctions, true))) {
                $caller = $frame;
                break;
            }
        }

        return [
            'module' => isset($caller['file']) ? basename($caller['file']) : ($component ?? 'unknown'),
            'line' => $caller['line'] ?? 0,
            'function' => $caller['function'] ?? '-',
        ];
    }

    /**
     * Minimal HTTP status text mapping for log output.
     */
    private function getHttpStatusText(int $statusCode): string
    {
        $map = [
            HttpStatusCodes::OK => 'OK',
            HttpStatusCodes::CREATED => 'Created',
            HttpStatusCodes::ACCEPTED => 'Accepted',
            HttpStatusCodes::NO_CONTENT => 'No Content',
            HttpStatusCodes::BAD_REQUEST => 'Bad Request',
            HttpStatusCodes::UNAUTHORIZED => 'Unauthorized',
            HttpStatusCodes::FORBIDDEN => 'Forbidden',
            HttpStatusCodes::NOT_FOUND => 'Not Found',
            HttpStatusCodes::CONFLICT => 'Conflict',
            HttpStatusCodes::UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
            HttpStatusCodes::TOO_MANY_REQUESTS => 'Too Many Requests',
            HttpStatusCodes::INTERNAL_SERVER_ERROR => 'Internal Server Error',
            HttpStatusCodes::BAD_GATEWAY => 'Bad Gateway',
            HttpStatusCodes::SERVICE_UNAVAILABLE => 'Service Unavailable',
            HttpStatusCodes::GATEWAY_TIMEOUT => 'Gateway Timeout',
        ];
        return $map[$statusCode] ?? 'OK';
    }

    /**
     * Format JSON for log output (compact JSON).
     */
    private function formatJsonForLog($data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : '{}';
    }

    /**
     * Mask sensitive data in text (JSON strings, URLs, etc.)
     * Uses CentralMasker.MaskBody() for consistent masking
     * 
     * @param string $text Text that may contain sensitive data
     * @return string Text with sensitive data masked
     */
    private function maskSensitiveInText($text)
    {
        if (empty($text)) {
            return $text;
        }
        // When DEBUG logging is enabled, do not mask request/response logs
        if (Logger::isDebugLoggingEnabled()) {
            return $text;
        }

        // Use CentralMasker.MaskBody() which handles both JSON and plain text
        return CentralMasker::maskBody($text);
    }

    /**
     * Mask sensitive headers for logging
     * Uses CentralMasker.MaskHeaders() or GetUnmaskedHeaders() based on debug mode
     * 
     * @param array $headers Request headers
     * @return array Headers with sensitive values masked
     */
    private function maskSensitiveInHeaders($headers)
    {
        // When DEBUG logging is enabled, do not mask request/response logs
        if (Logger::isDebugLoggingEnabled()) {
            return CentralMasker::getUnmaskedHeaders($headers);
        }
        return CentralMasker::maskHeaders($headers);
    }

    /**
     * If body is JSON and contains `encrypted_response`, decrypt it and return plaintext JSON string.
     * Returns null when no decryption is needed or decryption fails.
     */
    private function decryptEncryptedResponseBodyIfPresent($body)
    {
        if (!is_string($body)) {
            return null;
        }
        $trim = ltrim($body);
        if ($trim === '' || $trim[0] !== '{') {
            return null;
        }
        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return null;
        }
        $encValue = $decoded[JsonKeys::ENCRYPTED_RESPONSE] ?? null;
        if (!is_string($encValue) || trim($encValue) === '') {
            return null;
        }
        try {
            $enc = new Encryption(NimbblClient::getSecret());
            $plaintext = $enc->decrypt($encValue, false);
            if (!is_string($plaintext) || trim($plaintext) === '') {
                return null;
            }
            return $plaintext;
        } catch (\Throwable $e) {
            // Don't break API calls on decryption errors; keep original body and let JSON parse/exception handling run.
            try {
                $logger = Logger::getInstance();
                $logger->exception("Failed to decrypt encrypted response: " . $e->getMessage(), $e instanceof \Exception ? $e : new \Exception($e->getMessage()));
            } catch (\Throwable $t) {
                // ignore
            }
            return null;
        }
    }
}
