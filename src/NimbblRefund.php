<?php

namespace Nimbbl\Api;

use Exception;
use JsonSerializable;

class NimbblRefund extends NimbblEntity implements JsonSerializable
{
    public function entityClass()
    {
        error_log(__FILE__ . ": NimbblRefund::entityClass START" . PHP_EOL);
        $class = 'Nimbbl\\Api\\NimbblRefund';
        error_log(__FILE__ . ": NimbblRefund::entityClass END" . PHP_EOL);
        return $class;
    }

    public function create($attributes = array())
    {
        error_log(__FILE__ . ": NimbblRefund::create START" . PHP_EOL);
        throw new Exception("Unsupported operation.");
        error_log(__FILE__ . ": NimbblRefund::create END" . PHP_EOL);
    }

    public function edit($attributes = null)
    {
        error_log(__FILE__ . ": NimbblRefund::edit START" . PHP_EOL);
        throw new Exception("Unsupported operation.");
        error_log(__FILE__ . ": NimbblRefund::edit END" . PHP_EOL);
    }

    public function initiateRefund($attributes = array(), $apiVersion = 'v3')
    {
        error_log(__FILE__ . ": NimbblRefund::initiateRefund START" . PHP_EOL);
        error_log(__FILE__ . ": NimbblRefund::initiateRefund API REQUEST: " . print_r($attributes, true) . PHP_EOL);
        $nimbblRequest = new NimbblRequest();
        $response = $nimbblRequest->universalRequest('POST', $apiVersion.'/refund', $attributes);
        error_log(__FILE__ . ": NimbblRefund::initiateRefund API RESPONSE: " . print_r($response, true) . PHP_EOL);
        if (is_array($response) && key_exists('error', $response)){
            error_log('['.date("Y-m-d H:i:s").'] [ERROR] => Initiate Refund failed due to '.($response['error']['nimbbl_error_code'] ?? 'unknown') . PHP_EOL);
        }
        $loadedResponse = $this->fillOne($response);
        $this->attributes = $loadedResponse->attributes;
        $this->error = $loadedResponse->error;
        error_log(__FILE__ . ": NimbblRefund::initiateRefund END" . PHP_EOL);
        return $this;
    }

    public function retrieveOne($id)
    {
        error_log(__FILE__ . ": NimbblRefund::retrieveOne START" . PHP_EOL);
        $nimbblRequest = new NimbblRequest();
        $oneEntity = $nimbblRequest->request('GET', 'v2/fetch-refund/' . $id);
        $loadedEntity = $this->fillOne($oneEntity);
        $this->attributes = $loadedEntity->attributes;
        $this->error = $loadedEntity->error;
        error_log(__FILE__ . ": NimbblRefund::retrieveOne END" . PHP_EOL);
        return $this;
    }

    public function retrieveMany($options = array())
    {
        error_log(__FILE__ . ": NimbblRefund::retrieveMany START" . PHP_EOL);
        $f = base64_encode($this->buildHttpQuery($options));
        $nimbblRequest = new NimbblRequest();
        $manyEntities = $nimbblRequest->request('GET', 'refunds/many?f=' . $f . '&pt=no');
        $users = array();
        if (is_array($manyEntities) && isset($manyEntities['items']) && is_array($manyEntities['items'])) {
            foreach ($manyEntities['items'] as $idx => $oneEntity) {
                $users[] = $this->fillOne($oneEntity);
            }
        }
        error_log(__FILE__ . ": NimbblRefund::retrieveMany END" . PHP_EOL);
        return [
            'items' => $users,
            'meta' => $manyEntities['_meta'] ?? []
        ];
    }

    public function retrieveRefundByOrderId($id)
    {
        error_log(__FILE__ . ": NimbblRefund::retrieveRefundByOrderId START" . PHP_EOL);
        $nimbblRequest = new NimbblRequest();
        $manyEntities = $nimbblRequest->request('GET', 'v2/order/fetch-refunds/' . $id);
        $newResponse = new NimbblTransaction();
        if (is_array($manyEntities) && key_exists('error', $manyEntities)) {
            $newResponse->error = $manyEntities['error'];
        } else {
            $refunds = array();
            if(is_array($manyEntities) && isset($manyEntities['refunds']) && is_array($manyEntities['refunds'])) {
                foreach ($manyEntities['refunds'] as $idx => $oneEntity) {
                    $refunds[] = $this->fillOne($oneEntity);
                }
            }
            $newResponse->items = $refunds;
        }
        error_log(__FILE__ . ": NimbblRefund::retrieveRefundByOrderId END" . PHP_EOL);
        return $newResponse;
    }

    public function retrieveRefundByTxnId($id)
    {
        error_log(__FILE__ . ": NimbblRefund::retrieveRefundByTxnId START" . PHP_EOL);
        $nimbblRequest = new NimbblRequest();
        $manyEntities = $nimbblRequest->request('GET', 'v2/transaction/fetch-refunds/' . $id);
        $newResponse = new NimbblTransaction();
        if (is_array($manyEntities) && key_exists('error', $manyEntities)) {
            $newResponse->error = $manyEntities['error'];
        } else {
            $refunds = array();
            if(is_array($manyEntities) && isset($manyEntities['refunds']) && is_array($manyEntities['refunds'])) {
                foreach ($manyEntities['refunds'] as $idx => $oneEntity) {
                    $refunds[] = $this->fillOne($oneEntity);
                }
            }
            $newResponse->items = $refunds;
        }
        error_log(__FILE__ . ": NimbblRefund::retrieveRefundByTxnId END" . PHP_EOL);
        return $newResponse;
    }
}
