<?php

namespace Nimbbl\Api;

use JsonSerializable;

abstract class NimbblEntity implements JsonSerializable
{
    protected $attributes = array();
    protected $error;

    public function jsonSerialize()
    {
        return [
            'attributes' => $this->attributes,
            'error' => $this->error,
        ];
    }

    public function __get($key)
    {
        if ($key === 'error') {
            return $this->error;
        } else if ($key === 'attributes') {
            return $this->attributes;
        } else {
            return $this->attributes[$key];
        }
    }

    /**
     * Builds an http query string.
     * @param array $query  // of key value pairs to be used in the query
     * @return string       // http query string.
     **/
    protected function buildHttpQuery($query)
    {
        $query_array = array();
        foreach ($query as $key => $key_value) {
            $query_array[] = urlencode($key) . '=' . urlencode($key_value);
        }
        return implode('&', $query_array);
    }

    protected function fillOne($oneEntity)
    {
        $nimbblSegment = new NimbblSegment();
        $class = $this->entityClass();
        $entity = new $class;

        if (key_exists('error', $oneEntity)) {
            $entity->error = $oneEntity['error'];
        } else {
            $attributes = array();
            foreach ($oneEntity as $key => $value) {
                $attributes[$key] = $value;
            }
            $entity->attributes = $attributes;
        }
        return $entity;
    }

    public abstract function entityClass();

    public abstract function retrieveOne($id);

    public abstract function retrieveMany($options = array());

    public abstract function create($attributes = array());

    public abstract function edit($attributes = null);
}
