<?php

namespace Nimbbl\Api;

/**
 * Nimbbl Webhook Handler
 * 
 * Provides methods for verifying and parsing webhook events from Nimbbl
 * 
 * Implements WebhookInterface for webhook-related operations
 */
class Webhook
{
    /**
     * Verify webhook signature
     * @param string $payload
     * @param string $signature
     * @param string $secret
     * @return bool
     */
    public function verifyWebhook($payload, $signature, $secret)
    {
        $logger = Logger::getInstance();
        try {
            if (empty($payload) || empty($signature) || empty($secret)) {
                $logger->log(
                    ErrorMessages::WEBHOOK_VERIFICATION_FAILED_MISSING_PARAMS,
                    SdkConstants::LOG_ERROR,
                    SdkConstants::COMPONENT_WEBHOOK
                );
                return false;
            }

            $expectedSignature = hash_hmac('sha256', $payload, $secret);
            
            $verified = function_exists('hash_equals')
                ? hash_equals($expectedSignature, $signature)
                : $this->hashEquals($expectedSignature, $signature);
            
            if (!$verified) {
                $logger->log(ErrorMessages::WEBHOOK_SIGNATURE_VERIFICATION_FAILED, SdkConstants::LOG_ERROR, SdkConstants::COMPONENT_WEBHOOK);
            } else {
                $logger->log(ErrorMessages::WEBHOOK_SIGNATURE_VERIFICATION_SUCCESS, SdkConstants::LOG_DEBUG, SdkConstants::COMPONENT_WEBHOOK);
            }
            
            return $verified;
        } catch (\Exception $e) {
            $logger->log(
                ErrorMessages::WEBHOOK_VERIFICATION_ERROR . ErrorMessages::ERROR_PREFIX_GENERAL . $e->getMessage(),
                SdkConstants::LOG_ERROR,
                SdkConstants::COMPONENT_WEBHOOK
            );
            throw $e;
        }
    }

    /**
     * Parse webhook event
     * @param string $payload
     * @return array|null
     */
    public function parseWebhookEvent($payload)
    {
        $logger = Logger::getInstance();
        try {
            if (empty($payload)) {
                $logger->log(
                    ErrorMessages::WEBHOOK_PARSE_ERROR_EMPTY_PAYLOAD,
                    SdkConstants::LOG_ERROR,
                    SdkConstants::COMPONENT_WEBHOOK
                );
                return null;
            }

            $event = json_decode($payload, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $logger->log(
                    ErrorMessages::WEBHOOK_PARSE_ERROR_JSON . json_last_error_msg(),
                    SdkConstants::LOG_ERROR,
                    SdkConstants::COMPONENT_WEBHOOK
                );
                return null;
            }

            $logger->log(
                ErrorMessages::WEBHOOK_PARSED_SUCCESS . ($event['event'] ?? 'unknown'),
                SdkConstants::LOG_DEBUG,
                SdkConstants::COMPONENT_WEBHOOK
            );

            return $event;
        } catch (\Exception $e) {
            $logger->log(
                ErrorMessages::WEBHOOK_PARSE_ERROR . ErrorMessages::ERROR_PREFIX_GENERAL . $e->getMessage(),
                SdkConstants::LOG_ERROR,
                SdkConstants::COMPONENT_WEBHOOK
            );
            throw $e;
        }
    }

    /**
     * Verify and parse webhook
     * @param string $payload
     * @param string $signature
     * @param string $secret
     * @return array|null
     */
    public function verifyAndParse($payload, $signature, $secret)
    {
        try {
            if (!$this->verifyWebhook($payload, $signature, $secret)) {
                return null;
            }

            return $this->parseWebhookEvent($payload);
        } catch (\Exception $e) {
            $logger = Logger::getInstance();
            $logger->log(
                ErrorMessages::WEBHOOK_VERIFY_AND_PARSE_ERROR . ErrorMessages::ERROR_PREFIX_GENERAL . $e->getMessage(),
                SdkConstants::LOG_ERROR,
                SdkConstants::COMPONENT_WEBHOOK
            );
            throw $e;
        }
    }

    /**
     * Time-constant string comparison
     * @param string $expected
     * @param string $actual
     * @return bool
     */
    private function hashEquals($expected, $actual)
    {
        if (strlen($expected) !== strlen($actual)) {
            return false;
        }

        $res = $expected ^ $actual;
        $result = 0;
        for ($i = strlen($res) - 1; $i >= 0; $i--) {
            $result |= ord($res[$i]);
        }

        return $result === 0;
    }

    /**
     * Get webhook signature from headers
     * @param array $headers
     * @return string|null
     */
    public function getSignatureFromHeaders($headers)
    {
        try {
            // Try different header name variations
            $headerNames = [
                'X-Nimbbl-Signature',
                'x-nimbbl-signature',
                'X-NIMBBL-SIGNATURE',
                'HTTP_X_NIMBBL_SIGNATURE', // For $_SERVER
            ];

            foreach ($headerNames as $headerName) {
                if (isset($headers[$headerName])) {
                    return $headers[$headerName];
                }
            }

            return null;
        } catch (\Exception $e) {
            $logger = Logger::getInstance();
            $logger->log(
                ErrorMessages::WEBHOOK_GET_SIGNATURE_FROM_HEADERS_ERROR . ErrorMessages::ERROR_PREFIX_GENERAL . $e->getMessage(),
                SdkConstants::LOG_ERROR,
                SdkConstants::COMPONENT_WEBHOOK
            );
            throw $e;
        }
    }

    /**
     * Get webhook payload from input stream
     * @return string|null
     */
    public function getPayloadFromInput()
    {
        $logger = Logger::getInstance();
        try {
            $payload = file_get_contents('php://input');
            
            if ($payload === false) {
                $logger->log(
                    ErrorMessages::WEBHOOK_GET_PAYLOAD_FROM_INPUT_FAILED,
                    SdkConstants::LOG_ERROR,
                    SdkConstants::COMPONENT_WEBHOOK
                );
                throw new \Exception(ErrorMessages::WEBHOOK_PAYLOAD_READ_FAILED);
            }
            
            return $payload;
        } catch (\Exception $e) {
            $logger->log(
                ErrorMessages::WEBHOOK_GET_PAYLOAD_FROM_INPUT_ERROR . ErrorMessages::ERROR_PREFIX_GENERAL . $e->getMessage(),
                SdkConstants::LOG_ERROR,
                SdkConstants::COMPONENT_WEBHOOK
            );
            throw $e;
        }
    }
}

