<?php

namespace Nimbbl\Api;

use JsonSerializable;

class NimbblRefund extends NimbblEntity implements JsonSerializable
{
    public static function entityClass()
    {
        return 'Nimbbl\\Api\\NimbblRefund';
    }

    public function create($attributes = array())
    {
        throw new Exception("Unsupported operation.");
    }

    public function edit($attributes = null)
    {
        throw new Exception("Unsupported operation.");
    }

    public function initiateRefund($attributes = array())
    {
        $nimbblRequest = new NimbblRequest();
        $response = $nimbblRequest->universalRequest('POST', 'v2/refund', $attributes);
        $loadedResponse = $this->fillOne($response);
        $this->attributes = $loadedResponse->attributes;
        $this->error = $loadedResponse->error;
        return $this;
    }

    public function retrieveOne($id)
    {
        $nimbblRequest = new NimbblRequest();
        $oneEntity = $nimbblRequest->request('GET', 'v2/fetch-refund/' . $id);
        $loadedEntity = $this->fillOne($oneEntity);
        $this->attributes = $loadedEntity->attributes;
        $this->error = $loadedEntity->error;
        return $this;
    }

    public function retrieveMany($options = array())
    {
        $f = base64_encode($this->buildHttpQuery($options));
        $nimbblRequest = new NimbblRequest();
        $manyEntities = $nimbblRequest->request('GET', 'refunds/many?f=' . $f . '&pt=no');

        $users = array();
        foreach ($manyEntities['items'] as $idx => $oneEntity) {
            $users[] = $this->fillOne($oneEntity);
        }

        return [
            'items' => $users,
            'meta' => $manyEntities['_meta']
        ];
    }

    public function retrieveRefundByOrderId($id)
    {
        $nimbblRequest = new NimbblRequest();
        $manyEntities = $nimbblRequest->request('GET', 'v2/order/fetch-refunds/' . $id);
        
        $refunds = array();
        foreach ($manyEntities['refunds'] as $idx => $oneEntity) {
            $refunds[] = $this->fillOne($oneEntity);
        }

        return [
            'items' => $refunds
        ];
    }

    public function retrieveRefundByTxnId($id)
    {
        $nimbblRequest = new NimbblRequest();
        $manyEntities = $nimbblRequest->request('GET', 'v2/transaction/fetch-refunds/' . $id);
        
        $refunds = array();
        foreach ($manyEntities['refunds'] as $idx => $oneEntity) {
            $refunds[] = $this->fillOne($oneEntity);
        }

        return [
            'items' => $refunds
        ];
    }
}