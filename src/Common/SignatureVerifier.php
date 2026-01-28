<?php

namespace Nimbbl\Api\Common;

use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\ErrorMessages;
use Nimbbl\Api\Common\ErrorCodes;
use Nimbbl\Api\Log\Logger;

/**
 * Utility helpers for signature and webhook verification.
 * Aligned with .NET SDK SignatureVerifier.cs
 */
class SignatureVerifier
{
    /**
     * Verify signature for payment status callbacks and webhooks.
     * Uses format: invoice_id|transaction_id|transaction_amount|transaction_currency|status|transaction_type
     * 
     * @param array $attributes Response attributes containing transaction and order data
     * @param string|null $secretKey Secret key for signature verification
     * @return array Result with success status and message/error
     */
    public function verifyPaymentSignature(array $attributes, $secretKey = null)
    {
        $logger = Logger::getInstance();

        if (empty($secretKey)) {
            $logger->error(ErrorMessages::MESSAGE_AUTHENTICATION_FAILED . ": Secret key is required");
            throw new \InvalidArgumentException("Secret key is required");
        }

        // Log incoming payload for debugging
        $logger->info("VerifyPaymentSignature - Incoming JSON: " . json_encode($attributes));

        $txn = $attributes[JsonKeys::TRANSACTION] ?? null;
        if (empty($txn) || !is_array($txn)) {
            $logger->error(ErrorMessages::MESSAGE_SIGNATURE_VERIFICATION_MISSING_PARAMS . ": " . JsonKeys::TRANSACTION);
            return $this->createResult(false, "Missing " . JsonKeys::TRANSACTION);
        }

        $order = $attributes[JsonKeys::ORDER] ?? [];

        // Read signatureVersion only from inside transaction object
        $signatureVersion = $this->tryGetString($txn, JsonKeys::SIGNATURE_VERSION);
        if (empty($signatureVersion)) {
            $signatureVersion = SdkConstants::SIGNATURE_VERSION_V3;
        }

        // Read signature only from inside transaction object (no fallback to attributes or order)
        $signature = $this->tryGetString($txn, JsonKeys::SIGNATURE);

        // Read transaction_id only from inside the transaction object (no fallback)
        $transactionId = $this->tryGetString($txn, JsonKeys::TRANSACTION_ID);

        $invoiceId = $this->tryGetString($order, JsonKeys::INVOICE_ID);
        $transactionType = $this->tryGetString($txn, JsonKeys::TRANSACTION_TYPE);
        $transactionAmount = $this->tryGetDouble($txn, JsonKeys::TRANSACTION_AMOUNT);
        $transactionCurrency = $this->tryGetString($txn, JsonKeys::TRANSACTION_CURRENCY);
        $status = $this->tryGetString($txn, JsonKeys::STATUS);

        $missing = [];
        if (empty($invoiceId))
            $missing[] = JsonKeys::INVOICE_ID;
        if (empty($transactionId))
            $missing[] = JsonKeys::TRANSACTION_ID;
        if ($transactionAmount === null)
            $missing[] = JsonKeys::TRANSACTION_AMOUNT;
        if (empty($transactionCurrency))
            $missing[] = JsonKeys::TRANSACTION_CURRENCY;
        if (empty($status))
            $missing[] = JsonKeys::STATUS;
        if (empty($transactionType))
            $missing[] = JsonKeys::TRANSACTION_TYPE;
        if (empty($signature))
            $missing[] = JsonKeys::SIGNATURE;

        if (!empty($missing)) {
            $logger->error(ErrorMessages::MESSAGE_SIGNATURE_VERIFICATION_MISSING_PARAMS . ": " . implode(", ", $missing));
            return $this->createResult(false, "Missing " . implode(", ", $missing));
        }

        $amountStr = $this->formatAmount($transactionAmount);
        $payload = "{$invoiceId}|{$transactionId}|{$amountStr}|{$transactionCurrency}|{$status}|{$transactionType}";
        $expected = hash_hmac('sha256', $payload, $secretKey);

        if (!hash_equals($expected, $signature)) {
            $failMsg = "Signature Version: {$signatureVersion}, Invoice ID: {$invoiceId}, Transaction ID: {$transactionId}, Amount: {$amountStr}, Currency: {$transactionCurrency}, Status: {$status}, Type: {$transactionType}";
            $logger->error(ErrorMessages::MESSAGE_SIGNATURE_VERIFICATION_FAILED . " - {$failMsg}");
            return $this->createResult(false, $failMsg);
        }

        $okMsg = "Signature verification succeeded - Signature Version: {$signatureVersion}, Invoice ID: {$invoiceId}, Transaction ID: {$transactionId}, Amount: {$amountStr}";
        $logger->info($okMsg);
        return $this->createResult(true, $okMsg);
    }

    /**
     * Verify signature for refund callbacks and webhooks.
     * Uses format: invoice_id|transaction_id|refund_amount|transaction_currency|refund_status|transaction_type
     * 
     * @param array $attributes
     * @param string|null $secretKey
     * @return array
     */
    public function verifyRefundSignature(array $attributes, $secretKey = null)
    {
        $logger = Logger::getInstance();

        if (empty($secretKey)) {
            $logger->error(ErrorMessages::MESSAGE_AUTHENTICATION_FAILED . ": Secret key is required");
            throw new \InvalidArgumentException("Secret key is required");
        }

        $txn = $attributes[JsonKeys::TRANSACTION] ?? null;
        if (empty($txn) || !is_array($txn)) {
            $logger->error(ErrorMessages::MESSAGE_SIGNATURE_VERIFICATION_MISSING_PARAMS . ": " . JsonKeys::TRANSACTION);
            return $this->createResult(false, "Missing " . JsonKeys::TRANSACTION);
        }

        $order = $attributes[JsonKeys::ORDER] ?? [];

        // Read signatureVersion only from inside transaction object
        $signatureVersion = $this->tryGetString($txn, JsonKeys::SIGNATURE_VERSION);
        if (empty($signatureVersion)) {
            $signatureVersion = SdkConstants::SIGNATURE_VERSION_V3;
        }

        if ($signatureVersion !== SdkConstants::SIGNATURE_VERSION_V3) {
            $failMsg = "Unsupported signature version: {$signatureVersion}. Only " . SdkConstants::SIGNATURE_VERSION_V3 . " is supported.";
            $logger->error(ErrorMessages::MESSAGE_SIGNATURE_VERIFICATION_FAILED . " - {$failMsg}");
            return $this->createResult(false, $failMsg);
        }

        // Read signature only from inside transaction object (no fallback to attributes or order)
        $signature = $this->tryGetString($txn, JsonKeys::SIGNATURE);

        // Read transaction_id only from inside the transaction object (no fallback)
        $transactionId = $this->tryGetString($txn, JsonKeys::TRANSACTION_ID);

        $invoiceId = $this->tryGetString($order, JsonKeys::INVOICE_ID);
        $transactionType = $this->tryGetString($txn, JsonKeys::TRANSACTION_TYPE);
        $refundAmount = $this->tryGetDouble($txn, JsonKeys::REFUND_AMOUNT);
        $transactionCurrency = $this->tryGetString($txn, JsonKeys::TRANSACTION_CURRENCY);
        $status = $this->tryGetString($txn, JsonKeys::REFUND_STATUS);

        $missing = [];
        if (empty($invoiceId))
            $missing[] = JsonKeys::INVOICE_ID;
        if (empty($transactionId))
            $missing[] = JsonKeys::TRANSACTION_ID;
        if ($refundAmount === null)
            $missing[] = JsonKeys::REFUND_AMOUNT;
        if (empty($transactionCurrency))
            $missing[] = JsonKeys::TRANSACTION_CURRENCY;
        if (empty($status))
            $missing[] = JsonKeys::REFUND_STATUS;
        if (empty($transactionType))
            $missing[] = JsonKeys::TRANSACTION_TYPE;
        if (empty($signature))
            $missing[] = JsonKeys::SIGNATURE;

        if (!empty($missing)) {
            $logger->error(ErrorMessages::MESSAGE_SIGNATURE_VERIFICATION_MISSING_PARAMS . ": " . implode(", ", $missing));
            return $this->createResult(false, "Missing " . implode(", ", $missing));
        }

        $amountStr = $this->formatAmount($refundAmount);
        $payload = "{$invoiceId}|{$transactionId}|{$amountStr}|{$transactionCurrency}|{$status}|{$transactionType}";
        $expected = hash_hmac('sha256', $payload, $secretKey);

        if (!hash_equals($expected, $signature)) {
            $failMsg = "Signature Version: {$signatureVersion}, Invoice ID: {$invoiceId}, Transaction ID: {$transactionId}, Refund Amount: {$amountStr}, Currency: {$transactionCurrency}, Status: {$status}, Type: {$transactionType}";
            $logger->error(ErrorMessages::MESSAGE_SIGNATURE_VERIFICATION_FAILED . " - {$failMsg}");
            return $this->createResult(false, $failMsg);
        }

        $okMsg = "Signature verification succeeded - Signature Version: {$signatureVersion}, Invoice ID: {$invoiceId}, Transaction ID: {$transactionId}, Refund Amount: {$amountStr}";
        $logger->info($okMsg);
        return $this->createResult(true, $okMsg);
    }

    /**
     * Verify signature for payment link callbacks and webhooks.
     * Uses format: invoice_id|status|currency|amount_paid|payment_link_hash
     * 
     * @param array $attributes
     * @param string|null $secretKey
     * @return array
     */
    public function verifyPaymentLinkSignature(array $attributes, $secretKey = null)
    {
        $logger = Logger::getInstance();

        if (empty($secretKey)) {
            $logger->error(ErrorMessages::MESSAGE_AUTHENTICATION_FAILED . ": Secret key is required");
            throw new \InvalidArgumentException("Secret key is required");
        }

        $signatureVersion = $this->tryGetString($attributes, JsonKeys::SIGNATURE_VERSION)
            ?? SdkConstants::SIGNATURE_VERSION_V3;

        if ($signatureVersion !== SdkConstants::SIGNATURE_VERSION_V3) {
            $failMsg = "Unsupported signature version: {$signatureVersion}. Only " . SdkConstants::SIGNATURE_VERSION_V3 . " is supported.";
            $logger->error(ErrorMessages::MESSAGE_SIGNATURE_VERIFICATION_FAILED . " - {$failMsg}");
            return $this->createResult(false, $failMsg);
        }

        $signature = $this->tryGetString($attributes, JsonKeys::NIMBBL_SIGNATURE)
            ?? $this->tryGetString($attributes, JsonKeys::SIGNATURE);

        $invoiceId = $this->tryGetString($attributes, JsonKeys::INVOICE_ID);
        $status = $this->tryGetString($attributes, JsonKeys::STATUS);
        $currency = $this->tryGetString($attributes, JsonKeys::CURRENCY);

        $amountPaid = $this->tryGetDouble($attributes, JsonKeys::AMOUNT_PAID)
            ?? $this->tryGetDouble($attributes, JsonKeys::PAYMENT_LINK_AMOUNT_PAID)
            ?? 0.0;

        $paymentLinkHash = $this->tryGetString($attributes, JsonKeys::PAYMENT_LINK_HASH);

        $missing = [];
        if (empty($invoiceId))
            $missing[] = JsonKeys::INVOICE_ID;
        if (empty($status))
            $missing[] = JsonKeys::STATUS;
        if (empty($currency))
            $missing[] = JsonKeys::CURRENCY;
        if (empty($paymentLinkHash))
            $missing[] = JsonKeys::PAYMENT_LINK_HASH;
        if (empty($signature))
            $missing[] = JsonKeys::SIGNATURE;

        if (!empty($missing)) {
            $logger->error(ErrorMessages::MESSAGE_SIGNATURE_VERIFICATION_MISSING_PARAMS . ": " . implode(", ", $missing));
            return $this->createResult(false, "Missing " . implode(", ", $missing));
        }

        $amountStr = $this->formatAmount($amountPaid);
        $payload = "{$invoiceId}|{$status}|{$currency}|{$amountStr}|{$paymentLinkHash}";
        $expected = hash_hmac('sha256', $payload, $secretKey);

        if (!hash_equals($expected, $signature)) {
            $failMsg = "Signature Version: {$signatureVersion}, Invoice ID: {$invoiceId}, Status: {$status}, Currency: {$currency}, Amount Paid: {$amountStr}, Payment Link Hash: {$paymentLinkHash}";
            $logger->error(ErrorMessages::MESSAGE_SIGNATURE_VERIFICATION_FAILED . " - {$failMsg}");
            return $this->createResult(false, $failMsg);
        }

        $okMsg = "Signature verification succeeded - Signature Version: {$signatureVersion}, Invoice ID: {$invoiceId}, Amount Paid: {$amountStr}";
        $logger->info($okMsg);
        return $this->createResult(true, $okMsg);
    }

    /**
     * Verify signature for payment/refund callbacks and webhooks.
     * Routes to the appropriate verification method based on webhook event type.
     * 
     * @param array $attributes
     * @param string|null $secretKey
     * @return array Result array
     */
    public function verifySignature(array $attributes, $secretKey = null)
    {
        $logger = Logger::getInstance();

        $eventTypeStr = $this->tryGetString($attributes, JsonKeys::EVENT_TYPE);
        if (empty($eventTypeStr)) {
            $failMsg = "Missing required field: " . JsonKeys::EVENT_TYPE . ". Invalid webhook payload.";
            $logger->error(ErrorMessages::MESSAGE_SIGNATURE_VERIFICATION_FAILED . " - {$failMsg}");
            return $this->createResult(false, $failMsg);
        }

        // Determine event type
        if ($this->isPaymentLinkEvent($eventTypeStr)) {
            return $this->verifyPaymentLinkSignature($attributes, $secretKey);
        }

        if ($this->isRefundEvent($eventTypeStr)) {
            return $this->verifyRefundSignature($attributes, $secretKey);
        }

        // Default to payment signature verification
        return $this->verifyPaymentSignature($attributes, $secretKey);
    }

    /**
     * Verify signature for payment callbacks from the popup/redirect checkout.
     * Handles nested "payload" structures and automatically detects/decrypts encrypted responses.
     * 
     * @param array $payload Response attributes containing transaction and order data
     * @param string|null $secretKey Secret key for signature verification
     * @return array Result with success status and message/error
     */
    public function verifyCallbackSignature(array $payload, $secretKey = null)
    {
        return $this->verifyPaymentSignature($payload, $secretKey);
    }

    /**
     * Verify and parse webhook payload
     * 
     * @param string $payload JSON payload string
     * @param string|null $secretKey
     * @return array Result array with 'success' and 'parsed' data or error
     */
    public function verifyAndParseWebhook($payload, $secretKey)
    {
        $logger = Logger::getInstance();
        $parsed = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $logger->error("Webhook parse error: " . json_last_error_msg());
            return $this->createResult(false, "Invalid JSON payload");
        }

        $result = $this->verifySignature($parsed, $secretKey);

        if ($result['success']) {
            $logger->info(ErrorMessages::MESSAGE_SIGNATURE_VERIFICATION_SUCCESS);
            $result['parsed'] = $parsed;
        } else {
            $logger->error(ErrorMessages::MESSAGE_WEBHOOK_VERIFICATION_FAILED . ": " . $result['message']);
        }

        return $result;
    }

    /**
     * Parse webhook event without verification
     */
    public function parseWebhookEvent($payload)
    {
        if (empty($payload))
            return null;
        $decoded = json_decode($payload, true);
        return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : null;
    }

    // --- Helper Methods ---

    private function createResult($success, $message, $error = null)
    {
        return [
            'success' => $success,
            'message' => $message,
            'error' => $error
        ];
    }

    private function tryGetString($array, $key)
    {
        if (!is_array($array))
            return null;
        return isset($array[$key]) && is_string($array[$key]) ? $array[$key] : null;
    }

    private function tryGetDouble($array, $key)
    {
        if (!is_array($array) || !isset($array[$key]))
            return null;
        $val = $array[$key];
        if (is_numeric($val))
            return (float) $val;
        return null;
    }

    private function formatAmount($amount)
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function isPaymentLinkEvent($eventType)
    {
        $types = [
            'payment_link_paid',
            'payment_link_expired',
            'payment_link_cancelled',
            'payment_link_sent',
            'payment_link_opened',
            'payment_link_created'
        ];
        return in_array(strtolower($eventType), $types, true);
    }

    private function isRefundEvent($eventType)
    {
        $types = [
            'refund_pending',
            'refund_success',
            'refund_failed'
        ];
        return in_array(strtolower($eventType), $types, true);
    }

    /**
     * Verify payment signature (legacy helper expected by sample apps).
     *
     * Signature format: HMAC_SHA256(transaction_id|amount, access_secret)
     * Amount is normalized to 2 decimals (e.g., "100" -> "100.00") to match SDK behavior.
     *
     * @param string $signature Provided signature (hex)
     * @param string $transactionId Transaction ID
     * @param string|float|int $amount Amount
     * @param string $secret Access secret
     * @return bool True if signature matches, else false
     */
    public static function verifyPaymentSignatureLegacy($signature, $transactionId, $amount, $secret)
    {
        $logger = Logger::getInstance();
        try {
            if (empty($secret) || empty($transactionId) || empty($signature) || $amount === null) {
                $logger->error(ErrorMessages::SIGNATURE_VERIFICATION_FAILED_MISSING_PARAMS);
                return false;
            }

            $amountStr = number_format((float) $amount, 2, '.', '');
            $payload = $transactionId . '|' . $amountStr;
            $expected = hash_hmac('sha256', $payload, $secret);
            $ok = hash_equals($expected, (string) $signature);

            if ($ok) {
                $logger->info(ErrorMessages::SIGNATURE_VERIFICATION_SUCCESS . " - Transaction ID: {$transactionId}, Amount: {$amountStr}");
            } else {
                $logger->error(ErrorMessages::SIGNATURE_VERIFICATION_FAILED . " - Transaction ID: {$transactionId}, Amount: {$amountStr}");
            }

            return $ok;
        } catch (\Throwable $e) {
            // Don't throw from verification helper; match typical SDK helper behavior.
            try {
                $ex = $e instanceof \Exception ? $e : new \Exception($e->getMessage());
                $logger->exception(ErrorMessages::SIGNATURE_VERIFICATION_ERROR . ErrorMessages::ERROR_PREFIX_GENERAL . $e->getMessage(), $ex);
            } catch (\Throwable $t) {
                // ignore
            }
            return false;
        }
    }
}
