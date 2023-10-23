<?php

namespace Nimbbl\Api;

use Exception;
use JsonSerializable;


class NimbblOrder extends NimbblEntity implements JsonSerializable
{
    public function entityClass()
    {
        return 'Nimbbl\\Api\\NimbblOrder';
    }

    /**
     *  @param $id Customer id description
     */

    public function retrieveMany($options = array())
    {
        $f = base64_encode($this->buildHttpQuery($options));
        $nimbblRequest = new NimbblRequest();
        $manyEntities = $nimbblRequest->request('GET', 'orders/many?f=' . $f . '&pt=no');

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
        $nimbblRequest = new NimbblRequest();
        $nimbblSegment = new NimbblSegment();
        $nimbblSegment->track(array(
                "userId" => NimbblApi::getKey(),
                "event" => "Order Submitted",
                "properties" => [
                  "invoice_id" => $attributes['invoice_id'],
                  "amount" => $attributes['total_amount'],
                  "kit_name" => "psp-sdk",
                  "kit_version" => "1"
                ],
        ));

        $createdEntity = $nimbblRequest->request('POST', 'v2/create-order', $attributes);
        
        $newCreatedEntity = new NimbblOrder();
        if (key_exists('error', $createdEntity)) {
            $createdEntityArray = (array) $createdEntity['error'];
            $newCreatedEntity->error = $createdEntityArray;
        } else {
            if(array_key_exists('order',$createdEntity)){
                $nimbblSegment->track(array(
                    "userId" => NimbblApi::getKey(),
                    "event" => "Order Recieved",
                    "properties" => [
                      "invoice_id" => $createdEntity['order']['invoice_id'],
                      "order_id" => $createdEntity['order']['order_id'],
                      "amount" => $createdEntity['order']['total_amount'],
                      "merchant_id" => NimbblApi::getMerchantId(),
                      "kit_name" => 'psp-sdk',
                      'kit_version' => 1
                    ],
                ));
                $attributes = array();
                foreach ($createdEntity['order'] as $key => $value) {
                    $attributes[$key] = $value;
                }
            }else{
                $nimbblSegment->track(array(
                    "userId" => NimbblApi::getKey(),
                    "event" => "Order Recieved",
                    "properties" => [
                      "invoice_id" => $createdEntity['invoice_id'],
                      "order_id" => $createdEntity['order_id'],
                      "amount" => $createdEntity['total_amount'],
                      "merchant_id" => NimbblApi::getMerchantId(),
                      "kit_name" => 'psp-sdk',
                      'kit_version' => 1
                    ],
                ));
                $attributes = array();
                foreach ($createdEntity as $key => $value) {
                    $attributes[$key] = $value;
                }
            }
            $newCreatedEntity->attributes = $attributes;
        }

        return $newCreatedEntity;
    }

    public function retrieveOne($id)
    {
        $nimbblRequest = new NimbblRequest();
        $oneEntity = $nimbblRequest->request('GET', 'v2/get-order/' . $id);
        $loadedEntity = $this->fillOne($oneEntity);
        $this->attributes = $loadedEntity->attributes;
        $this->error = $loadedEntity->error;
        return $this;
    }

    public function edit($attributes = null)
    {
        throw new Exception("Unsupported operation.");
    }
}
