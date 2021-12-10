<?php

namespace Nimbbl\Api;

use Exception;
use JsonSerializable;

class NimbblTransaction extends NimbblEntity implements JsonSerializable
{
    public function entityClass()
    {
        return 'Nimbbl\\Api\\NimbblTransaction';
    }

    /**
     *  @param $id Customer id description
     */
    public function retrieveOne($id)
    {
        $nimbblRequest = new NimbblRequest();
        $oneEntity = $nimbblRequest->request('GET', 'v2/fetch-transaction/' . $id);
        $loadedEntity = $this->fillOne($oneEntity);
        $this->attributes = $loadedEntity->attributes;
        $this->error = $loadedEntity->error;
        return $this;
    }

    public function retrieveMany($options = array())
    {
        $f = base64_encode($this->buildHttpQuery($options));
        $nimbblRequest = new NimbblRequest();
        $manyEntities = $nimbblRequest->request('GET', 'transactions/many?f=' . $f . '&pt=no');

        $users = array();
        foreach ($manyEntities['items'] as $idx => $oneEntity) {
            $users[] = $this->fillOne($oneEntity);
        }

        return [
            'items' => $users,
            'meta' => $manyEntities['_meta']
        ];
    }

    public function create($attributes = array())
    {
        throw new Exception("Unsupported operation.");
    }

    public function edit($attributes = null)
    {
        throw new Exception("Unsupported operation.");
    }

    public function transactionEnquiry($attributes = array())
    {
        $nimbblRequest = new NimbblRequest();
        $nimbblSegment = new NimbblSegment();

        $nimbblSegment->track(array(
            "userId" => NimbblApi::getKey(),
            "event" => "Enquiry Submitted",
            "properties" => [
                "order_id" => $attributes['order_id'],
                "transaction_id" => $attributes['transaction_id'],
                "merchant_id" => NimbblApi::getMerchantId(),
                "kit_name" => 'psp-sdk',
                'kit_version' => 1
            ],
        ));

        $response = $nimbblRequest->universalRequest('POST', 'v2/transaction-enquiry', $attributes);
        $newResponse = new NimbblTransaction();
        if (key_exists('error', $response)) {
            $newResponse->error = $response['error'];
        }
        else {
            $nimbblSegment->track(array(
                "userId" => NimbblApi::getKey(),
                "event" => "Enquiry Received",
                "properties" => [
                    "order_id" => $response['nimbbl_order_id'],
                    "transaction_id" => $response['nimbbl_transaction_id'],
                    "merchant_id" => NimbblApi::getMerchantId(),
                    "status" => $response['status'],
                    "kit_name" => 'psp-sdk',
                    'kit_version' => 1
                ],
            ));
            $attributes = array();
            foreach ($response as $key => $value) {
                $attributes[$key] = $value;
            }
            $newResponse->attributes = $attributes;
        }
        return $newResponse;
    }

    public function retrieveTransactionByOrderId($id)
    {
        $nimbblRequest = new NimbblRequest();
        $manyEntities = $nimbblRequest->request('GET', 'v2/order/fetch-transactions/' . $id);
        
        $newResponse = new NimbblTransaction();
        if (key_exists('error', $manyEntities)) {
            $newResponse->error = $manyEntities['error'];
        }
        else{
            $transactions = array();
            foreach ($manyEntities['transactions'] as $idx => $oneEntity) {
                $transactions[] = $this->fillOne($oneEntity);
            }
            $newResponse->items = $transactions;
        }

        return $newResponse;
    }
    
}
