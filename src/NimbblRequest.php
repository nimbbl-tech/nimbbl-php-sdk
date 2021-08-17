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


// class NimbblAuth implements Requests_Auth
// {
//     protected $token;
//     protected $accessSecret;

//     public function __construct($token)
//     {
//         $this->token = $token;
//     }

//     public function register(Requests_Hooks $hooks)
//     {
//         $hooks->register('requests.before_request', array($this, 'before_request'));
//     }

//     public function before_request(&$url, &$headers, &$data, &$type, &$options)
//     {
//         $headers['Authorization'] = 'Bearer ' . $this->token;
//     }
// }

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
        $url = NimbblApi::getFullUrl($url);
        
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', array($this, 'setCurlSslOpts'));

        $nimbblToken = self::generateToken();

        // TODO: FIXME instead of using normal auth we have to use token auth.
        $options = [
            'hook' => $hooks,
            'timeout' => 60,
        ];

        $headers = $this->getRequestHeaders();

        $headers['Authorization'] = 'Bearer ' . $nimbblToken['token'];

        if (strtolower($method) === 'post') {
            $data = json_encode($data);
        }

        $response = Requests::request($url, $headers, $data, $method, $options);

        // $this->checkErrors($response);

        return json_decode($response->body, true);
        

        // TODO: FIXME instead of using normal auth we have to use token auth.
        
    }

    public function universalRequest($method, $url, $data = array())
    {
        $url = NimbblApi::getFullUrl($url);

        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', array($this, 'setCurlSslOpts'));

        $nimbblToken = self::generateToken();

        $nimbblKey = md5($nimbblToken['token']);
        $sub_merchant = $nimbblToken['auth_principal']['sub_merchant_id'];
        // TODO: FIXME instead of using normal auth we have to use token auth.
        $options = [
            // 'auth' => new NimbblAuth($tokenResponseBody['token']),
            'hook' => $hooks,
            'timeout' => 60,
        ];

        $headers = $this->getRequestHeaders();
        $headers['Authorization'] = 'Bearer ' . $nimbblToken['token'];
        $headers['x-nimbbl-key'] = $sub_merchant . '-' . $nimbblKey;

        if (strtolower($method) === 'post') {
            $data = json_encode($data);
        }

        $response = Requests::request($url, $headers, $data, $method, $options);

        // $this->checkErrors($response);

        return json_decode($response->body, true);
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
        self::$headers[$key] = $value;
    }

    /**
     * Returns all headers attached so far
     * @return array headers
     */
    public static function getHeaders()
    {
        return self::$headers;
    }

    /**
     * Process the statusCode of the response and throw exception if necessary
     * @param Object $response The response object returned by Requests
     */
    protected function checkErrors($response)
    {
        $body = $response->body;
        $httpStatusCode = $response->status_code;

        try {
            $body = json_decode($response->body, true);
        } catch (Exception $e) {
            $this->throwServerError($body, $httpStatusCode);
        }

        if (($httpStatusCode < 200) or ($httpStatusCode >= 300)) {
            $this->processError($body, $httpStatusCode, $response);
        }
    }

    protected function processError($body, $httpStatusCode, $response)
    {
        // TODO: FIXME based on the error structure coming from NimbblAPI.
        $code = $body['error']['code'];
        $description = $body['error']['description'];
        throw new NimbblError($description, $code, $httpStatusCode);
    }

    protected function throwServerError($body, $httpStatusCode)
    {
        $description = "The server did not send back a well-formed response. Server response: $body";

        throw new NimbblError($description, NimbblErrorCode::SERVER_ERROR, $httpStatusCode);
    }

    protected function getRequestHeaders()
    {
        $uaHeader = array(
            'User-Agent' => $this->constructUa()
        );

        $headers = array_merge(self::$headers, $uaHeader);

        return $headers;
    }

    protected function constructUa()
    {
        $ua = 'Nimbbl/v1 PHPSDK/' . NimbblApi::VERSION . ' PHP/' . phpversion();

        $ua .= ' ' . $this->getAppDetailsUa();

        return $ua;
    }

    protected function getAppDetailsUa()
    {
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

        return $appsDetailsUa;
    }

    public function generateToken()
    {
        $nimbblSegment = new NimbblSegment();
        $nimbblSegment->track(array(
                "userId" => NimbblApi::getKey(),
                "event" => "Authorization Submitted",
                "properties" => [
                  "access_key" => NimbblApi::getKey(),
                  "kit_name" => 'php-sdk',
                  "kit_version" => '1'
                ],
        ));
        $tokenResponse = Requests::post(NimbblApi::getTokenEndpoint(), ['Content-Type' => 'application/json'], json_encode(['access_key' => NimbblApi::getKey(), 'access_secret' => NimbblApi::getSecret()]));
        $tokenResponseBody = json_decode($tokenResponse->body, true);
        
        if (key_exists('error', $tokenResponseBody)) {
            $nimbblSegment->track(array(
                    "userId" => NimbblApi::getKey(),
                    "event" => "Authorization Received",
                    "properties" => [
                        "access_key" => NimbblApi::getKey(),
                        "auth_status" => "failed",
                        "kit_name" => 'php-sdk',
                        "kit_version" => '1'
                    ],
            ));
        }
        else {
            NimbblApi::setMerchantId($tokenResponseBody['auth_principal']['sub_merchant_id']);
            $nimbblSegment->track(array(
                    "userId" => NimbblApi::getKey(),
                    "event" => "Authorization Received",
                    "properties" => [
                        "access_key" => NimbblApi::getKey(),
                        "auth_status" => "success",
                        "kit_name" => 'php-sdk',
                        "kit_version" => '1'
                    ],
            ));
        }
        return $tokenResponseBody;
    }

    // /**
    //  * Verifies error is in proper format. If not then
    //  * throws ServerErrorException
    //  *
    //  * @param  array $body
    //  * @param  int $httpStatusCode
    //  * @return void
    //  */
    // protected function verifyErrorFormat($body, $httpStatusCode)
    // {
    //     if (is_array($body) === false)
    //     {
    //         $this->throwServerError($body, $httpStatusCode);
    //     }

    //     if ((isset($body['error']) === false) or
    //         (isset($body['error']['code']) === false))
    //     {
    //         $this->throwServerError($body, $httpStatusCode);
    //     }

    //     $code = $body['error']['code'];

    //     if (Errors\ErrorCode::exists($code) === false)
    //     {
    //         $this->throwServerError($body, $httpStatusCode);
    //     }
    // }
}
