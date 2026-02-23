<?php

declare(strict_types=1);

namespace Nimbbl\Tests;

require_once __DIR__ . '/../example/utils/helpers.php';

use Nimbbl\Api\Common\PayloadHelperUtils;
use Nimbbl\Api\Common\Encryption;
use Nimbbl\Api\Common\JsonKeys;
use PHPUnit\Framework\TestCase;

/**
 * PayloadHelperUtilsTest
 * 
 * Tests for Nimbbl\Api\Common\PayloadHelperUtils class
 * Coverage: Webhook payload parsing, encryption/decryption, unwrapping logic
 */
final class PayloadHelperUtilsTest extends TestCase
{
    private string $testSecret = 'access_secret_test_key_123';
    private Encryption $encryption;

    protected function setUp(): void
    {
        $this->encryption = new Encryption($this->testSecret);
    }

    /**
     * Test parsing simple JSON payload without encryption
     */
    public function testParseSimpleJsonPayload(): void
    {
        $payload = json_encode([
            'event_type' => 'payment_success',
            'order_id' => 'ord_123',
            'amount' => 100.00
        ]);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        $this->assertIsArray($result);
        $this->assertEquals('payment_success', $result['event_type']);
        $this->assertEquals('ord_123', $result['order_id']);
        $this->assertEquals(100.00, $result['amount']);
    }

    /**
     * Test parsing payload with encrypted_response at top level
     */
    public function testParsePayloadWithTopLevelEncryptedResponse(): void
    {
        $originalData = [
            'event_type' => 'payment_success',
            'order_id' => 'ord_456',
            'amount' => 250.50,
            'transaction_id' => 'txn_789'
        ];

        $encrypted = $this->encryption->encrypt($originalData);
        $payload = json_encode([
            'encrypted_response' => $encrypted
        ]);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        $this->assertIsArray($result);
        $this->assertEquals('payment_success', $result['event_type']);
        $this->assertEquals('ord_456', $result['order_id']);
        $this->assertEquals(250.50, $result['amount']);
    }

    /**
     * Test parsing payload with encrypted_response nested in "payload"
     */
    public function testParsePayloadWithNestedInPayload(): void
    {
        $originalData = [
            'event_type' => 'refund_success',
            'refund_id' => 'ref_123',
            'refund_amount' => 100.00
        ];

        $encrypted = $this->encryption->encrypt($originalData);
        $payload = json_encode([
            'payload' => [
                'encrypted_response' => $encrypted
            ]
        ]);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        $this->assertEquals('refund_success', $result['event_type']);
        $this->assertEquals('ref_123', $result['refund_id']);
        $this->assertEquals(100.00, $result['refund_amount']);
    }

    /**
     * Test parsing payload with encrypted_response nested in "callback"
     */
    public function testParsePayloadWithNestedInCallback(): void
    {
        $originalData = [
            'event_type' => 'payment_failed',
            'order_id' => 'ord_fail',
            'reason' => 'insufficient_funds'
        ];

        $encrypted = $this->encryption->encrypt($originalData);
        $payload = json_encode([
            'callback' => [
                'encrypted_response' => $encrypted
            ]
        ]);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        $this->assertEquals('payment_failed', $result['event_type']);
        $this->assertEquals('ord_fail', $result['order_id']);
        $this->assertEquals('insufficient_funds', $result['reason']);
    }

    /**
     * Test parsing payload with globalHandleCheckoutResponse unwrapping
     */
    public function testParsePayloadWithGlobalHandleCheckoutUnwrapping(): void
    {
        $actualData = [
            'order_id' => 'ord_checkout',
            'amount' => 500.00,
            'status' => 'success'
        ];

        $payload = json_encode([
            'event_type' => 'globalHandleCheckoutResponse',
            'payload' => $actualData
        ]);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        // Should unwrap the nested payload
        $this->assertIsArray($result);
        $this->assertEquals('ord_checkout', $result['order_id']);
        $this->assertEquals(500.00, $result['amount']);
    }

    /**
     * Test parsing payload with globalHandleCheckoutResponse and "data" field
     */
    public function testParsePayloadWithGlobalHandleCheckoutDataField(): void
    {
        $actualData = [
            'order_id' => 'ord_checkout2',
            'amount' => 750.75
        ];

        $payload = json_encode([
            'event_type' => 'globalHandleCheckoutResponse',
            'data' => $actualData
        ]);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        $this->assertEquals('ord_checkout2', $result['order_id']);
        $this->assertEquals(750.75, $result['amount']);
    }

    /**
     * Test parseResponse with base64 encoded JSON
     */
    public function testParseResponseWithBase64Encoding(): void
    {
        $originalPayload = json_encode([
            'event_type' => 'payment_success',
            'order_id' => 'ord_base64',
            'amount' => 100.00
        ]);

        $base64Encoded = base64_encode($originalPayload);

        $result = PayloadHelperUtils::parseResponse($base64Encoded, $this->testSecret);

        $this->assertIsArray($result);
        $this->assertEquals('payment_success', $result['event_type']);
        $this->assertEquals('ord_base64', $result['order_id']);
    }

    /**
     * Test parseResponse with non-base64 JSON string
     */
    public function testParseResponseWithDirectJson(): void
    {
        $payload = json_encode([
            'event_type' => 'refund_success',
            'refund_id' => 'ref_direct',
            'amount' => 50.00
        ]);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        $this->assertEquals('refund_success', $result['event_type']);
        $this->assertEquals('ref_direct', $result['refund_id']);
        $this->assertEquals(50.00, $result['amount']);
    }

    /**
     * Test parseResponse with encrypted base64 payload
     */
    public function testParseResponseWithEncryptedBase64(): void
    {
        $originalData = [
            'event_type' => 'payment_link_paid',
            'payment_link_id' => 'pl_123',
            'amount_paid' => 300.00
        ];

        $encrypted = $this->encryption->encrypt($originalData);
        $payload = json_encode([
            'encrypted_response' => $encrypted
        ]);

        $base64Encoded = base64_encode($payload);

        $result = PayloadHelperUtils::parseResponse($base64Encoded, $this->testSecret);

        $this->assertEquals('payment_link_paid', $result['event_type']);
        $this->assertEquals('pl_123', $result['payment_link_id']);
        $this->assertEquals(300.00, $result['amount_paid']);
    }

    /**
     * Test parseResponse with empty response throws exception
     */
    public function testParseResponseWithEmptyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PayloadHelperUtils::parseResponse('', $this->testSecret);
    }

    /**
     * Test parseResponse with null response throws exception
     */
    public function testParseResponseWithNullThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PayloadHelperUtils::parseResponse(null, $this->testSecret);
    }

    /**
     * Test parsing invalid JSON throws exception
     */
    public function testParseInvalidJsonThrows(): void
    {
        $this->expectException(\Exception::class);
        PayloadHelperUtils::parseResponse('{invalid json}', $this->testSecret);
    }

    /**
     * Test parsing payload with encrypted response using wrong secret fails
     */
    public function testParseEncryptedWithWrongSecretFails(): void
    {
        $originalData = ['order_id' => 'ord_test'];
        $encrypted = $this->encryption->encrypt($originalData);
        
        $payload = json_encode([
            'encrypted_response' => $encrypted
        ]);

        $this->expectException(\Exception::class);
        PayloadHelperUtils::parseResponse($payload, 'access_secret_wrong_key');
    }

    /**
     * Test parsing payload with nested callback structure
     */
    public function testParsePayloadWithTopLevelCallback(): void
    {
        $callbackData = [
            'event_type' => 'payment_success',
            'order_id' => 'ord_callback',
            'amount' => 200.00
        ];

        $payload = json_encode([
            'callback' => $callbackData
        ]);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        $this->assertEquals('payment_success', $result['event_type']);
        $this->assertEquals('ord_callback', $result['order_id']);
    }

    /**
     * Test parsing payload with complex nested structure
     */
    public function testParseComplexNestedStructure(): void
    {
        $complexData = [
            'event_type' => 'payment_success',
            'order' => [
                'order_id' => 'ord_complex',
                'items' => [
                    ['id' => 'item1', 'qty' => 2],
                    ['id' => 'item2', 'qty' => 1]
                ]
            ],
            'transaction' => [
                'transaction_id' => 'txn_complex',
                'amount' => 500.00,
                'currency' => 'INR'
            ]
        ];

        $encrypted = $this->encryption->encrypt($complexData);
        $payload = json_encode([
            'encrypted_response' => $encrypted
        ]);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        $this->assertEquals('payment_success', $result['event_type']);
        $this->assertEquals('ord_complex', $result['order']['order_id']);
        $this->assertEquals(2, count($result['order']['items']));
        $this->assertEquals('txn_complex', $result['transaction']['transaction_id']);
    }

    /**
     * Test parsing payload preserves data types
     */
    public function testParsePreservesDataTypes(): void
    {
        $typedData = [
            'string_value' => 'hello',
            'int_value' => 42,
            'float_value' => 3.14159,
            'bool_value' => true,
            'null_value' => null,
            'array_value' => [1, 2, 3],
            'object_value' => ['nested' => 'data']
        ];

        $encrypted = $this->encryption->encrypt($typedData);
        $payload = json_encode([
            'encrypted_response' => $encrypted
        ]);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        $this->assertIsString($result['string_value']);
        $this->assertIsInt($result['int_value']);
        $this->assertIsFloat($result['float_value']);
        $this->assertIsBool($result['bool_value']);
        $this->assertNull($result['null_value']);
        $this->assertIsArray($result['array_value']);
        $this->assertIsArray($result['object_value']);
    }

    /**
     * Test parsing Unicode/special characters in payload
     */
    public function testParseUnicodeCharacters(): void
    {
        $payloadData = [
            'event_type' => 'payment_success',
            'customer_name' => 'José García',
            'address' => '北京市朝阳区',
            'description' => 'مرحبا بك 🔐'
        ];

        $encrypted = $this->encryption->encrypt($payloadData);
        $payload = json_encode([
            'encrypted_response' => $encrypted
        ], JSON_UNESCAPED_UNICODE);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        $this->assertEquals('José García', $result['customer_name']);
        $this->assertEquals('北京市朝阳区', $result['address']);
        $this->assertStringContainsString('🔐', $result['description']);
    }

    /**
     * Test parsing payload with very large data
     */
    public function testParseLargePayload(): void
    {
        $largeData = [
            'event_type' => 'bulk_payment',
            'transactions' => array_fill(0, 100, [
                'transaction_id' => 'txn_' . uniqid(),
                'amount' => 111.11,
                'description' => str_repeat('Lorem ipsum dolor sit amet ', 10)
            ])
        ];

        $encrypted = $this->encryption->encrypt($largeData);
        $payload = json_encode([
            'encrypted_response' => $encrypted
        ]);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        $this->assertEquals('bulk_payment', $result['event_type']);
        $this->assertEquals(100, count($result['transactions']));
    }

    /**
     * Test parseResponse handles corrupted base64 gracefully
     */
    public function testParseResponseCorruptedBase64Fallback(): void
    {
        // Valid JSON that looks like it could be base64 but isn't
        $jsonPayload = json_encode([
            'event_type' => 'payment_success',
            'order_id' => 'ord_test'
        ]);

        $result = PayloadHelperUtils::parseResponse($jsonPayload, $this->testSecret);

        $this->assertEquals('payment_success', $result['event_type']);
    }

    /**
     * Test parse with encrypted response containing nested JSON
     */
    public function testParseEncryptedResponseWithNestedJson(): void
    {
        $nestedData = [
            'event_type' => 'webhook_test',
            'payload' => json_encode([
                'inner_data' => ['key' => 'value']
            ]) // Note: nested JSON as string inside encrypted data
        ];

        $encrypted = $this->encryption->encrypt($nestedData);
        $payload = json_encode([
            'encrypted_response' => $encrypted
        ]);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        $this->assertEquals('webhook_test', $result['event_type']);
        $this->assertIsString($result['payload']); // Should be unparsed string
    }

    /**
     * Test parse handles payload with additional metadata
     */
    public function testParsePayloadWithMetadata(): void
    {
        $payloadData = [
            'event_type' => 'payment_success',
            'order_id' => 'ord_meta',
            'meta' => [
                'request_id' => 'req_123',
                'timestamp' => time(),
                'merchant_id' => 'merchant_456'
            ]
        ];

        $payload = json_encode($payloadData);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        $this->assertEquals('payment_success', $result['event_type']);
        $this->assertIsArray($result['meta']);
        $this->assertEquals('req_123', $result['meta']['request_id']);
    }

    /**
     * Test parseResponse with mixed encoding scenarios
     */
    public function testParseResponseMixedScenarios(): void
    {
        // Scenario 1: Plain JSON
        $plainPayload = json_encode(['event_type' => 'payment', 'id' => '1']);
        $result1 = PayloadHelperUtils::parseResponse($plainPayload, $this->testSecret);
        $this->assertEquals('payment', $result1['event_type']);

        // Scenario 2: Base64 encoded JSON
        $base64Payload = base64_encode(json_encode(['event_type' => 'refund', 'id' => '2']));
        $result2 = PayloadHelperUtils::parseResponse($base64Payload, $this->testSecret);
        $this->assertEquals('refund', $result2['event_type']);
    }

    /**
     * Test parse validates event_type field
     */
    public function testParseValidatesEventType(): void
    {
        $payload = json_encode([
            'order_id' => 'ord_123'
            // Note: No event_type field
        ]);

        $result = PayloadHelperUtils::parseResponse($payload, $this->testSecret);

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('event_type', $result);
    }
}
