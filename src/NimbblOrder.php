<?php

namespace Nimbbl\Api;

use Exception;
use JsonSerializable;


class NimbblOrder extends NimbblEntity implements JsonSerializable
{
    public $token; // Add this line to declare the token property

    public function entityClass()
    {
        return 'Nimbbl\\Api\\NimbblOrder';
    }

    /**
     *  @param $id Customer id description
     */

    public function retrieveMany($options = array())
    {
        NimbblLogger::getInstance()->log("retrieveMany START - options: " . print_r($options, true), 'DEBUG', 'NimbblOrder');
        
        $f = base64_encode($this->buildHttpQuery($options));
        $nimbblRequest = new NimbblRequest();
        $manyEntities = $nimbblRequest->request('GET', 'orders/many?f=' . $f . '&pt=no');
        $users = array();
        if (is_array($manyEntities) && isset($manyEntities['items']) && is_array($manyEntities['items'])) {
            foreach ($manyEntities['items'] as $idx => $oneEntity) {
                $users[] = $this->fillOne($oneEntity);
            }
        }
        
        NimbblLogger::getInstance()->log("retrieveMany END - found " . count($users) . " entities", 'DEBUG', 'NimbblOrder');
        return [
            'items' => $users,
            'meta' => $manyEntities['_meta'] ?? []
        ];
    }

    public function create($attributes = array(), $apiVersion = 'v3')
    {
        NimbblLogger::getInstance()->log("create START - apiVersion: {$apiVersion}", 'DEBUG', 'NimbblOrder');
        NimbblLogger::getInstance()->log("create ATTRIBUTES: " . print_r($attributes, true), 'DEBUG', 'NimbblOrder');
        
        try {
            $endpoint = $apiVersion.'/create-order';
            $fullUrl = \Nimbbl\Api\NimbblApi::getFullUrl($endpoint);
            $nimbblRequest = new NimbblRequest();
            $headers = $nimbblRequest->getRequestHeaders();
            $tokenArr = $nimbblRequest->generateToken();
            $headers['Authorization'] = 'Bearer ' . $tokenArr['token'];
            $requestBody = json_encode($attributes);
            
            NimbblLogger::getInstance()->log("create PREPARED - endpoint: {$endpoint}, fullUrl: {$fullUrl}", 'DEBUG', 'NimbblOrder');
            //NimbblLogger::getInstance()->log("create HEADERS: " . print_r($headers, true), 'DEBUG', 'NimbblOrder');
            //NimbblLogger::getInstance()->log("create BODY: " . $requestBody, 'DEBUG', 'NimbblOrder');
            
            // Only make one API request and log the response
            $hooks = new \Requests_Hooks();
            $hooks->register('curl.before_send', array($nimbblRequest, 'setCurlSslOpts'));
            $options = [
                'hook' => $hooks,
                'timeout' => 60,
            ];
            $rawResponse = \Requests::request($fullUrl, $headers, $requestBody, 'POST', $options);
            
            NimbblLogger::getInstance()->log("create RESPONSE - status: {$rawResponse->status_code}", 'DEBUG', 'NimbblOrder');
            //NimbblLogger::getInstance()->log("create response HEADERS: " . print_r($rawResponse->headers, true), 'DEBUG', 'NimbblOrder');
            //NimbblLogger::getInstance()->log("create response BODY: " . $rawResponse->body, 'DEBUG', 'NimbblOrder');
            
            // Log the raw JSON response for debugging
            NimbblLogger::getInstance()->log("create RAW JSON RESPONSE: " . $rawResponse->body, 'DEBUG', 'NimbblOrder');
            
            $createdEntity = json_decode($rawResponse->body, true);
            $newCreatedEntity = new NimbblOrder();
            
            if (is_array($createdEntity) && isset($createdEntity['token'])) {
                NimbblLogger::getInstance()->log("create SUCCESS - token found in response", 'DEBUG', 'NimbblOrder');
                // Set all top-level fields as direct properties for easy access
                foreach ($createdEntity as $key => $value) {
                    $newCreatedEntity->$key = $value;
                }
                $newCreatedEntity->attributes = $createdEntity;
            } elseif (is_array($createdEntity) && isset($createdEntity['order']) && is_array($createdEntity['order'])) {
                NimbblLogger::getInstance()->log("create SUCCESS - order found in response", 'DEBUG', 'NimbblOrder');
                $attributes = $createdEntity['order'];
                $newCreatedEntity->attributes = $attributes;
                if (isset($attributes['token'])) {
                    $newCreatedEntity->token = $attributes['token'];
                }
            } elseif (is_array($createdEntity) && isset($createdEntity['error'])) {
                NimbblLogger::getInstance()->log("create ERROR: " . print_r($createdEntity['error'], true), 'ERROR', 'NimbblOrder');
                $newCreatedEntity->error = $createdEntity['error'];
            } else {
                NimbblLogger::getInstance()->log("create ERROR: Unexpected API response: " . print_r($createdEntity, true), 'ERROR', 'NimbblOrder');
            }
            
            //NimbblLogger::getInstance()->log("create END - result: " . print_r($newCreatedEntity, true), 'DEBUG', 'NimbblOrder');
            return $newCreatedEntity;
        } catch (\Exception $e) {
            NimbblLogger::getInstance()->log("create ERROR: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 'ERROR', 'NimbblOrder');
            throw $e;
        }
    }

    public function retrieveOne($id)
    {
        NimbblLogger::getInstance()->log("retrieveOne START - id: {$id}", 'DEBUG', 'NimbblOrder');
        
        $nimbblRequest = new NimbblRequest();
        $oneEntity = $nimbblRequest->request('GET', 'v2/get-order/' . $id);
        $loadedEntity = $this->fillOne($oneEntity);
        $this->attributes = $loadedEntity->attributes;
        $this->error = $loadedEntity->error;
        
        NimbblLogger::getInstance()->log("retrieveOne END - success", 'DEBUG', 'NimbblOrder');
        return $this;
    }

    public function edit($attributes = null)
    {
        NimbblLogger::getInstance()->log("edit START - attributes: " . print_r($attributes, true), 'DEBUG', 'NimbblOrder');
        NimbblLogger::getInstance()->log("edit ERROR: Unsupported operation", 'ERROR', 'NimbblOrder');
        throw new Exception("Unsupported operation.");
    }

    public function getOrderByInvoiceId($id, $apiVersion = 'v3'){
        NimbblLogger::getInstance()->log("getOrderByInvoiceId START - id: {$id}, apiVersion: {$apiVersion}", 'DEBUG', 'NimbblOrder');
        
        $nimbblrequest = new NimbblRequest();
        $response = $nimbblrequest->request('GET', $apiVersion.'/order?invoice_id='.$id);
        
        if (is_array($response) && key_exists('error', $response)){
            $errorCode = $response['error']['nimbbl_error_code'] ?? 'unknown';
            NimbblLogger::getInstance()->log("getOrderByInvoiceId ERROR: Get Order By Invoice Id failed due to {$errorCode}", 'ERROR', 'NimbblOrder');
            return (array) $response['error'];
        }
        
        NimbblLogger::getInstance()->log("getOrderByInvoiceId END - success", 'DEBUG', 'NimbblOrder');
        return $response;
    }

    public function getOrderByOrderId($id, $apiVersion = 'v3'){
        NimbblLogger::getInstance()->log("getOrderByOrderId START - id: {$id}, apiVersion: {$apiVersion}", 'DEBUG', 'NimbblOrder');
        
        $nimbblrequest = new NimbblRequest();
        $response = $nimbblrequest->request('GET', $apiVersion.'/order?order_id='.$id);
        
        if (is_array($response) && key_exists('error', $response)){
            $errorCode = $response['error']['nimbbl_error_code'] ?? 'unknown';
            NimbblLogger::getInstance()->log("getOrderByOrderId ERROR: Get Order By Order Id failed due to {$errorCode}", 'ERROR', 'NimbblOrder');
            return (array) $response['error'];
        }
        
        NimbblLogger::getInstance()->log("getOrderByOrderId END - success", 'DEBUG', 'NimbblOrder');
        return $response;
    }
}