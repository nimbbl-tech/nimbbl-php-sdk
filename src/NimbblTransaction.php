<?php

namespace Nimbbl\Api;

use JsonSerializable;

class NimbblTransaction extends NimbblEntity implements JsonSerializable
{
    public static function entityClass()
    {
        return 'Nimbbl\\Api\\NimbblTransaction';
    }

    /**
     *  @param $id Customer id description
     */
    public function retrieveOne($id)
    {
        $nimbblRequest = new NimbblRequest();
        $oneEntity = $nimbblRequest->request('GET', 'transactions/one/' . $id);
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
}
