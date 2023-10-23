<?php

namespace Nimbbl\Api;

use Requests;
use Requests_Auth;
use Exception;
use Requests_Hooks;


if (defined('CURL_SSLVERSION_TLSv1_1') === false) {
    define('CURL_SSLVERSION_TLSv1_1', 5);
}

class NimbblSegment
{
    protected static $segmentBaseUrl = 'https://api.segment.io/v1/';

    protected static $segmentWriteKey = "zcv0QoazADM0Qpfo8dWwCD1HmGAlx409:";

    
    public function identify($data = array())
    {
        $url = self::$segmentBaseUrl . "identify";

        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', array($this, 'setCurlSslOpts'));

        $options = [
            // 'auth' => new NimbblAuth($tokenResponseBody['token']),
            'hook' => $hooks,
            'timeout' => 60,
        ];

        $headers['Authorization'] = 'Basic ' . base64_encode(self::$segmentWriteKey);
        $headers['Content-Type'] = 'application/json';

        $method = "POST";
        
        $response = Requests::request($url, $headers, $data, $method, $options);

        return $response->body;
    }

    public function track($data = array())
    {
        $url = self::$segmentBaseUrl . "track";

        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', array($this, 'setCurlSslOpts'));

        $options = [
            // 'auth' => new NimbblAuth($tokenResponseBody['token']),
            'hook' => $hooks,
            'timeout' => 60,
        ];

        $headers['Authorization'] = 'Basic ' . base64_encode(self::$segmentWriteKey);
        $headers['Content-Type'] = 'application/json';

        $method = "POST";
        $data = json_encode($data);

        $response = Requests::request($url, $headers, $data, $method, $options);

        return $response->body;
    }
}
