<?php

namespace Nimbbl\Api\Common;

use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\ErrorMessages;
use Nimbbl\Api\Common\ErrorCodes;
use Nimbbl\Api\Log\Logger;

/**
 * Utility helpers for signature and webhook verification.
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

        // Log incoming payload (PII masked). Avoid dumping raw webhook payload at INFO level.
        $encoded = json_encode($attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $masked = is_string($encoded) ? CentralMasker::maskBody($encoded) : '[unserializable payload]';
        $logger->info("VerifyPaymentSignature - Incoming JSON: " . $masked);

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

        // Payment signatures currently only support v3.
        // If a payload provides an unknown/downgraded signature version, fail explicitly.
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
        
        // For refunds, get currency from order.refund_details.refundable_currency
        $refundDetails = $order[JsonKeys::REFUND_DETAILS] ?? null;
        $transactionCurrency = null;
        if (is_array($refundDetails)) {
            $transactionCurrency = $this->tryGetString($refundDetails, JsonKeys::REFUNDABLE_CURRENCY);
        }
        
        $status = $this->tryGetString($txn, JsonKeys::REFUND_STATUS);

        $missing = [];
        if (empty($invoiceId))
            $missing[] = JsonKeys::INVOICE_ID;
        if (empty($transactionId))
            $missing[] = JsonKeys::TRANSACTION_ID;
        if ($refundAmount === null)
            $missing[] = JsonKeys::REFUND_AMOUNT;
        if (empty($transactionCurrency))
            $missing[] = JsonKeys::REFUNDABLE_CURRENCY;
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
     * This is a thin wrapper over `verifyPaymentSignature()` for the callback payload structure.
     * 
     * @param array $payload Response attributes containing transaction and order data
     * @param string|null $secretKey Secret key for signature verification
     * @return array Result with success status and message/error
     */
    public function verifyCallbackSignature(array $payload, $secretKey = null)
    {
        return $this->verifyPaymentSignature($payload, $secretKey);
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
}
