<?php

namespace Nimbbl\Api\Common;

use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\ErrorMessages;
use Nimbbl\Api\Log\Logger;

/**
 * Generic helper to unwrap, decrypt, and sanitize webhook/callback payloads.
 * Aligned with .NET SDK PayloadHelperUtils.cs
 */
class PayloadHelperUtils
{
    /**
     * Parses the raw callback or webhook payload, handling decryption and "payload" unwrapping internally.
     * Use this before calling VerifySignature.
     * 
     * @param string $payload The raw JSON payload string
     * @param string $secret The merchant's access secret key
     * @return array Processed array containing events attributes for verification
     */
    public static function parse($payload, $secret)
    {
        $logger = Logger::getInstance();
        try {
            $decoded = json_decode($payload, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON: " . json_last_error_msg());
            }

            // 1. Detection logic for encrypted_response at various levels
            $encryptedResponse = null;
            if (is_array($decoded)) {
                if (isset($decoded[JsonKeys::ENCRYPTED_RESPONSE]) && is_string($decoded[JsonKeys::ENCRYPTED_RESPONSE])) {
                    $encryptedResponse = $decoded[JsonKeys::ENCRYPTED_RESPONSE];
                } elseif (isset($decoded[JsonKeys::PAYLOAD][JsonKeys::ENCRYPTED_RESPONSE]) && is_string($decoded[JsonKeys::PAYLOAD][JsonKeys::ENCRYPTED_RESPONSE])) {
                    $encryptedResponse = $decoded[JsonKeys::PAYLOAD][JsonKeys::ENCRYPTED_RESPONSE];
                } elseif (isset($decoded[JsonKeys::CALLBACK][JsonKeys::ENCRYPTED_RESPONSE]) && is_string($decoded[JsonKeys::CALLBACK][JsonKeys::ENCRYPTED_RESPONSE])) {
                    $encryptedResponse = $decoded[JsonKeys::CALLBACK][JsonKeys::ENCRYPTED_RESPONSE];
                }
            }

            $processed = $decoded;
            if (!empty($encryptedResponse)) {
                $logger->info("PayloadHelperUtils: decrypting " . JsonKeys::ENCRYPTED_RESPONSE . ".");
                $enc = new \Nimbbl\Api\Encryption($secret);
                $decrypted = $enc->decrypt($encryptedResponse, true);
                $processed = json_decode($decrypted, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Invalid JSON after decryption: " . json_last_error_msg());
                }
            } elseif (is_array($decoded) && isset($decoded[JsonKeys::CALLBACK]) && is_array($decoded[JsonKeys::CALLBACK])) {
                $processed = $decoded[JsonKeys::CALLBACK];
            }

            $eventTypeStr = isset($processed[JsonKeys::EVENT_TYPE]) && is_string($processed[JsonKeys::EVENT_TYPE]) 
                ? $processed[JsonKeys::EVENT_TYPE] 
                : null;
            $logger->debug("PayloadHelperUtils: eventTypeStr: " . ($eventTypeStr ?? 'null'));

            // Special handling for popup/redirect callback event type
            if ($eventTypeStr === JsonKeys::GLOBAL_HANDLE_CHECKOUT_RESPONSE) {
                if (isset($processed[JsonKeys::PAYLOAD]) && is_array($processed[JsonKeys::PAYLOAD])) {
                    $logger->debug("PayloadHelperUtils: detected " . JsonKeys::GLOBAL_HANDLE_CHECKOUT_RESPONSE . ", unwrapping nested " . JsonKeys::PAYLOAD . ".");
                    $processed = $processed[JsonKeys::PAYLOAD];
                }
            }

            return $processed;
        } catch (\Exception $ex) {
            $failMsg = ErrorMessages::MESSAGE_WEBHOOK_PARSE_ERROR . ": " . $ex->getMessage();
            $logger->error($failMsg);
            throw $ex;
        }
    }

    /**
     * Parses a payment response string, which can be either base64-encoded or a regular JSON string.
     * Automatically detects the format and handles both cases.
     * 
     * @param string $response The base64 encoded JSON response, or a regular JSON string
     * @param string $secret The merchant's access secret key
     * @return array Processed array containing events attributes for verification
     */
    public static function parseResponse($response, $secret)
    {
        $logger = Logger::getInstance();
        if (empty($response)) {
            throw new \InvalidArgumentException("Response cannot be empty");
        }
        
        try {
            // Try to decode as base64 first
            $decoded = base64_decode($response, true);
            if ($decoded !== false) {
                // Successfully decoded as base64, now parse as JSON
                return self::parse($decoded, $secret);
            } else {
                // If base64 decoding fails, treat the input as a regular JSON string
                $logger->debug("ParseResponse: Input is not base64 encoded, treating as regular JSON string");
                return self::parse($response, $secret);
            }
        } catch (\Exception $ex) {
            // If base64 decode succeeded but JSON parse failed, try as direct JSON
            $logger->debug("ParseResponse: Base64 decode succeeded but parse failed, trying as direct JSON");
            return self::parse($response, $secret);
        }
    }
}
