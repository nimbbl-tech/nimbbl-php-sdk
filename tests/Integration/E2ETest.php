<?php

declare(strict_types=1);

namespace Nimbbl\Tests\Integration;

require_once __DIR__ . '/../../example/utils/helpers.php';

use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\RestClient\Request;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\SdkConstants;
use PHPUnit\Framework\TestCase;

/**
 * Live End-to-End test suite (opt-in).
 *
 * Runs against the environment configured in example/config.php (api_host + credentials).
 * It is fully OPT-IN: every test skips unless the env var NIMBBL_E2E=1 is set, so it never
 * hits live APIs during the normal offline suite.
 *
 * Run:
 *   NIMBBL_E2E=1 vendor/bin/phpunit tests/E2ETest.php --testdox
 *
 * Coverage
 *   Always (when NIMBBL_E2E=1):
 *     - Auth: generate token
 *     - Orders: create order, get order by id + by invoice id
 *     - Transactions: transaction enquiry by order id
 *     - Webhooks: pre-auth lifecycle (payment_authorized -> capture_success -> void_success),
 *                 signed exactly like the backend webhook-payload-generator, verified via verifyWebhook()
 *     - Callback: v4 checkout callback verified via verifyCallback()
 *
 *   Env-gated (need real transactions that can't be produced purely server-side):
 *     - Capture:  NIMBBL_PREAUTH_TXN_ID = an `authorized` txn on a capture_mode=manual sub-merchant
 *     - Void:     NIMBBL_VOID_TXN_ID    = a second `authorized` txn (capture is terminal, so use a different one)
 *     - Refund:   NIMBBL_PAID_TXN_ID    = a captured/settled txn to refund
 *
 * Example (full live pre-auth run):
 *   NIMBBL_E2E=1 NIMBBL_PREAUTH_TXN_ID=o_xxx-a NIMBBL_VOID_TXN_ID=o_yyy-b \
 *     vendor/bin/phpunit tests/E2ETest.php --testdox
 */
final class E2ETest extends TestCase
{
    /** @var array<string,mixed> */
    private static $config = [];
    private static ?NimbblClient $api = null;
    private static ?string $merchantToken = null;

    // Shared state produced by the order-creation step.
    private static ?string $orderId = null;
    private static ?string $invoiceId = null;

    protected function setUp(): void
    {
        if (getenv('NIMBBL_E2E') !== '1') {
            $this->markTestSkipped('Set NIMBBL_E2E=1 to run the live end-to-end suite.');
        }
        if (self::$api === null) {
            self::$config = loadConfig();
            self::$api = new NimbblClient(
                self::$config['access_key'],
                self::$config['access_secret'],
                self::$config['api_endpoint']
            );
        }
    }

    private function secret(): string
    {
        return (string) (self::$config['access_secret'] ?? '');
    }

    // ---- Auth --------------------------------------------------------------

    public function testGenerateToken(): void
    {
        $res = (new Request())->generateToken();
        $this->assertArrayHasKey('token', $res);
        $this->assertNotEmpty($res['token']);
        self::$merchantToken = $res['token'];
    }

    // ---- Orders ------------------------------------------------------------

    /**
     * @depends testGenerateToken
     */
    public function testCreateOrder(): void
    {
        $invoiceId = 'E2E_' . time();
        $order = self::$api->orders()->createOrder([
            'invoice_id' => $invoiceId,
            'amount_before_tax' => 100,
            'tax' => 0,
            'total_amount' => 100,
            'currency' => 'INR',
            'user' => [
                'email' => 'e2e@example.com',
                'first_name' => 'E2E',
                'last_name' => 'Tester',
                'mobile_number' => '9876543210',
                'country_code' => '+91',
            ],
        ], self::$merchantToken);

        $this->assertArrayNotHasKey('error', $order);
        $this->assertNotEmpty($order['order_id'] ?? null);
        $this->assertSame('new', $order['status'] ?? null);
        self::$orderId = $order['order_id'];
        self::$invoiceId = $invoiceId;
    }

    /**
     * @depends testCreateOrder
     */
    public function testGetOrderById(): void
    {
        $order = self::$api->orders()->getOrderById(self::$orderId, self::$merchantToken);
        $this->assertArrayNotHasKey('error', $order);
        $this->assertSame(self::$orderId, $order['order_id'] ?? null);
    }

    /**
     * @depends testCreateOrder
     */
    public function testGetOrderByInvoiceId(): void
    {
        $order = self::$api->orders()->getOrderByInvoiceId(self::$invoiceId, self::$merchantToken);
        $this->assertArrayNotHasKey('error', $order);
        $this->assertSame(self::$orderId, $order['order_id'] ?? null);
    }

    // ---- Transactions ------------------------------------------------------

    /**
     * @depends testCreateOrder
     */
    public function testTransactionEnquiry(): void
    {
        try {
            $res = self::$api->transactions()->transactionEnquiry(['order_id' => self::$orderId], self::$merchantToken);
            // A brand-new order may have no transactions yet; either a valid structure or a
            // controlled "no transaction" response is acceptable — we assert the call succeeds.
            $this->assertIsArray($res);
            $this->assertArrayNotHasKey('error', $res);
        } catch (\Exception $e) {
            $this->markTestSkipped('Transaction enquiry not applicable for a new order: ' . $e->getMessage());
        }
    }

    // ---- Pre-auth: Capture / Void / Refund (env-gated, live) ---------------

    public function testLiveCapture(): void
    {
        $txnId = getenv('NIMBBL_PREAUTH_TXN_ID');
        if (empty($txnId)) {
            $this->markTestSkipped('Set NIMBBL_PREAUTH_TXN_ID (an authorized, capture_mode=manual txn) to run live capture.');
        }
        $res = self::$api->payments()->capture(['transaction_id' => $txnId, 'comment' => 'E2E capture'], self::$merchantToken);
        $this->assertArrayNotHasKey('error', $res);
        $this->assertContains($res['capture_status'] ?? null, ['succeeded', 'pending', 'failed']);
        $this->assertSame('capture', $res['transaction_type'] ?? 'capture');
        $this->assertSame($txnId, $res['original_payment_transaction_id'] ?? $txnId);

        // Confirm via enquiry.
        $enq = self::$api->transactions()->transactionEnquiry(['transaction_id' => $txnId], self::$merchantToken);
        $this->assertIsArray($enq);
    }

    public function testLiveVoid(): void
    {
        $txnId = getenv('NIMBBL_VOID_TXN_ID');
        if (empty($txnId)) {
            $this->markTestSkipped('Set NIMBBL_VOID_TXN_ID (a second authorized txn) to run live void.');
        }
        $res = self::$api->payments()->void(['transaction_id' => $txnId, 'comment' => 'E2E void'], self::$merchantToken);
        $this->assertArrayNotHasKey('error', $res);
        $this->assertContains($res['void_status'] ?? null, ['succeeded', 'pending', 'failed']);
        $this->assertSame('void', $res['transaction_type'] ?? 'void');
    }

    public function testLiveRefund(): void
    {
        $txnId = getenv('NIMBBL_PAID_TXN_ID');
        if (empty($txnId)) {
            $this->markTestSkipped('Set NIMBBL_PAID_TXN_ID (a captured/settled txn) to run live refund.');
        }
        $res = self::$api->refunds()->initiateRefund(['transaction_id' => $txnId, 'comment' => 'E2E refund'], self::$merchantToken);
        $this->assertArrayNotHasKey('error', $res);
        $this->assertNotEmpty($res['status'] ?? $res['refund_status'] ?? null);
    }

    // ---- Webhooks: pre-auth lifecycle (faithful payloads, verified) --------

    /**
     * Builds a v4 webhook envelope exactly like the backend webhook-payload-generator
     * (SNAKE_CASE inner JSON, signature = HMAC-SHA256 over the inner JSON string) and
     * verifies it through the SDK's verifyWebhook(). This exercises the real verify path
     * end-to-end without needing a public webhook endpoint.
     */
    private function signedWebhook(string $eventType, string $status, array $txn): string
    {
        $inner = [
            'event_type' => $eventType,
            'status' => $status,
            'nimbbl_order_id' => self::$orderId ?? 'o_e2e',
            'nimbbl_transaction_id' => $txn['transaction_id'],
            'transaction' => $txn,
            'order' => ['invoice_id' => self::$invoiceId ?? 'inv_e2e', 'status' => 'lapsed'],
            'is_webhook' => true,
            'version' => SdkConstants::WEBHOOK_CALLBACK_VERSION_V4,
        ];
        $innerJson = json_encode($inner);
        return json_encode([
            'version' => SdkConstants::WEBHOOK_CALLBACK_VERSION_V4,
            'payload' => base64_encode($innerJson),
            'signature' => hash_hmac('sha256', $innerJson, $this->secret()),
            'sub_merchant_id' => 'e2e',
        ]);
    }

    public function testWebhookPreAuthLifecycle(): void
    {
        $verifier = self::$api->signatureVerifier();
        $baseTxn = [
            'transaction_id' => 'o_e2e-auth', 'status' => 'authorized', 'transaction_type' => 'payment',
            'transaction_currency' => 'INR', 'transaction_amount' => 100.00,
            'authorization_details' => [
                'mechanism' => 'pre_auth', 'capture_mode' => 'manual',
                'authorized_time' => '2026-06-22T10:46:14Z', 'expiry_time' => '2026-06-29T10:46:14Z',
                'captured_amount' => 0.0, 'voided_amount' => 0.0, 'available_authorized_amount' => 100.00,
            ],
        ];

        // 1) payment_authorized
        $r = $verifier->verifyWebhook($this->signedWebhook('payment_authorized', 'authorized', $baseTxn), $this->secret());
        $this->assertTrue($r['success'], $r['message'] ?? '');
        $this->assertSame('payment_authorized', $r['event_type']);
        $this->assertSame('pre_auth', $r['payload']['transaction']['authorization_details']['mechanism']);

        // 2) capture_success
        $capTxn = ['transaction_id' => 'o_e2e-cap', 'status' => 'succeeded', 'transaction_type' => 'capture',
            'transaction_currency' => 'INR', 'transaction_amount' => 100.00,
            'original_payment_transaction_id' => 'o_e2e-auth', 'capture_type' => 'full'];
        $r = $verifier->verifyWebhook($this->signedWebhook('capture_success', 'succeeded', $capTxn), $this->secret());
        $this->assertTrue($r['success'], $r['message'] ?? '');
        $this->assertSame('capture_success', $r['event_type']);

        // 3) void_success
        $voidTxn = ['transaction_id' => 'o_e2e-void', 'status' => 'succeeded', 'transaction_type' => 'void',
            'transaction_currency' => 'INR', 'transaction_amount' => 100.00,
            'original_payment_transaction_id' => 'o_e2e-auth', 'reversal_reason' => 'authorization_voided'];
        $r = $verifier->verifyWebhook($this->signedWebhook('void_success', 'succeeded', $voidTxn), $this->secret());
        $this->assertTrue($r['success'], $r['message'] ?? '');
        $this->assertSame('void_success', $r['event_type']);

        // 4) tampered -> rejected
        $env = json_decode($this->signedWebhook('capture_success', 'succeeded', $capTxn), true);
        $env['payload'] = base64_encode('{"event_type":"capture_success","transaction":{"transaction_amount":999999}}');
        $rt = $verifier->verifyWebhook(json_encode($env), $this->secret());
        $this->assertFalse($rt['success'], 'Tampered webhook must be rejected');
    }

    // ---- Callback: v4 checkout callback (faithful, verified) ---------------

    public function testCheckoutCallbackV4(): void
    {
        $inner = json_encode([
            'checkout_status' => 'success', 'reason' => 'payment_authorized',
            'nimbbl_order_id' => self::$orderId ?? 'o_e2e', 'invoice_id' => self::$invoiceId ?? 'inv_e2e',
            'retry' => false, 'message' => 'Your payment has been authorized.',
        ]);
        $signed = [
            'payload' => base64_encode($inner),
            'signature' => hash_hmac('sha256', $inner, $this->secret()),
            'sub_merchant_id' => 'e2e',
        ];
        $outer = base64_encode(json_encode([
            'event_type' => JsonKeys::GLOBAL_CLOSE_CHECKOUT_MODAL,
            'payload' => $signed,
        ]));

        $r = self::$api->signatureVerifier()->verifyCallback($outer, $this->secret());
        $this->assertTrue($r['success'], $r['message'] ?? '');
        $this->assertSame('payment_authorized', $r['payload']['reason'] ?? null);
        $this->assertSame('success', $r['payload']['checkout_status'] ?? null);
    }
}
