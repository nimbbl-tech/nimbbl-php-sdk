<?php

namespace Nimbbl\Api;

use Exception;
use JsonSerializable;

class NimbblTransaction extends NimbblEntity implements JsonSerializable
{
    public function entityClass()
    {
        error_log(__FILE__ . ": NimbblTransaction::entityClass START" . PHP_EOL);
        $class = 'Nimbbl\\Api\\NimbblTransaction';
        error_log(__FILE__ . ": NimbblTransaction::entityClass END" . PHP_EOL);
        return $class;
    }

    /**
     *  @param $id Customer id description
     */
    public function retrieveOne($id)
    {
        error_log(__FILE__ . ": NimbblTransaction::retrieveOne START" . PHP_EOL);
        $nimbblRequest = new NimbblRequest();
        error_log(__FILE__ . ": NimbblTransaction::retrieveOne API REQUEST: id = $id" . PHP_EOL);
        $oneEntity = $nimbblRequest->request('GET', 'v2/fetch-transaction/' . $id);
        error_log(__FILE__ . ": NimbblTransaction::retrieveOne API RESPONSE: " . print_r($oneEntity, true) . PHP_EOL);
        $loadedEntity = $this->fillOne($oneEntity);
        $this->attributes = $loadedEntity->attributes;
        $this->error = $loadedEntity->error;
        error_log(__FILE__ . ": NimbblTransaction::retrieveOne END" . PHP_EOL);
        return $this;
    }

    public function retrieveMany($options = array())
    {
        error_log(__FILE__ . ": NimbblTransaction::retrieveMany START" . PHP_EOL);
        $f = base64_encode($this->buildHttpQuery($options));
        $nimbblRequest = new NimbblRequest();
        error_log(__FILE__ . ": NimbblTransaction::retrieveMany API REQUEST: options = " . print_r($options, true) . PHP_EOL);
        $manyEntities = $nimbblRequest->request('GET', 'transactions/many?f=' . $f . '&pt=no');
        error_log(__FILE__ . ": NimbblTransaction::retrieveMany API RESPONSE: " . print_r($manyEntities, true) . PHP_EOL);
        $users = array();
        if (is_array($manyEntities) && isset($manyEntities['items']) && is_array($manyEntities['items'])) {
            foreach ($manyEntities['items'] as $idx => $oneEntity) {
                $users[] = $this->fillOne($oneEntity);
            }
        }
        error_log(__FILE__ . ": NimbblTransaction::retrieveMany END" . PHP_EOL);
        return [
            'items' => $users,
            'meta' => $manyEntities['_meta'] ?? []
        ];
    }

    public function create($attributes = array())
    {
        error_log(__FILE__ . ": NimbblTransaction::create START" . PHP_EOL);
        throw new Exception("Unsupported operation.");
        error_log(__FILE__ . ": NimbblTransaction::create END" . PHP_EOL);
    }

    public function edit($attributes = null)
    {
        error_log(__FILE__ . ": NimbblTransaction::edit START" . PHP_EOL);
        throw new Exception("Unsupported operation.");
        error_log(__FILE__ . ": NimbblTransaction::edit END" . PHP_EOL);
    }

    public function transactionEnquiry($attributes = array())
    {
        error_log(__FILE__ . ": NimbblTransaction::transactionEnquiry START" . PHP_EOL);
        $nimbblRequest = new NimbblRequest();
        $nimbblSegment = new NimbblSegment();

        $response = $nimbblRequest->universalRequest('POST', 'v2/transaction-enquiry', $attributes);
        $newResponse = new NimbblTransaction();
        if (is_array($response) && key_exists('error', $response)) {
            $newResponse->error = $response['error'];
        }
        else {
            $attributes = array();
            if(is_array($response)) {
                foreach ($response as $key => $value) {
                    $attributes[$key] = $value;
                }
            }
            $newResponse->attributes = $attributes;
        }
        error_log(__FILE__ . ": NimbblTransaction::transactionEnquiry END" . PHP_EOL);
        return $newResponse;
    }

    public function retrieveTransactionByOrderId($id)
    {
        error_log(__FILE__ . ": NimbblTransaction::retrieveTransactionByOrderId START" . PHP_EOL);
        $nimbblRequest = new NimbblRequest();
        $manyEntities = $nimbblRequest->request('GET', 'v2/order/fetch-transactions/' . $id);
        
        $newResponse = new NimbblTransaction();
        if (is_array($manyEntities) && key_exists('error', $manyEntities)) {
            $newResponse->error = $manyEntities['error'];
        }
        else{
            $transactions = array();
            if(is_array($manyEntities) && isset($manyEntities['transactions']) && is_array($manyEntities['transactions'])) {
                foreach ($manyEntities['transactions'] as $idx => $oneEntity) {
                    $transactions[] = $this->fillOne($oneEntity);
                }
            }
            $newResponse->items = $transactions;
        }
        error_log(__FILE__ . ": NimbblTransaction::retrieveTransactionByOrderId END" . PHP_EOL);
        return $newResponse;
    }
    
}
