<?php

declare(strict_types=1);

namespace Nimbbl\Tests\Common;
use Nimbbl\Api\Common\SignatureVerifier;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\Encryption;
use PHPUnit\Framework\TestCase;

final class SignatureVerifierTest extends TestCase
{
    private $signatureVerifier;
    private $secret;

    protected function setUp(): void
    {
        // Offline unit tests: SignatureVerifier is stateless and doesn't need live credentials.
        $this->signatureVerifier = new SignatureVerifier();
        $this->secret = 'test_secret_key_12345';
    }

    /**
     * Test payment signature verification
     */
    public function testVerifyPaymentSignature(): void
    {
        $invoiceId = 'inv_123';
        $transactionId = 'txn_456';
        $amount = 100.50;
        $currency = 'INR';
        $status = 'success';
        $type = 'payment';

        // Construct payload string for v3 signature
        $amountStr = number_format($amount, 2, '.', '');
        $payloadStr = "{$invoiceId}|{$transactionId}|{$amountStr}|{$currency}|{$status}|{$type}";
        $signature = hash_hmac('sha256', $payloadStr, $this->secret);

        $attributes = [
            JsonKeys::ORDER => [
                JsonKeys::INVOICE_ID => $invoiceId
            ],
            JsonKeys::TRANSACTION => [
                JsonKeys::TRANSACTION_ID => $transactionId,
                JsonKeys::TRANSACTION_AMOUNT => $amount,
                JsonKeys::TRANSACTION_CURRENCY => $currency,
                JsonKeys::STATUS => $status,
                JsonKeys::TRANSACTION_TYPE => $type,
                JsonKeys::SIGNATURE_VERSION => SdkConstants::SIGNATURE_VERSION_V3,
                JsonKeys::SIGNATURE => $signature
            ],
            JsonKeys::EVENT_TYPE => 'payment_success'
        ];

        $result = $this->signatureVerifier->verifySignature($attributes, $this->secret);

        $this->assertTrue($result['success'], 'Payment signature verification should succeed: ' . ($result['message'] ?? ''));
    }

    /**
     * Test refund signature verification
     */
    public function testVerifyRefundSignature(): void
    {
        $invoiceId = 'inv_123';
        $transactionId = 'txn_ref_789';
        $amount = 50.00;
        $currency = 'INR';
        $status = 'success';
        $type = 'refund';

        // Construct payload string for v3 signature (refund)
        $amountStr = number_format($amount, 2, '.', '');
        $payloadStr = "{$invoiceId}|{$transactionId}|{$amountStr}|{$currency}|{$status}|{$type}";
        $signature = hash_hmac('sha256', $payloadStr, $this->secret);

        $attributes = [
            JsonKeys::ORDER => [
                JsonKeys::INVOICE_ID => $invoiceId,
                JsonKeys::REFUND_DETAILS => [
                    JsonKeys::REFUNDABLE_CURRENCY => $currency,
                ],
            ],
            JsonKeys::TRANSACTION => [
                JsonKeys::TRANSACTION_ID => $transactionId,
                JsonKeys::REFUND_AMOUNT => $amount,
                JsonKeys::TRANSACTION_CURRENCY => $currency,
                JsonKeys::REFUND_STATUS => $status,
                JsonKeys::TRANSACTION_TYPE => $type,
                JsonKeys::SIGNATURE_VERSION => SdkConstants::SIGNATURE_VERSION_V3,
                JsonKeys::SIGNATURE => $signature
            ],
            JsonKeys::EVENT_TYPE => 'refund_success'
        ];

        $result = $this->signatureVerifier->verifySignature($attributes, $this->secret);

        $this->assertTrue($result['success'], 'Refund signature verification should succeed: ' . ($result['message'] ?? ''));
    }

    /**
     * Test payment link signature verification
     */
    public function testVerifyPaymentLinkSignature(): void
    {
        $invoiceId = 'inv_link_001';
        $amountPaid = 500.00;
        $currency = 'INR';
        $status = 'paid';
        $plHash = 'hash_xyz';

        // Construct payload string for v3 signature (payment link)
        $amountStr = number_format($amountPaid, 2, '.', '');
        $payloadStr = "{$invoiceId}|{$status}|{$currency}|{$amountStr}|{$plHash}";
        $signature = hash_hmac('sha256', $payloadStr, $this->secret);

        $attributes = [
            JsonKeys::INVOICE_ID => $invoiceId,
            JsonKeys::STATUS => $status,
            JsonKeys::CURRENCY => $currency,
            JsonKeys::AMOUNT_PAID => $amountPaid,
            JsonKeys::PAYMENT_LINK_HASH => $plHash,
            JsonKeys::SIGNATURE_VERSION => SdkConstants::SIGNATURE_VERSION_V3,
            JsonKeys::NIMBBL_SIGNATURE => $signature,
            JsonKeys::EVENT_TYPE => 'payment_link_paid'
        ];

        $result = $this->signatureVerifier->verifySignature($attributes, $this->secret);

        $this->assertTrue($result['success'], 'Payment Link signature verification should succeed: ' . ($result['message'] ?? ''));
    }

    /**
     * Test verify and parse webhook (integration)
     */
    public function testVerifyAndParseWebhook(): void
    {
        $invoiceId = 'inv_parse_test';
        $transactionId = 'txn_parse_test';
        $amount = 100.00;
        $currency = 'INR';
        $status = 'success';
        $type = 'payment';

        $amountStr = number_format($amount, 2, '.', '');
        $payloadStr = "{$invoiceId}|{$transactionId}|{$amountStr}|{$currency}|{$status}|{$type}";
        $signature = hash_hmac('sha256', $payloadStr, $this->secret);

        $data = [
            JsonKeys::ORDER => [
                JsonKeys::INVOICE_ID => $invoiceId
            ],
            JsonKeys::TRANSACTION => [
                JsonKeys::TRANSACTION_ID => $transactionId,
                JsonKeys::TRANSACTION_AMOUNT => $amount,
                JsonKeys::TRANSACTION_CURRENCY => $currency,
                JsonKeys::STATUS => $status,
                JsonKeys::TRANSACTION_TYPE => $type,
                JsonKeys::SIGNATURE_VERSION => SdkConstants::SIGNATURE_VERSION_V3,
                JsonKeys::SIGNATURE => $signature
            ],
            JsonKeys::EVENT_TYPE => 'payment_success'
        ];
        
        $result = $this->signatureVerifier->verifySignature($data, $this->secret);

        $this->assertTrue($result['success'], 'Verify and Parse should succeed');
    }

    /**
     * Test failure on missing parameters
     */
    public function testVerifyFailureMissingParams(): void
    {
        $attributes = [
            JsonKeys::EVENT_TYPE => 'payment_success',
            JsonKeys::TRANSACTION => []
        ];

        $result = $this->signatureVerifier->verifySignature($attributes, $this->secret);

        $this->assertFalse($result['success'], 'Should fail when params missing');
        $this->assertStringContainsString('Missing', $result['message']);
    }

    /**
     * Test failure on invalid signature
     */
    public function testVerifyFailureInvalidSignature(): void
    {
        $invoiceId = 'inv_123';
        $transactionId = 'txn_456';

        $attributes = [
            JsonKeys::ORDER => [
                JsonKeys::INVOICE_ID => $invoiceId
            ],
            JsonKeys::TRANSACTION => [
                JsonKeys::TRANSACTION_ID => $transactionId,
                JsonKeys::TRANSACTION_AMOUNT => 100,
                JsonKeys::TRANSACTION_CURRENCY => 'INR',
                JsonKeys::STATUS => 'success',
                JsonKeys::TRANSACTION_TYPE => 'payment',
                JsonKeys::SIGNATURE_VERSION => SdkConstants::SIGNATURE_VERSION_V3,
                JsonKeys::SIGNATURE => 'invalid_signature_hash'
            ],
            JsonKeys::EVENT_TYPE => 'payment_success'
        ];

        $result = $this->signatureVerifier->verifySignature($attributes, $this->secret);

        $this->assertFalse($result['success'], 'Should fail on invalid signature');
    }

    // ---------------------------------------------------------------------
    // Pre-auth: per-field (v3) signature for capture / void / authorized txns
    // ---------------------------------------------------------------------

    /**
     * Build a legacy per-field payment payload with a valid transaction.signature.
     */
    private function makePerFieldPayload(string $status, string $type, string $invoiceId = 'inv_pa', string $txnId = 'txn_pa', float $amount = 250.00): array
    {
        $amountStr = number_format($amount, 2, '.', '');
        $payloadStr = "{$invoiceId}|{$txnId}|{$amountStr}|INR|{$status}|{$type}";
        $signature = hash_hmac('sha256', $payloadStr, $this->secret);

        return [
            JsonKeys::ORDER => [JsonKeys::INVOICE_ID => $invoiceId],
            JsonKeys::TRANSACTION => [
                JsonKeys::TRANSACTION_ID => $txnId,
                JsonKeys::TRANSACTION_AMOUNT => $amount,
                JsonKeys::TRANSACTION_CURRENCY => 'INR',
                JsonKeys::STATUS => $status,
                JsonKeys::TRANSACTION_TYPE => $type,
                JsonKeys::SIGNATURE_VERSION => SdkConstants::SIGNATURE_VERSION_V3,
                JsonKeys::SIGNATURE => $signature,
            ],
        ];
    }

    public function testVerifyPerFieldSignatureForCaptureTransaction(): void
    {
        $data = $this->makePerFieldPayload('succeeded', 'capture');
        $data[JsonKeys::EVENT_TYPE] = 'capture_success';
        $result = $this->signatureVerifier->verifySignature($data, $this->secret);
        $this->assertTrue($result['success'], 'Capture per-field signature should verify: ' . ($result['message'] ?? ''));
    }

    public function testVerifyPerFieldSignatureForVoidTransaction(): void
    {
        $data = $this->makePerFieldPayload('succeeded', 'void');
        $data[JsonKeys::EVENT_TYPE] = 'void_success';
        $result = $this->signatureVerifier->verifySignature($data, $this->secret);
        $this->assertTrue($result['success'], 'Void per-field signature should verify: ' . ($result['message'] ?? ''));
    }

    public function testVerifyPerFieldSignatureForAuthorizedTransaction(): void
    {
        $data = $this->makePerFieldPayload('authorized', 'payment');
        $data[JsonKeys::EVENT_TYPE] = 'payment_authorized';
        $result = $this->signatureVerifier->verifySignature($data, $this->secret);
        $this->assertTrue($result['success'], 'Authorized per-field signature should verify: ' . ($result['message'] ?? ''));
    }

    // ---------------------------------------------------------------------
    // v4 envelope helpers + tests
    // ---------------------------------------------------------------------

    private function makeV4Envelope(array $innerPayload, string $sigField, string $secret): array
    {
        $innerJson = json_encode($innerPayload);
        return [
            JsonKeys::VERSION => SdkConstants::WEBHOOK_CALLBACK_VERSION_V4,
            JsonKeys::PAYLOAD => base64_encode($innerJson),
            $sigField => hash_hmac('sha256', $innerJson, $secret),
        ];
    }

    /**
     * Build the wire body for an ENCRYPTED v4 webhook/callback, matching the backend:
     * the whole signed v4 envelope { payload: base64(event), signature?, version } is
     * AES-GCM-encrypted, and the outer body carries only { encrypted_response, sub_merchant_id }
     * — no top-level `version`. `$sigField=null` reproduces the "signature might not come" case.
     */
    private function makeEncryptedV4Body(array $event, string $secret, ?string $sigField = JsonKeys::SIGNATURE): string
    {
        $innerJson = json_encode($event);
        $envelope = [
            JsonKeys::PAYLOAD => base64_encode($innerJson),
            JsonKeys::VERSION => SdkConstants::WEBHOOK_CALLBACK_VERSION_V4,
        ];
        if ($sigField !== null) {
            // Present but intentionally ignored by the SDK — GCM decryption authenticates.
            $envelope[$sigField] = hash_hmac('sha256', $innerJson, $secret);
        }
        $hex = (new Encryption($secret))->encrypt($envelope);
        return json_encode([JsonKeys::ENCRYPTED_RESPONSE => $hex, 'sub_merchant_id' => 'sm_1']);
    }

    // ---- Encrypted v4 webhook/callback (decryption authenticates, no HMAC) ----

    public function testVerifyWebhookEncryptedV4(): void
    {
        $event = [
            JsonKeys::EVENT_TYPE => 'payment_authorized',
            JsonKeys::STATUS => 'authorized',
            JsonKeys::TRANSACTION => [JsonKeys::TRANSACTION_ID => 't_enc', JsonKeys::STATUS => 'authorized'],
        ];
        $body = $this->makeEncryptedV4Body($event, $this->secret, JsonKeys::SIGNATURE);

        $res = $this->signatureVerifier->verifyWebhook($body, $this->secret);
        $this->assertTrue($res['success'], $res['message'] ?? '');
        $this->assertSame('v4', $res['version']);
        $this->assertSame('payment_authorized', $res['event_type']);
        $this->assertSame('authorized', $res['payload']['status'] ?? null);
    }

    public function testVerifyCallbackEncryptedV4(): void
    {
        $event = [JsonKeys::EVENT_TYPE => 'payment_success', JsonKeys::STATUS => 'succeeded'];
        $body = $this->makeEncryptedV4Body($event, $this->secret, JsonKeys::NIMBBL_SIGNATURE);

        $res = $this->signatureVerifier->verifyCallback($body, $this->secret);
        $this->assertTrue($res['success'], $res['message'] ?? '');
        $this->assertSame('v4', $res['version']);
        $this->assertSame('payment_success', $res['event_type']);
    }

    public function testVerifyWebhookEncryptedV4WithoutSignatureField(): void
    {
        // "signature might not come" — successful decryption alone authenticates.
        $event = [JsonKeys::EVENT_TYPE => 'capture_success', JsonKeys::STATUS => 'succeeded'];
        $body = $this->makeEncryptedV4Body($event, $this->secret, null);

        $res = $this->signatureVerifier->verifyWebhook($body, $this->secret);
        $this->assertTrue($res['success'], $res['message'] ?? '');
        $this->assertSame('capture_success', $res['event_type']);
    }

    public function testVerifyWebhookEncryptedV4EventDirect(): void
    {
        // Defensive: decryption yields the event directly (no base64 `payload` wrapper).
        $event = [JsonKeys::EVENT_TYPE => 'void_success', JsonKeys::STATUS => 'succeeded', JsonKeys::VERSION => 'v4'];
        $hex = (new Encryption($this->secret))->encrypt($event);
        $body = json_encode([JsonKeys::ENCRYPTED_RESPONSE => $hex]);

        $res = $this->signatureVerifier->verifyWebhook($body, $this->secret);
        $this->assertTrue($res['success'], $res['message'] ?? '');
        $this->assertSame('void_success', $res['event_type']);
    }

    public function testVerifyWebhookEncryptedTamperedFails(): void
    {
        $event = [JsonKeys::EVENT_TYPE => 'payment_success'];
        $b = json_decode($this->makeEncryptedV4Body($event, $this->secret), true);
        // Corrupt the last ciphertext/tag byte — AES-GCM auth must fail => not authenticated.
        $hex = $b[JsonKeys::ENCRYPTED_RESPONSE];
        $b[JsonKeys::ENCRYPTED_RESPONSE] = substr($hex, 0, -2) . (substr($hex, -2) === 'ff' ? '00' : 'ff');

        $res = $this->signatureVerifier->verifyWebhook(json_encode($b), $this->secret);
        $this->assertFalse($res['success'], 'Tampered ciphertext must fail (GCM auth).');
    }

    public function testVerifyWebhookEncryptedWrongSecretFails(): void
    {
        $event = [JsonKeys::EVENT_TYPE => 'payment_success'];
        $body = $this->makeEncryptedV4Body($event, $this->secret);

        $res = $this->signatureVerifier->verifyWebhook($body, 'a_different_secret_value');
        $this->assertFalse($res['success'], 'Wrong secret must fail decryption.');
    }

    public function testVerifyWebhookV4Valid(): void
    {
        $inner = [
            JsonKeys::EVENT_TYPE => 'capture_success',
            JsonKeys::ORDER => [JsonKeys::INVOICE_ID => 'inv_v4'],
            JsonKeys::TRANSACTION => [JsonKeys::TRANSACTION_ID => 't_v4', JsonKeys::TRANSACTION_TYPE => 'capture', JsonKeys::STATUS => 'succeeded'],
        ];
        $env = $this->makeV4Envelope($inner, JsonKeys::SIGNATURE, $this->secret);
        $env[JsonKeys::SUB_MERCHANT_ID] = 'sm_1';

        $result = $this->signatureVerifier->verifyWebhook(json_encode($env), $this->secret);
        $this->assertTrue($result['success'], 'v4 webhook should verify: ' . ($result['message'] ?? ''));
        $this->assertSame(SdkConstants::WEBHOOK_CALLBACK_VERSION_V4, $result['version']);
        $this->assertSame('capture_success', $result['event_type']);
    }

    public function testVerifyWebhookV4TamperedFails(): void
    {
        $inner = [JsonKeys::EVENT_TYPE => 'void_success', JsonKeys::ORDER => [], JsonKeys::TRANSACTION => []];
        $env = $this->makeV4Envelope($inner, JsonKeys::SIGNATURE, $this->secret);
        $env[JsonKeys::SIGNATURE] = 'tampered_signature';

        $result = $this->signatureVerifier->verifyWebhook(json_encode($env), $this->secret);
        $this->assertFalse($result['success'], 'Tampered v4 webhook should fail');
    }

    public function testVerifyWebhookV4WrongSecretFails(): void
    {
        $inner = [JsonKeys::EVENT_TYPE => 'capture_success', JsonKeys::ORDER => [], JsonKeys::TRANSACTION => []];
        $env = $this->makeV4Envelope($inner, JsonKeys::SIGNATURE, $this->secret);

        $result = $this->signatureVerifier->verifyWebhook(json_encode($env), 'a_different_secret');
        $this->assertFalse($result['success'], 'v4 webhook with wrong secret should fail');
    }

    public function testVerifyWebhookLegacyNoVersionStillWorks(): void
    {
        // Regression: a payload with no `version` must flow through the legacy path.
        $data = $this->makePerFieldPayload('succeeded', 'payment', 'inv_legacy', 'txn_legacy', 100.00);
        $data[JsonKeys::EVENT_TYPE] = 'payment_success';

        $result = $this->signatureVerifier->verifyWebhook(json_encode($data), $this->secret);
        $this->assertTrue($result['success'], 'Legacy (no version) webhook should verify: ' . ($result['message'] ?? ''));
        $this->assertSame('legacy', $result['version']);
    }

    public function testVerifyCallbackV4PaymentCallback(): void
    {
        // Payment callback carries the signature in `nimbbl_signature` (no sub_merchant_id).
        $inner = [
            JsonKeys::ORDER => [JsonKeys::INVOICE_ID => 'inv_pc'],
            JsonKeys::TRANSACTION => [JsonKeys::TRANSACTION_ID => 't_pc', JsonKeys::STATUS => 'authorized', JsonKeys::TRANSACTION_TYPE => 'payment'],
        ];
        $env = $this->makeV4Envelope($inner, JsonKeys::NIMBBL_SIGNATURE, $this->secret);

        $result = $this->signatureVerifier->verifyCallback(json_encode($env), $this->secret);
        $this->assertTrue($result['success'], 'v4 payment callback should verify: ' . ($result['message'] ?? ''));
        $this->assertSame(SdkConstants::WEBHOOK_CALLBACK_VERSION_V4, $result['version']);
    }

    public function testVerifyCallbackV4CheckoutCallback(): void
    {
        // Checkout callback: v4 envelope signed under `nimbbl_signature` (confirmed against live
        // callbacks + backend _sign_v4_payload), nested under globalCloseCheckoutModal.
        $inner = [JsonKeys::CHECKOUT_STATUS => 'success', JsonKeys::REASON => 'payment_authorized', JsonKeys::ORDER_ID => 'o_cc'];
        $signed = $this->makeV4Envelope($inner, JsonKeys::NIMBBL_SIGNATURE, $this->secret);
        $signed[JsonKeys::SUB_MERCHANT_ID] = 'sm_1';
        $outer = [JsonKeys::EVENT_TYPE => JsonKeys::GLOBAL_CLOSE_CHECKOUT_MODAL, JsonKeys::PAYLOAD => $signed];

        $result = $this->signatureVerifier->verifyCallback(json_encode($outer), $this->secret);
        $this->assertTrue($result['success'], 'v4 checkout callback should verify: ' . ($result['message'] ?? ''));
        $this->assertSame('payment_authorized', $result['payload'][JsonKeys::REASON] ?? null);
    }

    public function testVerifyCallbackV4CheckoutTamperedFails(): void
    {
        $inner = [JsonKeys::CHECKOUT_STATUS => 'success', JsonKeys::REASON => 'payment_captured'];
        $signed = $this->makeV4Envelope($inner, JsonKeys::NIMBBL_SIGNATURE, $this->secret);
        $signed[JsonKeys::NIMBBL_SIGNATURE] = 'nope';
        $outer = [JsonKeys::EVENT_TYPE => JsonKeys::GLOBAL_CLOSE_CHECKOUT_MODAL, JsonKeys::PAYLOAD => $signed];

        $result = $this->signatureVerifier->verifyCallback(json_encode($outer), $this->secret);
        $this->assertFalse($result['success'], 'Tampered v4 checkout callback should fail');
    }
}
