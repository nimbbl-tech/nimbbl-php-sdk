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
        error_log(__FILE__ . ": NimbblSegment::identify START" . PHP_EOL);
        try {
            $url = self::$segmentBaseUrl . "identify";
            $hooks = new Requests_Hooks();
            $hooks->register('curl.before_send', array($this, 'setCurlSslOpts'));
            $options = [
                'hook' => $hooks,
                'timeout' => 60,
            ];
            $headers['Authorization'] = 'Basic ' . base64_encode(self::$segmentWriteKey);
            $headers['Content-Type'] = 'application/json';
            $method = "POST";
            $response = Requests::request($url, $headers, $data, $method, $options);
            error_log(__FILE__ . ": NimbblSegment::identify END" . PHP_EOL);
            return $response->body;
        } catch (Exception $e) {
            error_log(__FILE__ . ": NimbblSegment::identify ERROR: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    public function track($data = array())
    {
        error_log(__FILE__ . ": NimbblSegment::track START" . PHP_EOL);
        try {
            $url = self::$segmentBaseUrl . "track";
            $hooks = new Requests_Hooks();
            $hooks->register('curl.before_send', array($this, 'setCurlSslOpts'));
            $options = [
                'hook' => $hooks,
                'timeout' => 60,
            ];
            $headers['Authorization'] = 'Basic ' . base64_encode(self::$segmentWriteKey);
            $headers['Content-Type'] = 'application/json';
            $method = "POST";
            $data = json_encode($data);
            $response = Requests::request($url, $headers, $data, $method, $options);
            error_log(__FILE__ . ": NimbblSegment::track END" . PHP_EOL);
            return $response->body;
        } catch (Exception $e) {
            error_log(__FILE__ . ": NimbblSegment::track ERROR: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }
}
