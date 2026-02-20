<?php

declare(strict_types=1);

namespace Nimbbl\Tests;

require_once __DIR__ . '/../example/utils/helpers.php';

use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\Common\SignatureVerifier;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\SdkConstants;
use PHPUnit\Framework\TestCase;

final class SignatureVerifierTest extends TestCase
{
    private $signatureVerifier;
    private $secret;
    private $config;

    protected function setUp(): void
    {
        $this->config = loadConfig();
        // Initialize client but we mostly need the Webhook service instance
        $api = new NimbblClient(
            $this->config['access_key'],
            $this->config['access_secret'],
            $this->config['api_endpoint']
        );
        $this->signatureVerifier = $api->signatureVerifier();
        $this->secret = 'test_secret_key_12345'; // Use a fixed secret for reproducible tests
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
                JsonKeys::NIMBBL_SIGNATURE => $signature
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
                JsonKeys::INVOICE_ID => $invoiceId
            ],
            JsonKeys::TRANSACTION => [
                JsonKeys::TRANSACTION_ID => $transactionId,
                JsonKeys::REFUND_AMOUNT => $amount,
                JsonKeys::TRANSACTION_CURRENCY => $currency,
                JsonKeys::REFUND_STATUS => $status,
                JsonKeys::TRANSACTION_TYPE => $type,
                JsonKeys::SIGNATURE_VERSION => SdkConstants::SIGNATURE_VERSION_V3,
                JsonKeys::NIMBBL_SIGNATURE => $signature
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
                JsonKeys::NIMBBL_SIGNATURE => $signature
            ],
            JsonKeys::EVENT_TYPE => 'payment_success'
        ];

        $jsonPayload = json_encode($data);

        $result = $this->signatureVerifier->verifySignature($jsonPayload, $this->secret);

        $this->assertTrue($result['success'], 'Verify and Parse should succeed');
        $this->assertIsArray($result['parsed']);
        $this->assertEquals('payment_success', $result['parsed'][JsonKeys::EVENT_TYPE]);
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
                JsonKeys::NIMBBL_SIGNATURE => 'invalid_signature_hash'
            ],
            JsonKeys::EVENT_TYPE => 'payment_success'
        ];

        $result = $this->signatureVerifier->verifySignature($attributes, $this->secret);

        $this->assertFalse($result['success'], 'Should fail on invalid signature');
    }
}
