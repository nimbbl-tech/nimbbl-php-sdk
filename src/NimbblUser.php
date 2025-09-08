<?php

namespace Nimbbl\Api;

use JsonSerializable;

class NimbblUser extends NimbblEntity implements JsonSerializable
{
    public function entityClass()
    {
        error_log(__FILE__ . ": NimbblUser::entityClass START" . PHP_EOL);
        $class = 'Nimbbl\\Api\\NimbblUser';
        error_log(__FILE__ . ": NimbblUser::entityClass END" . PHP_EOL);
        return $class;
    }

    /**
     *  @param $id Customer id description
     */
    public function retrieveOne($id)
    {
        error_log(__FILE__ . ": NimbblUser::retrieveOne START" . PHP_EOL);
        $nimbblRequest = new NimbblRequest();
        error_log(__FILE__ . ": NimbblUser::retrieveOne API REQUEST: id = $id" . PHP_EOL);
        $oneEntity = $nimbblRequest->request('GET', 'users/one/' . $id);
        error_log(__FILE__ . ": NimbblUser::retrieveOne API RESPONSE: " . print_r($oneEntity, true) . PHP_EOL);
        $loadedEntity = $this->fillOne($oneEntity);
        $this->attributes = $loadedEntity->attributes;
        $this->error = $loadedEntity->error;
        error_log(__FILE__ . ": NimbblUser::retrieveOne END" . PHP_EOL);
        return $this;
    }

    public function retrieveMany($options = array())
    {
        error_log(__FILE__ . ": NimbblUser::retrieveMany START" . PHP_EOL);
        $f = base64_encode($this->buildHttpQuery($options));
        $nimbblRequest = new NimbblRequest();
        error_log(__FILE__ . ": NimbblUser::retrieveMany API REQUEST: options = " . print_r($options, true) . PHP_EOL);
        $manyEntities = $nimbblRequest->request('GET', 'users/many?f=' . $f . '&pt=no');
        error_log(__FILE__ . ": NimbblUser::retrieveMany API RESPONSE: " . print_r($manyEntities, true) . PHP_EOL);
        $users = array();
        if (is_array($manyEntities) && isset($manyEntities['items']) && is_array($manyEntities['items'])) {
            foreach ($manyEntities['items'] as $idx => $oneEntity) {
                $users[] = $this->fillOne($oneEntity);
            }
        }
        error_log(__FILE__ . ": NimbblUser::retrieveMany END" . PHP_EOL);
        return [
            'items' => $users,
            'meta' => $manyEntities['_meta'] ?? []
        ];
    }

    public function create($attributes = array())
    {
        error_log(__FILE__ . ": NimbblUser::create START" . PHP_EOL);
        throw new Exception("Unsupported operation.");
        error_log(__FILE__ . ": NimbblUser::create END" . PHP_EOL);
    }

    public function edit($attributes = null)
    {
        error_log(__FILE__ . ": NimbblUser::edit START" . PHP_EOL);
        throw new Exception("Unsupported operation.");
        error_log(__FILE__ . ": NimbblUser::edit END" . PHP_EOL);
    }
}
