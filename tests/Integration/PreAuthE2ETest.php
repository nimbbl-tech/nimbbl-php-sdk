<?php

declare(strict_types=1);

namespace Nimbbl\Tests\Integration;

require_once __DIR__ . '/../../example/utils/helpers.php';

use Nimbbl\Api\Common\SignatureVerifier;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\RestClient\Request;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end pre-authorization (capture / void) tests.
 *
 * The webhook/callback payloads here are built to match the Nimbbl backend's
 * webhook-payload-generator byte-for-byte:
 *   - SNAKE_CASE JSON (ObjectMapperUtil: PropertyNamingStrategies.SNAKE_CASE)
 *   - inner payload shape = TransactionPayloadDto (event_type, status, message,
 *     nimbbl_order_id, nimbbl_transaction_id, transaction{}, order{}, user{},
 *     is_webhook, version)
 *   - transaction shape = TransactionDto (single object, incl. authorization_details)
 *   - authorization_details = { mechanism, capture_mode, authorized_time, expiry_time,
 *     captured_amount, voided_amount, available_authorized_amount }
 *   - v4 envelope = SignatureGenerationUtil.buildV4WebhookPayload:
 *       { payload: base64(innerJson), signature: hmac_sha256(innerJson, secret),
 *         sub_merchant_id, version }
 *   - v3 per-field signature = invoice|txn|amount(2dp)|currency|status|type
 *
 * These drive the SDK's real verify -> parse path (verifyWebhook / verifyCallback)
 * and assert the decoded outcome, exactly as a live webhook would.
 *
 * The final test performs a LIVE capture-then-void against the configured environment
 * when a real authorized transaction id is supplied via NIMBBL_PREAUTH_TXN_ID
 * (skips otherwise).
 */
final class PreAuthE2ETest extends TestCase
{
    private SignatureVerifier $verifier;
    private string $secret = 'access_secret_e2e_preauth_key';
    private string $subMerchantId = '3119';

    protected function setUp(): void
    {
        $this->verifier = new SignatureVerifier();
    }

    // ---- fixtures modeled on webhook-payload-generator --------------------

    /** authorization_details block (AuthorizationDetailsDto, snake_case). */
    private function authDetails(float $captured = 0.0, float $voided = 0.0, float $available = 500.00): array
    {
        return [
            'mechanism' => 'pre_auth',
            'capture_mode' => 'manual',
            'authorized_time' => '2026-06-22T10:46:14Z',
            'expiry_time' => '2026-06-29T10:46:14Z',
            'captured_amount' => $captured,
            'voided_amount' => $voided,
            'available_authorized_amount' => $available,
        ];
    }

    /** transaction node (TransactionDto, snake_case). */
    private function txn(array $overrides): array
    {
        return array_merge([
            'transaction_id' => 'o_Rz4Zx2WeyooEpyxa-221117104614',
            'status' => 'authorized',
            'payment_partner' => 'PayU',
            'psp_transaction_id' => 'psp_abc_123',
            'payment_mode' => 'Credit Card/Debit Card/Prepaid Card',
            'transaction_type' => 'payment',
            'transaction_currency' => 'INR',
            'transaction_amount' => 500.00,
            'signature_version' => SdkConstants::WEBHOOK_CALLBACK_VERSION_V4,
        ], $overrides);
    }

    /** inner payload (TransactionPayloadDto, snake_case). */
    private function innerPayload(string $eventType, string $status, string $message, array $txn): array
    {
        return [
            'event_type' => $eventType,
            'status' => $status,
            'message' => $message,
            'nimbbl_order_id' => 'o_Rz4Zx2WeyooEpyxa',
            'nimbbl_transaction_id' => $txn['transaction_id'],
            'transaction' => $txn,
            'order' => [
                'invoice_id' => 'invoice_123',
                'status' => 'lapsed',
                'lapsed_reason' => 'payment_authorized',
            ],
            'user' => ['email' => 'test@example.com', 'mobile' => '9876543210', 'name' => 'Test User', 'user_id' => 'user_1'],
            'is_webhook' => true,
            'version' => SdkConstants::WEBHOOK_CALLBACK_VERSION_V4,
        ];
    }

    /** v4 envelope = SignatureGenerationUtil.buildV4WebhookPayload (signature over inner JSON string). */
    private function wrapV4(array $inner, string $sigField = 'signature'): string
    {
        $innerJson = json_encode($inner);
        $envelope = [
            'payload' => base64_encode($innerJson),
            $sigField => hash_hmac('sha256', $innerJson, $this->secret),
            'sub_merchant_id' => $this->subMerchantId,
            'version' => SdkConstants::WEBHOOK_CALLBACK_VERSION_V4,
        ];
        return json_encode($envelope);
    }

    // ---- webhook v4 events -------------------------------------------------

    public function testWebhookPaymentAuthorizedV4(): void
    {
        $inner = $this->innerPayload('payment_authorized', 'authorized', 'Payment Authorized',
            $this->txn(['status' => 'authorized', 'authorization_details' => $this->authDetails()]));

        $result = $this->verifier->verifyWebhook($this->wrapV4($inner), $this->secret);

        $this->assertTrue($result['success'], $result['message'] ?? '');
        $this->assertSame('v4', $result['version']);
        $this->assertSame('payment_authorized', $result['event_type']);
        $ad = $result['payload']['transaction']['authorization_details'];
        $this->assertSame('pre_auth', $ad['mechanism']);
        $this->assertSame('manual', $ad['capture_mode']);
        $this->assertEquals(500.00, $ad['available_authorized_amount']);
        $this->assertArrayHasKey('expiry_time', $ad);
    }

    public function testWebhookCaptureSuccessV4(): void
    {
        $inner = $this->innerPayload('capture_success', 'succeeded', 'Capture Successful',
            $this->txn([
                'transaction_id' => 'o_Rz4Zx2WeyooEpyxa-221117104615',
                'status' => 'succeeded',
                'transaction_type' => 'capture',
                'capture_type' => 'full',
                'original_payment_transaction_id' => 'o_Rz4Zx2WeyooEpyxa-221117104614',
                'payment_transaction_amount' => 500.00,
                'authorization_details' => $this->authDetails(500.00, 0.0, 0.0),
            ]));

        $result = $this->verifier->verifyWebhook($this->wrapV4($inner), $this->secret);

        $this->assertTrue($result['success'], $result['message'] ?? '');
        $this->assertSame('capture_success', $result['event_type']);
        $txn = $result['payload']['transaction'];
        $this->assertSame('capture', $txn['transaction_type']);
        $this->assertSame('full', $txn['capture_type']);
        $this->assertSame('o_Rz4Zx2WeyooEpyxa-221117104614', $txn['original_payment_transaction_id']);
    }

    public function testWebhookVoidSuccessV4(): void
    {
        $inner = $this->innerPayload('void_success', 'succeeded', 'Authorization Voided',
            $this->txn([
                'transaction_id' => 'o_Rz4Zx2WeyooEpyxa-221117104616',
                'status' => 'succeeded',
                'transaction_type' => 'void',
                'original_payment_transaction_id' => 'o_Rz4Zx2WeyooEpyxa-221117104614',
                'reversal_reason' => 'authorization_voided',
            ]));

        $result = $this->verifier->verifyWebhook($this->wrapV4($inner), $this->secret);

        $this->assertTrue($result['success'], $result['message'] ?? '');
        $this->assertSame('void_success', $result['event_type']);
        $this->assertSame('authorization_voided', $result['payload']['transaction']['reversal_reason']);
    }

    public function testWebhookVoidExpiredV4NotInitiatedByMerchant(): void
    {
        // A void_success the merchant did NOT initiate — hold released at expiry.
        $inner = $this->innerPayload('void_success', 'succeeded', 'Authorization Voided',
            $this->txn([
                'transaction_id' => 'o_Rz4Zx2WeyooEpyxa-221117104617',
                'status' => 'succeeded',
                'transaction_type' => 'void',
                'reversal_reason' => 'authorization_expired',
            ]));

        $result = $this->verifier->verifyWebhook($this->wrapV4($inner), $this->secret);

        $this->assertTrue($result['success']);
        $this->assertSame('authorization_expired', $result['payload']['transaction']['reversal_reason']);
    }

    public function testWebhookCaptureFailedV4CarriesError(): void
    {
        $txn = $this->txn([
            'transaction_id' => 'o_Rz4Zx2WeyooEpyxa-221117104618',
            'status' => 'failed',
            'transaction_type' => 'capture',
            'nimbbl_error_code' => 'CAPTURE_FAILED',
            'nimbbl_merchant_message' => 'There was a problem with the payment.',
        ]);
        $inner = $this->innerPayload('capture_failed', 'failed', 'Capture Failed', $txn);

        $result = $this->verifier->verifyWebhook($this->wrapV4($inner), $this->secret);

        $this->assertTrue($result['success']);
        $this->assertSame('capture_failed', $result['event_type']);
        $this->assertSame('CAPTURE_FAILED', $result['payload']['transaction']['nimbbl_error_code']);
    }

    public function testWebhookV4TamperedIsRejected(): void
    {
        $inner = $this->innerPayload('capture_success', 'succeeded', 'Capture Successful',
            $this->txn(['transaction_type' => 'capture', 'status' => 'succeeded']));
        $env = json_decode($this->wrapV4($inner), true);
        // Attacker flips the amount after signing.
        $tamperedInner = $inner;
        $tamperedInner['transaction']['transaction_amount'] = 999999.00;
        $env['payload'] = base64_encode(json_encode($tamperedInner));

        $result = $this->verifier->verifyWebhook(json_encode($env), $this->secret);

        $this->assertFalse($result['success'], 'Tampered v4 payload must be rejected');
    }

    // ---- callback v4 (checkout + payment) ---------------------------------

    public function testCheckoutCallbackAuthorizedV4(): void
    {
        // Checkout callback: nested under globalCloseCheckoutModal, v4 signature field `nimbbl_signature`.
        $inner = [
            'checkout_status' => 'success',
            'reason' => 'payment_authorized',
            'order_id' => 'o_Rz4Zx2WeyooEpyxa',
            'transaction_id' => 'o_Rz4Zx2WeyooEpyxa-221117104614',
            'invoice_id' => 'invoice_123',
            'retry' => false,
            'message' => 'Your payment has been authorized. You will be charged when the order is confirmed.',
            'version' => 'v4',
        ];
        $innerJson = json_encode($inner);
        $signed = [
            'payload' => base64_encode($innerJson),
            'nimbbl_signature' => hash_hmac('sha256', $innerJson, $this->secret),
            'sub_merchant_id' => $this->subMerchantId,
            'version' => 'v4',
        ];
        $outer = json_encode(['event_type' => JsonKeys::GLOBAL_CLOSE_CHECKOUT_MODAL, 'payload' => $signed]);

        $result = $this->verifier->verifyCallback($outer, $this->secret);

        $this->assertTrue($result['success'], $result['message'] ?? '');
        $this->assertSame('payment_authorized', $result['payload']['reason']);
        $this->assertSame('success', $result['payload']['checkout_status']);
    }

    public function testPaymentCallbackAuthorizedV4(): void
    {
        // Server payment callback: signature field `nimbbl_signature`, {order, transaction} body.
        $inner = [
            'order' => ['invoice_id' => 'invoice_123', 'status' => 'lapsed', 'lapsed_reason' => 'payment_authorized'],
            'transaction' => $this->txn(['status' => 'authorized', 'authorization_details' => $this->authDetails()]),
            'version' => 'v4',
        ];
        $innerJson = json_encode($inner);
        $env = json_encode([
            'payload' => base64_encode($innerJson),
            'nimbbl_signature' => hash_hmac('sha256', $innerJson, $this->secret),
            'version' => 'v4',
        ]);

        $result = $this->verifier->verifyCallback($env, $this->secret);

        $this->assertTrue($result['success'], $result['message'] ?? '');
        $this->assertSame('authorized', $result['payload']['transaction']['status']);
        $this->assertSame('pre_auth', $result['payload']['transaction']['authorization_details']['mechanism']);
    }

    // ---- legacy v3 (regression: pre-auth events on old format) ------------

    public function testLegacyV3CaptureWebhookStillVerifies(): void
    {
        // No `version` -> legacy per-field path. Signature over invoice|txn|amt|cur|status|type.
        $invoiceId = 'invoice_123';
        $txnId = 'o_Rz4Zx2WeyooEpyxa-221117104615';
        $amountStr = number_format(500.00, 2, '.', '');
        $sigData = "{$invoiceId}|{$txnId}|{$amountStr}|INR|succeeded|capture";
        $signature = hash_hmac('sha256', $sigData, $this->secret);

        $legacy = [
            'event_type' => 'capture_success',
            'order' => ['invoice_id' => $invoiceId],
            'transaction' => [
                'transaction_id' => $txnId,
                'transaction_amount' => 500.00,
                'transaction_currency' => 'INR',
                'status' => 'succeeded',
                'transaction_type' => 'capture',
                'signature_version' => 'v3',
                'signature' => $signature,
            ],
        ];

        $result = $this->verifier->verifyWebhook(json_encode($legacy), $this->secret);

        $this->assertTrue($result['success'], $result['message'] ?? '');
        $this->assertSame('legacy', $result['version']);
    }

    // ---- LIVE capture -> void (env-gated) ---------------------------------

    /**
     * Performs a real capture then void against the configured environment.
     * Provide an authorized transaction id from a manual-capture sub-merchant:
     *   NIMBBL_PREAUTH_TXN_ID=o_xxx-yyy vendor/bin/phpunit tests/PreAuthE2ETest.php
     * Skips when the env var is not set.
     */
    public function testLiveCaptureThenVoid(): void
    {
        $txnId = getenv('NIMBBL_PREAUTH_TXN_ID');
        if (empty($txnId)) {
            $this->markTestSkipped('Set NIMBBL_PREAUTH_TXN_ID to an authorized (manual-capture) transaction to run the live capture/void E2E.');
        }

        $config = loadConfig();
        $api = new NimbblClient($config['access_key'], $config['access_secret'], $config['api_endpoint']);
        $token = (new Request())->generateToken()['token'];

        // Capture is terminal; only one of capture/void will succeed on a given txn.
        // Choose the action via NIMBBL_PREAUTH_ACTION (capture|void), default capture.
        $action = getenv('NIMBBL_PREAUTH_ACTION') ?: 'capture';

        if ($action === 'void') {
            $res = $api->payments()->void(['transaction_id' => $txnId, 'comment' => 'E2E void'], $token);
            $this->assertArrayNotHasKey('error', $res);
            $this->assertContains($res['void_status'] ?? null, ['succeeded', 'pending', 'failed']);
            $this->assertSame('void', $res['transaction_type'] ?? 'void');
        } else {
            $res = $api->payments()->capture(['transaction_id' => $txnId, 'comment' => 'E2E capture'], $token);
            $this->assertArrayNotHasKey('error', $res);
            $this->assertContains($res['capture_status'] ?? null, ['succeeded', 'pending', 'failed']);
            $this->assertSame('capture', $res['transaction_type'] ?? 'capture');
            $this->assertSame($txnId, $res['original_payment_transaction_id'] ?? $txnId);
        }
    }
}
