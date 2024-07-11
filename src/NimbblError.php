<?php

namespace Nimbbl\Api;

use Exception;

class NimbblError extends Exception
{
    protected $httpStatusCode;

    public function __construct($message, $code, $httpStatusCode)
    {
        $this->code = $code;

        $this->message = $message;

        $this->httpStatusCode = $httpStatusCode;
    }

    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }
}