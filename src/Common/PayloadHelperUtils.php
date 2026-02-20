<?php

namespace Nimbbl\Api\Common;

use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\ErrorMessages;
use Nimbbl\Api\Log\Logger;

/**
 * Generic helper to unwrap, decrypt, and sanitize webhook/callback payloads.
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
                $enc = new \Nimbbl\Api\Common\Encryption($secret);
                $decrypted = $enc->decrypt($encryptedResponse, true);
                $processed = is_array($decrypted) ? $decrypted : json_decode($decrypted, true);
                if (!is_array($processed)) {
                    $rawPreview = is_string($decrypted) ? substr($decrypted, 0, 500) : gettype($decrypted);
                    $rawLen = is_string($decrypted) ? strlen($decrypted) : 0;
                    $logger->error("PayloadHelperUtils: after decryption parse failed. raw_type=" . gettype($decrypted) . " raw_len=" . $rawLen . " raw_preview=" . (is_string($rawPreview) ? $rawPreview : $rawPreview));
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception("Invalid JSON after decryption: " . json_last_error_msg());
                    }
                    throw new \Exception("Decryption did not produce a JSON object.");
                }
                $logger->info("PayloadHelperUtils: after decryption top-level keys=" . implode(',', array_keys($processed)) . " full=" . json_encode($processed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
                } elseif (isset($processed['data']) && is_array($processed['data'])) {
                    $logger->debug("PayloadHelperUtils: detected " . JsonKeys::GLOBAL_HANDLE_CHECKOUT_RESPONSE . ", unwrapping nested data.");
                    $processed = $processed['data'];
                }
            }

            return $processed;
        } catch (\Exception $ex) {
            $failMsg = ErrorMessages::MESSAGE_WEBHOOK_PARSE_ERROR . ": " . $ex->getMessage();
            $logger->error($failMsg);
            $payloadPreview = is_string($payload) ? substr($payload, 0, 400) : gettype($payload);
            $logger->error("PayloadHelperUtils: parse failed. incoming_payload_preview=" . (is_string($payloadPreview) ? $payloadPreview : $payloadPreview) . " incoming_len=" . (is_string($payload) ? strlen($payload) : 0));
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
                $logger->debug("ParseResponse: Input is base64 encoded. Decoded length=" . strlen($decoded) . " preview=" . substr($decoded, 0, 200));
                return self::parse($decoded, $secret);
            } else {
                // If base64 decoding fails, treat the input as a regular JSON string
                $logger->debug("ParseResponse: Input is not base64 encoded, treating as regular JSON string. Length=" . strlen($response) . " preview=" . substr($response, 0, 200));
                return self::parse($response, $secret);
            }
        } catch (\Exception $ex) {
            // If base64 decode succeeded but JSON parse failed, try as direct JSON
            $logger->debug("ParseResponse: Base64 decode succeeded but parse failed, trying as direct JSON. Length=" . strlen($response) . " preview=" . substr($response, 0, 200));
            return self::parse($response, $secret);
        }
    }
}
