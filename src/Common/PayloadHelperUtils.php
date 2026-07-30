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
     * Internal parser that unwraps and decrypts payload data.
     *
     * This method is intentionally private; external callers should use parseResponse().
     * 
     * @param string $payload The raw JSON payload string
     * @param string $secret The merchant's access secret key
     * @return array Processed array containing events attributes for verification
     */
    private static function parseAndUnwrapPayload($payload, $secret)
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
                    // Never log decrypted plaintext (it is PII) — only its type/length.
                    $rawLen = is_string($decrypted) ? strlen($decrypted) : 0;
                    $logger->error("PayloadHelperUtils: after decryption parse failed. raw_type=" . gettype($decrypted) . " raw_len=" . $rawLen);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception("Invalid JSON after decryption: " . json_last_error_msg());
                    }
                    throw new \Exception("Decryption did not produce a JSON object.");
                }
                // The decrypted payload contains PII (email, name, mobile, transaction data).
                // Log only the top-level keys at INFO; the masked body goes to DEBUG.
                $logger->info("PayloadHelperUtils: decryption succeeded. top-level keys=" . implode(',', array_keys($processed)));
                $logger->debug("PayloadHelperUtils: decrypted payload=" . CentralMasker::maskBody(json_encode($processed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
            } elseif (is_array($decoded) && isset($decoded[JsonKeys::CALLBACK]) && is_array($decoded[JsonKeys::CALLBACK])) {
                $processed = $decoded[JsonKeys::CALLBACK];
            }

            $eventTypeStr = isset($processed[JsonKeys::EVENT_TYPE]) && is_string($processed[JsonKeys::EVENT_TYPE]) 
                ? $processed[JsonKeys::EVENT_TYPE] 
                : null;
            $logger->debug("PayloadHelperUtils: eventTypeStr: " . ($eventTypeStr ?? 'null'));

            // Special handling for popup/redirect checkout callback event types.
            // Both globalHandleCheckoutResponse (existing) and globalCloseCheckoutModal (v4 callbackHandler) are valid.
            if ($eventTypeStr === JsonKeys::GLOBAL_HANDLE_CHECKOUT_RESPONSE
                || $eventTypeStr === JsonKeys::GLOBAL_CLOSE_CHECKOUT_MODAL) {
                if (isset($processed[JsonKeys::PAYLOAD]) && is_array($processed[JsonKeys::PAYLOAD])) {
                    $logger->debug("PayloadHelperUtils: detected {$eventTypeStr}, unwrapping nested " . JsonKeys::PAYLOAD . ".");
                    $processed = $processed[JsonKeys::PAYLOAD];
                } elseif (isset($processed['data']) && is_array($processed['data'])) {
                    $logger->debug("PayloadHelperUtils: detected {$eventTypeStr}, unwrapping nested data.");
                    $processed = $processed['data'];
                }
            }

            // v4 signed envelope: `payload` is a Base64-encoded compact JSON string alongside a
            // signature field (nimbbl_signature for payment callbacks, signature for webhook/checkout).
            // Unwrap it so direct callers of parseResponse() get the inner payload. Signature
            // verification itself is done by SignatureVerifier::verifyWebhook()/verifyCallback().
            if (isset($processed[JsonKeys::PAYLOAD]) && is_string($processed[JsonKeys::PAYLOAD])
                && (isset($processed[JsonKeys::NIMBBL_SIGNATURE]) || isset($processed[JsonKeys::SIGNATURE]))) {
                $innerRaw = base64_decode($processed[JsonKeys::PAYLOAD], true);
                if ($innerRaw !== false) {
                    $inner = json_decode($innerRaw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($inner)) {
                        $logger->debug("PayloadHelperUtils: detected v4 envelope, unwrapping Base64 " . JsonKeys::PAYLOAD . ".");
                        // The inner payload may itself be an encrypted wrapper.
                        if (isset($inner[JsonKeys::ENCRYPTED_RESPONSE]) && is_string($inner[JsonKeys::ENCRYPTED_RESPONSE])) {
                            $enc = new \Nimbbl\Api\Common\Encryption($secret);
                            $decryptedInner = $enc->decrypt($inner[JsonKeys::ENCRYPTED_RESPONSE], true);
                            $processed = is_array($decryptedInner) ? $decryptedInner : json_decode($decryptedInner, true);
                        } else {
                            $processed = $inner;
                        }
                    }
                }
            }

            return $processed;
        } catch (\Exception $ex) {
            $failMsg = ErrorMessages::MESSAGE_WEBHOOK_PARSE_ERROR . ": " . $ex->getMessage();
            $logger->error($failMsg);
            // Do not log the raw payload (may carry PII/legacy signed data) at ERROR — only its length.
            // A masked preview is available at DEBUG for troubleshooting.
            $logger->error("PayloadHelperUtils: parse failed. incoming_len=" . (is_string($payload) ? strlen($payload) : 0));
            if (is_string($payload)) {
                $logger->debug("PayloadHelperUtils: incoming payload (masked)=" . CentralMasker::maskBody(substr($payload, 0, 400)));
            }
            throw $ex;
        }
    }

    /**
     * Public entry point for parsing callback/webhook payloads.
     *
     * Parses a payment response string, which can be either base64-encoded or a regular JSON string,
     * then delegates to the internal parser for decryption and unwrapping.
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
                $logger->debug("ParseResponse: Input is base64 encoded. Decoded length=" . strlen($decoded) . " preview=" . CentralMasker::maskBody(substr($decoded, 0, 200)));
                return self::parseAndUnwrapPayload($decoded, $secret);
            } else {
                // If base64 decoding fails, treat the input as a regular JSON string
                $logger->debug("ParseResponse: Input is not base64 encoded, treating as regular JSON string. Length=" . strlen($response) . " preview=" . CentralMasker::maskBody(substr($response, 0, 200)));
                return self::parseAndUnwrapPayload($response, $secret);
            }
        } catch (\Exception $ex) {
            // If base64 decode succeeded but parsing failed, try as direct JSON.
            // Preserve the first parse exception details if the fallback also fails.
            $logger->debug(
                "ParseResponse: Base64 decode succeeded but parse failed, trying as direct JSON. Length=" . strlen($response) . " preview=" . CentralMasker::maskBody(substr($response, 0, 200)) . " first_error=" . $ex->getMessage()
            );
            try {
                return self::parseAndUnwrapPayload($response, $secret);
            } catch (\Exception $ex2) {
                $logger->debug("ParseResponse: direct JSON parse also failed. second_error=" . $ex2->getMessage());
                throw $ex;
            }
        }
    }
}
