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

    public function create($attributes = array(), $apiVersion = 'v3')
    {
        $nimbblRequest = new NimbblRequest();

        $createdEntity = $nimbblRequest->request('POST', $apiVersion.'/create-order', $attributes);
        
        $newCreatedEntity = new NimbblOrder();
        if (key_exists('error', $createdEntity)) {
            error_log('['.date("Y-m-d H:i:s").'] [ERROR] => Create Order failed due to '.$createdEntity['error']['nimbbl_error_code']);
            $createdEntityArray = (array) $createdEntity['error'];
            $newCreatedEntity->error = $createdEntityArray;
        } else {
            if(array_key_exists('order',$createdEntity)){
                $attributes = array();
                foreach ($createdEntity['order'] as $key => $value) {
                    $attributes[$key] = $value;
                }
            }else{
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

    public function getOrderByInvoiceId($id, $apiVersion = 'v3'){
        $nimbblrequest = new NimbblRequest();
        $response = $nimbblrequest->request('GET', $apiVersion.'/order?invoice_id='.$id);

        if (key_exists('error', $response)){
            error_log('['.date("Y-m-d H:i:s").'] [ERROR] => Get Order By Invoice Id failed due to '.$response['error']['nimbbl_error_code']);
            return (array) $response['error'];
        }
        return $response;
    }

    public function getOrderByOrderId($id, $apiVersion = 'v3'){
        $nimbblrequest = new NimbblRequest();
        $response = $nimbblrequest->request('GET', $apiVersion.'/order?order_id='.$id);

        if (key_exists('error', $response)){
            error_log('['.date("Y-m-d H:i:s").'] [ERROR] => Get Order By Order Id failed due to '.$response['error']['nimbbl_error_code']);
            return (array) $response['error'];
        }
        return $response;
    }
}
