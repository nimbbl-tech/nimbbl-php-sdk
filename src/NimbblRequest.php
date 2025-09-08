<?php

namespace Nimbbl\Api;

use Requests;
use Requests_Auth;
use Exception;
use Requests_Hooks;


// Available since PHP 5.5.19 and 5.6.3
// https://git.io/fAMVS | https://secure.php.net/manual/en/curl.constants.php
if (defined('CURL_SSLVERSION_TLSv1_1') === false) {
    define('CURL_SSLVERSION_TLSv1_1', 5);
}

/**
 * Request class to communicate to the request libarary
 */
class NimbblRequest
{
    /**
     * Headers to be sent with every http request to the API
     * @var array
     */
    protected static $headers = array(
        'Nimbbl-API'  =>  1
    );

    /**
     * Fires a request to the API
     * @param  string   $method HTTP Verb
     * @param  string   $url    Relative URL for the request
     * @param  array $data Data to be passed along the request
     * @return array Response data in array format. Not meant
     * to be used directly
     */
    public function request($method, $url, $data = array())
    {
        NimbblLogger::getInstance()->log("request START - method: {$method}, url: {$url}", 'INFO', 'NimbblRequest');
        
        try {
            $endpoint = $url;
            $url = NimbblApi::getFullUrl($url);
            $hooks = new Requests_Hooks();
            $hooks->register('curl.before_send', array($this, 'setCurlSslOpts'));
            $nimbblToken = self::generateToken();
            $options = [
                'hook' => $hooks,
                'timeout' => 60,
            ];
            $headers = $this->getRequestHeaders();
            $headers['Authorization'] = 'Bearer ' . $nimbblToken['token'];
            $requestBody = (strtolower($method) === 'post') ? json_encode($data) : $data;
            
            NimbblLogger::getInstance()->log("request PREPARED - endpoint: {$endpoint}, fullUrl: {$url}", 'DEBUG', 'NimbblRequest');
            NimbblLogger::getInstance()->log("request HEADERS: " . print_r($headers, true), 'DEBUG', 'NimbblRequest');
            NimbblLogger::getInstance()->log("request BODY: " . $requestBody, 'DEBUG', 'NimbblRequest');
            
            $response = Requests::request($url, $headers, $requestBody, $method, $options);
            
            NimbblLogger::getInstance()->log("request RESPONSE - status: {$response->status_code}", 'INFO', 'NimbblRequest');
            NimbblLogger::getInstance()->log("response HEADERS: " . print_r($response->headers, true), 'DEBUG', 'NimbblRequest');
            NimbblLogger::getInstance()->log("response BODY: " . $response->body, 'DEBUG', 'NimbblRequest');
            
            $result = json_decode($response->body, true);
            NimbblLogger::getInstance()->log("request DECODED RESPONSE: " . print_r($result, true), 'DEBUG', 'NimbblRequest');
            NimbblLogger::getInstance()->log("request END - SUCCESS", 'INFO', 'NimbblRequest');
            
            return $result;
        } catch (Exception $e) {
            NimbblLogger::getInstance()->log("request ERROR: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 'ERROR', 'NimbblRequest');
            throw $e;
        }
    }

    public function universalRequest($method, $url, $data = array())
    {
        NimbblLogger::getInstance()->log("universalRequest START - method: {$method}, url: {$url}", 'INFO', 'NimbblRequest');
        
        try {
            $endpoint = $url;
            $url = NimbblApi::getFullUrl($url);
            $hooks = new Requests_Hooks();
            $hooks->register('curl.before_send', array($this, 'setCurlSslOpts'));
            $nimbblToken = self::generateToken();
            $options = [
                'hook' => $hooks,
                'timeout' => 60,
            ];
            $headers = $this->getRequestHeaders();
            $headers['Authorization'] = 'Bearer ' . $nimbblToken['token'];
            $requestBody = (strtolower($method) === 'post') ? json_encode($data) : $data;
            
            NimbblLogger::getInstance()->log("universalRequest PREPARED - endpoint: {$endpoint}, fullUrl: {$url}", 'DEBUG', 'NimbblRequest');
            NimbblLogger::getInstance()->log("universalRequest HEADERS: " . print_r($headers, true), 'DEBUG', 'NimbblRequest');
            NimbblLogger::getInstance()->log("universalRequest BODY: " . $requestBody, 'DEBUG', 'NimbblRequest');
            
            $response = Requests::request($url, $headers, $requestBody, $method, $options);
            
            NimbblLogger::getInstance()->log("universalRequest RESPONSE - status: {$response->status_code}", 'INFO', 'NimbblRequest');
            NimbblLogger::getInstance()->log("universalRequest response HEADERS: " . print_r($response->headers, true), 'DEBUG', 'NimbblRequest');
            NimbblLogger::getInstance()->log("universalRequest response BODY: " . $response->body, 'DEBUG', 'NimbblRequest');
            
            $result = json_decode($response->body, true);
            NimbblLogger::getInstance()->log("universalRequest DECODED RESPONSE: " . print_r($result, true), 'DEBUG', 'NimbblRequest');
            NimbblLogger::getInstance()->log("universalRequest END - SUCCESS", 'INFO', 'NimbblRequest');
            
            return $result;
        } catch (Exception $e) {
            NimbblLogger::getInstance()->log("universalRequest ERROR: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 'ERROR', 'NimbblRequest');
            throw $e;
        }
    }

    public function setCurlSslOpts($curl)
    {
        NimbblLogger::getInstance()->log("setCurlSslOpts START", 'DEBUG', 'NimbblRequest');
        curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_1);
        NimbblLogger::getInstance()->log("setCurlSslOpts END", 'DEBUG', 'NimbblRequest');
    }

    /**
     * Adds an additional header to all API requests
     * @param string $key   Header key
     * @param string $value Header value
     * @return null
     */
    public static function addHeader($key, $value)
    {
        $logMessage = "[NimbblRequest] addHeader START - key: {$key}, value: {$value}" . PHP_EOL;
        error_log($logMessage);
        
        // Also write to custom log file
        $logFile = dirname(__FILE__) . '/../../../logs/nimbbl_debug.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        self::$headers[$key] = $value;
        
        $endMessage = "[NimbblRequest] addHeader END" . PHP_EOL;
        error_log($endMessage);
        file_put_contents($logFile, $endMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Returns all headers attached so far
     * @return array headers
     */
    public static function getHeaders()
    {
        $logMessage = "[NimbblRequest] getHeaders START" . PHP_EOL;
        error_log($logMessage);
        
        // Also write to custom log file
        $logFile = dirname(__FILE__) . '/../../../logs/nimbbl_debug.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        $headers = self::$headers;
        
        $endMessage = "[NimbblRequest] getHeaders END - headers: " . print_r($headers, true) . PHP_EOL;
        error_log($endMessage);
        file_put_contents($logFile, $endMessage, FILE_APPEND | LOCK_EX);
        
        return $headers;
    }

    /**
     * Process the statusCode of the response and throw exception if necessary
     * @param Object $response The response object returned by Requests
     */
    protected function checkErrors($response)
    {
        NimbblLogger::getInstance()->log("checkErrors START - status: {$response->status_code}", 'INFO', 'NimbblRequest');
        
        $body = $response->body;
        $httpStatusCode = $response->status_code;

        try {
            $body = json_decode($response->body, true);
        } catch (Exception $e) {
            NimbblLogger::getInstance()->log("checkErrors ERROR: " . $e->getMessage(), 'ERROR', 'NimbblRequest');
            $this->throwServerError($body, $httpStatusCode);
        }

        if (($httpStatusCode < 200) or ($httpStatusCode >= 300)) {
            $this->processError($body, $httpStatusCode, $response);
        }
        
        NimbblLogger::getInstance()->log("checkErrors END", 'INFO', 'NimbblRequest');
    }

    protected function processError($body, $httpStatusCode, $response)
    {
        NimbblLogger::getInstance()->log("processError START - status: {$httpStatusCode}", 'INFO', 'NimbblRequest');
        
        // TODO: FIXME based on the error structure coming from NimbblAPI.
        $code = $body['error']['code'];
        $description = $body['error']['description'];
        
        NimbblLogger::getInstance()->log("processError ERROR: {$description} ({$code})", 'ERROR', 'NimbblRequest');
        throw new NimbblError($description, $code, $httpStatusCode);
    }

    protected function throwServerError($body, $httpStatusCode)
    {
        NimbblLogger::getInstance()->log("throwServerError START - status: {$httpStatusCode}", 'INFO', 'NimbblRequest');
        
        $description = "The server did not send back a well-formed response. Server response: $body";
        NimbblLogger::getInstance()->log("throwServerError ERROR: {$description}", 'ERROR', 'NimbblRequest');
        throw new NimbblError($description, NimbblErrorCode::SERVER_ERROR, $httpStatusCode);
    }

    public function getRequestHeaders()
    {
        NimbblLogger::getInstance()->log("getRequestHeaders START", 'DEBUG', 'NimbblRequest');
        
        $uaHeader = array(
            'User-Agent' => $this->constructUa()
        );

        $headers = array_merge(self::$headers, $uaHeader);

        NimbblLogger::getInstance()->log("getRequestHeaders END - headers: " . print_r($headers, true), 'DEBUG', 'NimbblRequest');
        return $headers;
    }

    protected function constructUa()
    {
        NimbblLogger::getInstance()->log("constructUa START", 'DEBUG', 'NimbblRequest');
        
        $ua = 'Nimbbl/v1 PHPSDK/' . NimbblApi::VERSION . ' PHP/' . phpversion();
        $ua .= ' ' . $this->getAppDetailsUa();

        NimbblLogger::getInstance()->log("constructUa END - ua: {$ua}", 'DEBUG', 'NimbblRequest');
        return $ua;
    }

    protected function getAppDetailsUa()
    {
        NimbblLogger::getInstance()->log("getAppDetailsUa START", 'DEBUG', 'NimbblRequest');
        
        $appsDetails = NimbblApi::$appsDetails;
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

        NimbblLogger::getInstance()->log("getAppDetailsUa END - ua: {$appsDetailsUa}", 'DEBUG', 'NimbblRequest');
        return $appsDetailsUa;
    }

    public function generateToken()
    {
        NimbblLogger::getInstance()->log("generateToken START", 'DEBUG', 'NimbblRequest');
        
        try {
            $nimbblSegment = new NimbblSegment();
            $tokenResponse = Requests::post(NimbblApi::getTokenEndpoint(), ['Content-Type' => 'application/json'], json_encode(['access_key' => NimbblApi::getKey(), 'access_secret' => NimbblApi::getSecret()]));
            $tokenResponseBody = json_decode($tokenResponse->body, true);
            
            NimbblLogger::getInstance()->log("generateToken RESPONSE - status: {$tokenResponse->status_code}", 'DEBUG', 'NimbblRequest');
            NimbblLogger::getInstance()->log("generateToken RESPONSE BODY: " . print_r($tokenResponseBody, true), 'DEBUG', 'NimbblRequest');
            
            if (key_exists('error', $tokenResponseBody)) {
                NimbblLogger::getInstance()->log("generateToken ERROR: " . $tokenResponseBody['error']['nimbbl_error_code'], 'ERROR', 'NimbblRequest');
            }
            
            NimbblLogger::getInstance()->log("generateToken END - SUCCESS", 'DEBUG', 'NimbblRequest');
            return $tokenResponseBody;
        } catch (Exception $e) {
            NimbblLogger::getInstance()->log("generateToken ERROR: " . $e->getMessage(), 'ERROR', 'NimbblRequest');
            throw $e;
        }
    }
}
