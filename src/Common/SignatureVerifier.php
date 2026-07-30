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

    /**
     * Low-level v4 envelope signature check.
     * The v4 signature is an HMAC-SHA256 of the ENTIRE compact JSON string
     * (the Base64-decoded `payload`) — not a per-field concatenation.
     *
     * @param string $rawCompactJson The exact Base64-decoded payload string as Nimbbl sent it
     * @param string $providedSignature The signature from the envelope
     * @param string|null $secret access_secret
     * @return bool
     */
    public function verifyEnvelopeSignature($rawCompactJson, $providedSignature, $secret = null)
    {
        if (empty($secret) || empty($providedSignature) || !is_string($rawCompactJson)) {
            return false;
        }
        $expected = hash_hmac('sha256', $rawCompactJson, $secret);
        return hash_equals($expected, $providedSignature);
    }

    /**
     * Version-aware webhook verification entry point.
     *
     * Uses the top-level `version` field as the source of truth:
     *  - version === "v4"  -> new envelope handling (HMAC over whole compact JSON; encrypted => decrypt authenticates)
     *  - version absent / v1 / v2 / v3 -> legacy handling (unchanged: parseResponse + verifySignature)
     *
     * @param string $rawBody The raw webhook POST body
     * @param string|null $secret access_secret
     * @return array ['success'=>bool,'message'=>string,'version'=>?string,'event_type'=>?string,'payload'=>?array]
     */
    public function verifyWebhook($rawBody, $secret = null)
    {
        $logger = Logger::getInstance();

        if (!is_string($rawBody) || $rawBody === '') {
            return $this->createResult(false, "Empty webhook body");
        }

        $decoded = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return $this->createResult(false, "Invalid JSON webhook body: " . json_last_error_msg());
        }

        // Encrypted payloads must be handled BEFORE version dispatch: when encrypted, `version`
        // lives inside the ciphertext, so the outer body carries only `encrypted_response`.
        // Successful AES-GCM decryption authenticates it — no signature check.
        $encResult = $this->handleEncryptedPayload($decoded, $secret, 'Webhook');
        if ($encResult !== null) {
            return $encResult;
        }

        $version = $this->tryGetString($decoded, JsonKeys::VERSION);

        // Version-based dispatch — `version` is the sole source of truth, and a payload of a given
        // version ALWAYS carries that `version` key (there is no version-less v4).
        //   - v4                -> signed-envelope handling
        //   - absent / v1/v2/v3 -> legacy per-field handling (the default below)
        // FUTURE: if a new version (e.g. v5) ships with a different payload/envelope, add a branch
        // here routing to its OWN handler — do not overload verifyV4Envelope():
        //   } elseif ($version === SdkConstants::WEBHOOK_CALLBACK_VERSION_V5) {
        //       return $this->verifyV5Webhook($decoded, $secret);
        //   }
        if ($version === SdkConstants::WEBHOOK_CALLBACK_VERSION_V4) {
            $logger->info("verifyWebhook: v4 payload detected, using envelope handling");
            return $this->verifyV4Envelope($decoded, $secret, JsonKeys::SIGNATURE, true, 'Webhook');
        }

        // Legacy path (v1/v2/v3 or no version) — behaviour unchanged.
        $logger->info("verifyWebhook: legacy payload (version=" . ($version ?? 'none') . "), using legacy handling");
        $payload = PayloadHelperUtils::parseResponse($rawBody, $secret);
        $result = $this->verifySignature($payload, $secret);
        return $this->decorateResult($result, $version ?? 'legacy', $payload);
    }

    /**
     * Version-aware callback verification entry point (server payment callback and
     * client checkout callback forwarded to your server).
     *
     * Uses `version` as the source of truth. For v4 callbacks the envelope HMAC is the
     * ONLY signature (v4 callbacks carry no per-field transaction.signature). Encrypted
     * v4 callbacks carry no signature at all — a successful decryption authenticates them.
     *
     * @param string $rawBody Raw callback body (Base64-encoded response string or raw JSON)
     * @param string|null $secret access_secret
     * @return array
     */
    public function verifyCallback($rawBody, $secret = null)
    {
        $logger = Logger::getInstance();

        if (!is_string($rawBody) || $rawBody === '') {
            return $this->createResult(false, "Empty callback body");
        }

        // Redirect callbacks arrive Base64-encoded; POST/popup callbacks arrive as raw JSON.
        $jsonStr = $rawBody;
        $maybe = base64_decode($rawBody, true);
        if ($maybe !== false && $this->looksLikeJson($maybe)) {
            $jsonStr = $maybe;
        }

        $decoded = json_decode($jsonStr, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return $this->createResult(false, "Invalid JSON callback body: " . json_last_error_msg());
        }

        // Unwrap the outer checkout envelope (globalCloseCheckoutModal / globalHandleCheckoutResponse):
        // the signed object is the nested `payload`.
        $eventType = $this->tryGetString($decoded, JsonKeys::EVENT_TYPE);
        $signed = $decoded;
        $isCheckoutWrapper = in_array($eventType, [JsonKeys::GLOBAL_CLOSE_CHECKOUT_MODAL, JsonKeys::GLOBAL_HANDLE_CHECKOUT_RESPONSE], true);
        if ($isCheckoutWrapper && isset($decoded[JsonKeys::PAYLOAD]) && is_array($decoded[JsonKeys::PAYLOAD])) {
            $signed = $decoded[JsonKeys::PAYLOAD];
        }

        // v4 callbacks (both checkout and payment) sign the envelope under `nimbbl_signature`
        // (confirmed against live callbacks + the backend `_sign_v4_payload`). Webhooks use `signature`.

        // Encrypted callbacks must be handled BEFORE version dispatch: `version` is inside the
        // ciphertext, so the outer body carries only `encrypted_response`. Successful AES-GCM
        // decryption authenticates it — no signature check.
        $encResult = $this->handleEncryptedPayload($signed, $secret, 'Callback');
        if ($encResult !== null) {
            return $encResult;
        }

        $version = $this->tryGetString($signed, JsonKeys::VERSION);

        // Version-based dispatch — `version` is the sole source of truth (a v4 payload always
        // carries `version: v4`).
        //   - v4                -> signed-envelope handling (envelope HMAC only)
        //   - absent / v1/v2/v3 -> legacy per-field handling (the default below)
        // FUTURE: if a new version (e.g. v5) ships with a different payload/envelope, add a branch
        // here routing to its OWN handler — do not overload verifyV4Envelope():
        //   } elseif ($version === SdkConstants::WEBHOOK_CALLBACK_VERSION_V5) {
        //       return $this->verifyV5Callback($signed, $secret);
        //   }
        if ($version === SdkConstants::WEBHOOK_CALLBACK_VERSION_V4) {
            $logger->info("verifyCallback: v4 payload detected, using envelope handling");
            // v4 callbacks: envelope HMAC only, signed under `nimbbl_signature` (no inner per-field signature).
            return $this->verifyV4Envelope($signed, $secret, JsonKeys::NIMBBL_SIGNATURE, false, 'Callback');
        }

        // Legacy path (v1/v2/v3 or no version) — behaviour unchanged.
        $logger->info("verifyCallback: legacy payload (version=" . ($version ?? 'none') . "), using legacy handling");
        $payload = PayloadHelperUtils::parseResponse($rawBody, $secret);
        $result = $this->verifyCallbackSignature($payload, $secret);
        return $this->decorateResult($result, $version ?? 'legacy', $payload);
    }

    // --- Helper Methods ---

    /**
     * Verify a plaintext v4 signed envelope. Same algorithm for all sources; only the signature
     * field name differs, and it is taken STRICTLY from $sigKeyPrimary (no fallback):
     *  - Webhook v4:  { version, payload, signature, sub_merchant_id }        -> $sigKeyPrimary = signature
     *  - Callback v4: { version, payload, nimbbl_signature, sub_merchant_id } -> $sigKeyPrimary = nimbbl_signature
     * The HMAC is computed over the exact Base64-decoded `payload` (the inner compact JSON).
     *
     * @param array $envelope The signed envelope object
     * @param string|null $secret access_secret
     * @param string $sigKeyPrimary The signature key for this source (signature | nimbbl_signature) — no fallback
     * @param bool $runPerField When true, also validate the retained legacy per-field signature (webhook v4)
     * @return array
     */
    private function verifyV4Envelope(array $envelope, $secret, $sigKeyPrimary, $runPerField, $apiTag = 'Webhook')
    {
        $logger = Logger::getInstance();

        // Encryption is handled upstream in verifyWebhook()/verifyCallback() (decryption
        // authenticates). By the time we get here the envelope is always plaintext, so this
        // method only verifies the envelope HMAC over the exact Base64-decoded compact JSON.
        $b64 = $this->tryGetString($envelope, JsonKeys::PAYLOAD);
        if (empty($b64)) {
            return $this->createResult(false, "Missing v4 " . JsonKeys::PAYLOAD);
        }
        if (empty($b64)) {
            return $this->createResult(false, "Missing v4 " . JsonKeys::PAYLOAD);
        }

        // Use ONLY the signature key for this source (no fallback): webhooks carry `signature`,
        // callbacks carry `nimbbl_signature` — each alongside the top-level `version`.
        $signature = $this->tryGetString($envelope, $sigKeyPrimary);
        if (empty($signature)) {
            // Surface the envelope's keys so a signature-field mismatch is diagnosable from the log
            // (rather than guessed) — the compact `payload` base64 is not logged (it holds the event).
            $envKeys = implode(',', array_keys($envelope));
            return $this->createResult(false, "Missing v4 signature (" . $sigKeyPrimary . "); envelope keys: [" . $envKeys . "]");
        }

        $inner = base64_decode($b64, true);
        if ($inner === false) {
            return $this->createResult(false, "v4 payload is not valid base64");
        }

        if (!$this->verifyEnvelopeSignature($inner, $signature, $secret)) {
            $logger->error(ErrorMessages::MESSAGE_SIGNATURE_VERIFICATION_FAILED . " - v4 envelope signature mismatch");
            return $this->createResult(false, "v4 envelope signature mismatch");
        }

        $payload = json_decode($inner, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
            return $this->createResult(false, "v4 inner payload invalid JSON: " . json_last_error_msg());
        }

        // Webhook v4 retains the legacy per-field transaction.signature; cross-check when present.
        if ($runPerField) {
            $txn = isset($payload[JsonKeys::TRANSACTION]) && is_array($payload[JsonKeys::TRANSACTION])
                ? $payload[JsonKeys::TRANSACTION]
                : null;
            if (is_array($txn) && !empty($this->tryGetString($txn, JsonKeys::SIGNATURE))) {
                $perField = $this->verifySignature($payload, $secret);
                if (empty($perField['success'])) {
                    return $this->createResult(false, "v4 envelope verified but per-field signature failed: " . ($perField['message'] ?? ''));
                }
            }
        }

        $ctx = $this->eventLogContext($payload, $apiTag, $this->tryGetString($envelope, JsonKeys::SUB_MERCHANT_ID));
        $logger->info("v4 envelope signature verified", null, $ctx);
        return $this->decorateResult(
            $this->createResult(true, "v4 envelope signature verified"),
            SdkConstants::WEBHOOK_CALLBACK_VERSION_V4,
            $payload
        );
    }

    /**
     * Handle an encrypted webhook/callback payload.
     *
     * A top-level `encrypted_response` means the whole (v4 signed) envelope was AES-GCM
     * encrypted. Successful decryption IS the authentication: the 128-bit GCM tag guarantees
     * integrity + authenticity (only the access_secret holder can produce a valid ciphertext),
     * so NO separate HMAC/signature check is performed on the decrypted content — an inner HMAC
     * check would be redundant, and a `signature` field may or may not be present.
     *
     * The decrypted content is normally the signed envelope { payload: base64(event),
     * signature?, version } — we unwrap the Base64 `payload` to the inner event. If decryption
     * yields the event directly (no `payload`), it is used as-is.
     *
     * @param array $container Parsed body that may carry `encrypted_response`
     * @param string|null $secret access_secret
     * @param string $apiTag Log tag: Webhook / Callback
     * @return array|null Verification result, or null when $container is not encrypted
     */
    private function handleEncryptedPayload(array $container, $secret, $apiTag = 'Webhook')
    {
        $encrypted = $this->tryGetString($container, JsonKeys::ENCRYPTED_RESPONSE);
        if (empty($encrypted)) {
            return null; // not encrypted — caller continues with version-based dispatch
        }

        $logger = Logger::getInstance();

        // Log the RECEIVED encrypted body up-front: the ciphertext is opaque (no PII), safe to
        // log in full, and useful for debugging even if decryption fails below.
        $logger->info(
            "Encrypted payload received: " . json_encode($container, JSON_UNESCAPED_SLASHES),
            null,
            ['apiTag' => $apiTag, 'subMerchantId' => $this->tryGetString($container, JsonKeys::SUB_MERCHANT_ID)]
        );

        try {
            $decrypted = (new Encryption($secret))->decrypt($encrypted, true);
        } catch (\Exception $ex) {
            $logger->error(ErrorMessages::MESSAGE_SIGNATURE_VERIFICATION_FAILED . " - encrypted payload decryption failed: " . $ex->getMessage());
            return $this->createResult(false, "encrypted payload decryption failed: " . $ex->getMessage());
        }
        if (!is_array($decrypted)) {
            return $this->createResult(false, "encrypted payload could not be decoded");
        }

        // Unwrap the signed envelope's Base64 `payload` to the inner event (if present).
        // The envelope `signature` is intentionally NOT verified — AES-GCM decryption already
        // authenticated the payload, so an inner HMAC check would be redundant.
        $version = $this->tryGetString($decrypted, JsonKeys::VERSION);
        $event = $decrypted;
        $b64 = $this->tryGetString($decrypted, JsonKeys::PAYLOAD);
        if (!empty($b64)) {
            $innerRaw = base64_decode($b64, true);
            if ($innerRaw !== false) {
                $inner = json_decode($innerRaw, true);
                if (is_array($inner)) {
                    $event = $inner;
                    if ($version === null) {
                        $version = $this->tryGetString($inner, JsonKeys::VERSION);
                    }
                }
            }
        }

        $ctx = $this->eventLogContext($event, $apiTag, $this->tryGetString($container, JsonKeys::SUB_MERCHANT_ID));

        // Log the DECRYPTED payload, PII-masked (never raw), so it is visible without DEBUG.
        $logger->info(
            "Decrypted payload (authenticated via decryption): "
            . CentralMasker::maskBody(json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            null,
            $ctx
        );
        return $this->decorateResult(
            $this->createResult(true, "encrypted payload authenticated via decryption"),
            $version ?? SdkConstants::WEBHOOK_CALLBACK_VERSION_V4,
            $event
        );
    }

    /**
     * Build a Logger context array from a verified/decrypted payload so webhook/callback
     * log lines are traceable (SubMerchantID / OrderID / InvoiceID / TransactionID / EventType).
     */
    private function eventLogContext(array $payload, $apiTag, $subMerchantId = null): array
    {
        $order = (isset($payload[JsonKeys::ORDER]) && is_array($payload[JsonKeys::ORDER])) ? $payload[JsonKeys::ORDER] : [];
        $txn = (isset($payload[JsonKeys::TRANSACTION]) && is_array($payload[JsonKeys::TRANSACTION])) ? $payload[JsonKeys::TRANSACTION] : [];
        return [
            'apiTag' => $apiTag,
            'subMerchantId' => $subMerchantId ?? $this->tryGetString($payload, JsonKeys::SUB_MERCHANT_ID),
            'orderId' => $this->tryGetString($payload, JsonKeys::NIMBBL_ORDER_ID) ?? $this->tryGetString($order, JsonKeys::ORDER_ID),
            // Minimal v4 callbacks carry invoice_id / nimbbl_transaction_id at the TOP level;
            // full webhook payloads carry them under order / transaction.
            'invoiceId' => $this->tryGetString($payload, JsonKeys::INVOICE_ID) ?? $this->tryGetString($order, JsonKeys::INVOICE_ID),
            'transactionId' => $this->tryGetString($payload, JsonKeys::NIMBBL_TRANSACTION_ID) ?? $this->tryGetString($txn, JsonKeys::TRANSACTION_ID),
            'eventType' => $this->tryGetString($payload, JsonKeys::EVENT_TYPE),
        ];
    }

    /**
     * Attach version / event_type / payload to a base result array.
     */
    private function decorateResult(array $result, $version, $payload)
    {
        $result['version'] = $version;
        $result['event_type'] = is_array($payload) ? $this->tryGetString($payload, JsonKeys::EVENT_TYPE) : null;
        $result['payload'] = $payload;
        return $result;
    }

    /**
     * Cheap check whether a decoded string looks like a JSON object/array.
     */
    private function looksLikeJson($str)
    {
        if (!is_string($str)) {
            return false;
        }
        $t = ltrim($str);
        return isset($t[0]) && ($t[0] === '{' || $t[0] === '[');
    }

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
        return in_array(strtolower((string) $eventType), WebhookEvents::PAYMENT_LINK_EVENTS, true);
    }

    private function isRefundEvent($eventType)
    {
        return in_array(strtolower((string) $eventType), WebhookEvents::REFUND_EVENTS, true);
    }
}
