<?php

namespace Nimbbl\Api\Exception;

/**
 * Base exception class for all Nimbbl SDK exceptions
 */
class NimbblException extends \Exception
{
    protected $errorCode;
    protected $requestId;
    protected $httpStatusCode;
    protected $errorData;

    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param string|null $errorCode Nimbbl error code
     * @param string|null $requestId Request ID for tracking
     * @param int|null $httpStatusCode HTTP status code
     * @param array|null $errorData Additional error data
     * @param \Exception|null $previous Previous exception
     */
    public function __construct(
        $message = '',
        $errorCode = null,
        $requestId = null,
        $httpStatusCode = null,
        $errorData = null,
        $previous = null
    ) {
        parent::__construct($message, (int) ($httpStatusCode ?? 0), $previous);
        $this->errorCode = $errorCode;
        $this->requestId = $requestId;
        $this->httpStatusCode = $httpStatusCode;
        $this->errorData = $errorData;
    }

    /**
     * Get Nimbbl error code
     * 
     * @return string|null
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Get request ID
     * 
     * @return string|null
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Get HTTP status code
     * 
     * @return int|null
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    /**
     * Get additional error data
     * 
     * @return array|null
     */
    public function getErrorData()
    {
        return $this->errorData;
    }

    /**
     * Convert exception to array
     * 
     * @return array
     */
    public function toArray()
    {
        return [
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'request_id' => $this->requestId,
            'http_status_code' => $this->httpStatusCode,
            'error_data' => $this->errorData,
        ];
    }
}

