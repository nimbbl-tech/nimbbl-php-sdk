<?php

declare(strict_types=1);

// require_once __DIR__ . '/../vendor/autoload.php';

use Nimbbl\Api\Api;
use Nimbbl\Api\Webhook;
use PHPUnit\Framework\TestCase;
use Nimbbl\Tests\TestCredentials;

final class WebhookTest extends TestCase
{
    private $webhook;
    private $secret;

    protected function setUp(): void
    {
        $api = new Api(TestCredentials::ACCESS_KEY, TestCredentials::ACCESS_SECRET);
        $this->webhook = $api->webhook();
        $this->secret = TestCredentials::ACCESS_SECRET;
    }

    /**
     * Test webhook signature verification with valid signature
     */
    public function testVerifyWebhookWithValidSignature(): void
    {
        $payload = '{"event_type":"payment_success","nimbbl_order_id":"order_123"}';
        $expectedSignature = hash_hmac('sha256', $payload, $this->secret);
        
        $result = $this->webhook->verifyWebhook($payload, $expectedSignature, $this->secret);
        
        $this->assertTrue($result, 'Webhook signature verification should succeed with valid signature');
    }

    /**
     * Test webhook signature verification with invalid signature
     */
    public function testVerifyWebhookWithInvalidSignature(): void
    {
        $payload = '{"event_type":"payment_success","nimbbl_order_id":"order_123"}';
        $invalidSignature = 'invalid_signature';
        
        $result = $this->webhook->verifyWebhook($payload, $invalidSignature, $this->secret);
        
        $this->assertFalse($result, 'Webhook signature verification should fail with invalid signature');
    }

    /**
     * Test webhook signature verification with missing parameters
     */
    public function testVerifyWebhookWithMissingParams(): void
    {
        $result1 = $this->webhook->verifyWebhook('', 'signature', $this->secret);
        $this->assertFalse($result1, 'Should return false when payload is empty');
        
        $result2 = $this->webhook->verifyWebhook('payload', '', $this->secret);
        $this->assertFalse($result2, 'Should return false when signature is empty');
        
        $result3 = $this->webhook->verifyWebhook('payload', 'signature', '');
        $this->assertFalse($result3, 'Should return false when secret is empty');
    }

    /**
     * Test parsing valid webhook event
     */
    public function testParseWebhookEventWithValidPayload(): void
    {
        $payload = '{"event_type":"payment_success","nimbbl_order_id":"order_123","nimbbl_transaction_id":"txn_456"}';
        
        $result = $this->webhook->parseWebhookEvent($payload);
        
        $this->assertIsArray($result, 'Parsed webhook event should be an array');
        $this->assertEquals('payment_success', $result['event_type']);
        $this->assertEquals('order_123', $result['nimbbl_order_id']);
        $this->assertEquals('txn_456', $result['nimbbl_transaction_id']);
    }

    /**
     * Test parsing invalid JSON payload
     */
    public function testParseWebhookEventWithInvalidJson(): void
    {
        $payload = 'invalid json {';
        
        $result = $this->webhook->parseWebhookEvent($payload);
        
        $this->assertNull($result, 'Should return null for invalid JSON');
    }

    /**
     * Test parsing empty payload
     */
    public function testParseWebhookEventWithEmptyPayload(): void
    {
        $result = $this->webhook->parseWebhookEvent('');
        
        $this->assertNull($result, 'Should return null for empty payload');
    }

    /**
     * Test verify and parse with valid webhook
     */
    public function testVerifyAndParseWithValidWebhook(): void
    {
        $payload = '{"event_type":"payment_success","nimbbl_order_id":"order_123"}';
        $signature = hash_hmac('sha256', $payload, $this->secret);
        
        $result = $this->webhook->verifyAndParse($payload, $signature, $this->secret);
        
        $this->assertIsArray($result, 'Should return parsed event array');
        $this->assertEquals('payment_success', $result['event_type']);
    }

    /**
     * Test verify and parse with invalid signature
     */
    public function testVerifyAndParseWithInvalidSignature(): void
    {
        $payload = '{"event_type":"payment_success","nimbbl_order_id":"order_123"}';
        $invalidSignature = 'invalid_signature';
        
        $result = $this->webhook->verifyAndParse($payload, $invalidSignature, $this->secret);
        
        $this->assertNull($result, 'Should return null when signature is invalid');
    }

    /**
     * Test getting signature from headers
     */
    public function testGetSignatureFromHeaders(): void
    {
        $headers = [
            'X-Nimbbl-Signature' => 'test_signature_123'
        ];
        
        $result = $this->webhook->getSignatureFromHeaders($headers);
        
        $this->assertEquals('test_signature_123', $result, 'Should extract signature from X-Nimbbl-Signature header');
    }

    /**
     * Test getting signature from headers with case variations
     */
    public function testGetSignatureFromHeadersCaseVariations(): void
    {
        // Test lowercase
        $headers1 = ['x-nimbbl-signature' => 'signature_lower'];
        $result1 = $this->webhook->getSignatureFromHeaders($headers1);
        $this->assertEquals('signature_lower', $result1);
        
        // Test uppercase
        $headers2 = ['X-NIMBBL-SIGNATURE' => 'signature_upper'];
        $result2 = $this->webhook->getSignatureFromHeaders($headers2);
        $this->assertEquals('signature_upper', $result2);
        
        // Test $_SERVER format
        $headers3 = ['HTTP_X_NIMBBL_SIGNATURE' => 'signature_server'];
        $result3 = $this->webhook->getSignatureFromHeaders($headers3);
        $this->assertEquals('signature_server', $result3);
    }

    /**
     * Test getting signature from headers when not found
     */
    public function testGetSignatureFromHeadersNotFound(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Test'
        ];
        
        $result = $this->webhook->getSignatureFromHeaders($headers);
        
        $this->assertNull($result, 'Should return null when signature header is not found');
    }

    /**
     * Test getting payload from input stream
     * Note: This test may not work in all environments as it depends on php://input
     */
    public function testGetPayloadFromInput(): void
    {
        // This test is difficult to test in unit test environment
        // as php://input can only be read once and may not be available
        // In a real webhook scenario, the payload would come from the HTTP request body
        
        // We'll test that the method exists and can be called
        // Actual functionality should be tested in integration tests
        $this->assertTrue(
            method_exists($this->webhook, 'getPayloadFromInput'),
            'getPayloadFromInput method should exist'
        );
    }

    /**
     * Test webhook with real-world payment success event structure
     */
    public function testRealWorldPaymentSuccessEvent(): void
    {
        $payload = json_encode([
            'event_type' => 'payment_success',
            'nimbbl_order_id' => 'order_test_123',
            'nimbbl_transaction_id' => 'txn_test_456',
            'amount' => 1000,
            'currency' => 'INR',
            'timestamp' => time()
        ]);
        
        $signature = hash_hmac('sha256', $payload, $this->secret);
        
        // Verify signature
        $verified = $this->webhook->verifyWebhook($payload, $signature, $this->secret);
        $this->assertTrue($verified, 'Real-world event signature should be valid');
        
        // Parse event
        $event = $this->webhook->parseWebhookEvent($payload);
        $this->assertIsArray($event);
        $this->assertEquals('payment_success', $event['event_type']);
        $this->assertEquals('order_test_123', $event['nimbbl_order_id']);
        
        // Verify and parse together
        $result = $this->webhook->verifyAndParse($payload, $signature, $this->secret);
        $this->assertIsArray($result);
        $this->assertEquals('payment_success', $result['event_type']);
    }
}

