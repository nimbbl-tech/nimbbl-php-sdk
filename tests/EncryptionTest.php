<?php

declare(strict_types=1);

namespace Nimbbl\Tests;

require_once __DIR__ . '/../example/utils/helpers.php';

use Nimbbl\Api\Common\Encryption;
use Nimbbl\Api\Exception\NimbblException;
use PHPUnit\Framework\TestCase;

/**
 * EncryptionTest
 * 
 * Tests for Nimbbl\Api\Common\Encryption class
 * Coverage: AES-256-GCM encryption/decryption operations
 */
final class EncryptionTest extends TestCase
{
    private Encryption $encryption;
    private string $testSecret = 'access_secret_test_key_123';

    protected function setUp(): void
    {
        $this->encryption = new Encryption($this->testSecret);
    }

    /**
     * Test encrypting a simple string
     */
    public function testEncryptSimpleString(): void
    {
        $plaintext = 'Hello, World!';
        $encrypted = $this->encryption->encrypt($plaintext);

        // Should be a hex string
        $this->assertIsString($encrypted);
        $this->assertTrue(ctype_xdigit($encrypted), 'Encrypted output should be valid hex');
        $this->assertGreaterThan(0, strlen($encrypted), 'Encrypted output should not be empty');
    }

    /**
     * Test encrypting an array/object (JSON serialization)
     */
    public function testEncryptArray(): void
    {
        $data = [
            'order_id' => 'ord_123',
            'amount' => 100.50,
            'currency' => 'INR',
            'metadata' => ['key' => 'value']
        ];
        
        $encrypted = $this->encryption->encrypt($data);

        $this->assertIsString($encrypted);
        $this->assertTrue(ctype_xdigit($encrypted), 'Encrypted output should be valid hex');
    }

    /**
     * Test encrypting empty string
     */
    public function testEncryptEmptyString(): void
    {
        $plaintext = '';
        $encrypted = $this->encryption->encrypt($plaintext);

        $this->assertIsString($encrypted);
        $this->assertTrue(ctype_xdigit($encrypted), 'Encrypted output should be valid hex');
        $this->assertGreaterThan(0, strlen($encrypted), 'Even empty string should produce non-empty encrypted output');
    }

    /**
     * Test encrypting special characters
     */
    public function testEncryptSpecialCharacters(): void
    {
        $plaintext = '!@#$%^&*()_+-=[]{}|;:\'"<>?,./ñáéíóúü';
        $encrypted = $this->encryption->encrypt($plaintext);

        $this->assertIsString($encrypted);
        $this->assertTrue(ctype_xdigit($encrypted), 'Encrypted output should be valid hex');
    }

    /**
     * Test encrypting UTF-8 characters
     */
    public function testEncryptUTF8Characters(): void
    {
        $plaintext = 'Hello 你好 مرحبا Привет 🔐';
        $encrypted = $this->encryption->encrypt($plaintext);

        $this->assertIsString($encrypted);
        $this->assertTrue(ctype_xdigit($encrypted), 'Encrypted output should be valid hex');
    }

    /**
     * Test round-trip encryption/decryption for string
     */
    public function testEncryptDecryptRoundTripString(): void
    {
        $plaintext = 'This is a test message for encryption round-trip';
        
        $encrypted = $this->encryption->encrypt($plaintext);
        $decrypted = $this->encryption->decrypt($encrypted, false);

        $this->assertEquals($plaintext, $decrypted, 'Decrypted text should match original plaintext');
    }

    /**
     * Test round-trip encryption/decryption for array
     */
    public function testEncryptDecryptRoundTripArray(): void
    {
        $originalData = [
            'order_id' => 'ord_xyz',
            'amount' => 250.75,
            'items' => [
                ['id' => 'item1', 'qty' => 2],
                ['id' => 'item2', 'qty' => 1]
            ],
            'timestamp' => time()
        ];
        
        $encrypted = $this->encryption->encrypt($originalData);
        $decrypted = $this->encryption->decrypt($encrypted, true);

        $this->assertIsArray($decrypted, 'Decrypted with returnAsArray=true should be array');
        $this->assertEquals($originalData, $decrypted, 'Decrypted array should match original');
    }

    /**
     * Test decrypt returns string when returnAsArray=false
     */
    public function testDecryptReturnsString(): void
    {
        $originalData = ['key' => 'value'];
        $encrypted = $this->encryption->encrypt($originalData);
        $decrypted = $this->encryption->decrypt($encrypted, false);

        $this->assertIsString($decrypted);
        $this->assertJson($decrypted, 'Decrypted string should be valid JSON');
    }

    /**
     * Test decrypt returns array when returnAsArray=true
     */
    public function testDecryptReturnsArray(): void
    {
        $originalData = ['key' => 'value'];
        $encrypted = $this->encryption->encrypt($originalData);
        $decrypted = $this->encryption->decrypt($encrypted, true);

        $this->assertIsArray($decrypted);
        $this->assertEquals($originalData, $decrypted);
    }

    /**
     * Test decryption with invalid hex string throws exception
     */
    public function testDecryptInvalidHexString(): void
    {
        $this->expectException(NimbblException::class);
        
        // Invalid hex string (contains non-hex characters)
        $invalid = 'not_valid_hex_ZZZZZ';
        $this->encryption->decrypt($invalid);
    }

    /**
     * Test decryption with malformed encrypted data throws exception
     */
    public function testDecryptMalformedData(): void
    {
        $this->expectException(NimbblException::class);
        
        // Valid hex but too short (needs nonce + ciphertext + tag)
        $malformed = bin2hex('short');
        $this->encryption->decrypt($malformed);
    }

    /**
     * Test decryption with wrong key fails authentication
     */
    public function testDecryptWithWrongKeyFails(): void
    {
        $plaintext = 'Secret message';
        $encrypted = $this->encryption->encrypt($plaintext);

        // Create encryption instance with different key
        $wrongKeyEncryption = new Encryption('access_secret_different_key');
        
        $this->expectException(NimbblException::class);
        $wrongKeyEncryption->decrypt($encrypted);
    }

    /**
     * Test constructor with empty secret throws exception
     */
    public function testConstructorWithEmptySecretThrows(): void
    {
        $this->expectException(NimbblException::class);
        
        new Encryption('');
    }

    /**
     * Test constructor with null secret throws exception
     */
    public function testConstructorWithNullSecretThrows(): void
    {
        $this->expectException(\TypeError::class);

        $class = new \ReflectionClass(Encryption::class);
        $class->newInstanceArgs([null]);
    }

    /**
     * Test encryption with different key iterations
     */
    public function testEncryptionWithDifferentIterations(): void
    {
        $plaintext = 'Test message';
        
        // Encrypt with 1 iteration (default)
        $enc1 = new Encryption($this->testSecret, 1);
        $encrypted1 = $enc1->encrypt($plaintext);

        // Encrypt with 1 iteration again - should produce different result (random nonce)
        $encrypted2 = $enc1->encrypt($plaintext);

        // Both should be valid hex
        $this->assertTrue(ctype_xdigit($encrypted1));
        $this->assertTrue(ctype_xdigit($encrypted2));

        // Both should decrypt to same plaintext (same key)
        $this->assertEquals($plaintext, $enc1->decrypt($encrypted1));
        $this->assertEquals($plaintext, $enc1->decrypt($encrypted2));
    }

    /**
     * Test getEncryptionKeyHex returns valid hex
     */
    public function testGetEncryptionKeyHex(): void
    {
        $keyHex = $this->encryption->getEncryptionKeyHex();

        $this->assertIsString($keyHex);
        $this->assertTrue(ctype_xdigit($keyHex), 'Key hex should be valid hexadecimal');
        $this->assertEquals(64, strlen($keyHex), 'AES-256 key should be 32 bytes (64 hex chars)');
    }

    /**
     * Test same secret produces same key
     */
    public function testSameSecretProducesSameKey(): void
    {
        $enc1 = new Encryption($this->testSecret);
        $enc2 = new Encryption($this->testSecret);

        $key1 = $enc1->getEncryptionKeyHex();
        $key2 = $enc2->getEncryptionKeyHex();

        $this->assertEquals($key1, $key2, 'Same secret should produce same key');
    }

    /**
     * Test different secrets produce different keys
     */
    public function testDifferentSecretsProduceDifferentKeys(): void
    {
        $enc1 = new Encryption('access_secret_key1');
        $enc2 = new Encryption('access_secret_key2');

        $key1 = $enc1->getEncryptionKeyHex();
        $key2 = $enc2->getEncryptionKeyHex();

        $this->assertNotEquals($key1, $key2, 'Different secrets should produce different keys');
    }

    /**
     * Test access_secret_ prefix is correctly stripped
     */
    public function testAccessSecretPrefixStripped(): void
    {
        $secretWithPrefix = 'access_secret_mykey123';
        $encWithPrefix = new Encryption($secretWithPrefix);

        // This should work - prefix is stripped for key generation
        $encrypted = $encWithPrefix->encrypt('test');
        $decrypted = $encWithPrefix->decrypt($encrypted);

        $this->assertEquals('test', $decrypted);
    }

    /**
     * Test large data encryption/decryption
     */
    public function testLargeDataEncryptionDecryption(): void
    {
        $largeData = [
            'items' => array_fill(0, 100, [
                'id' => 'item_' . uniqid(),
                'name' => str_repeat('A', 50),
                'description' => str_repeat('Lorem ipsum dolor sit amet ', 10),
                'price' => 99.99,
                'quantity' => 10
            ]),
            'metadata' => str_repeat('X', 5000)
        ];

        $encrypted = $this->encryption->encrypt($largeData);
        $decrypted = $this->encryption->decrypt($encrypted, true);

        $this->assertEquals($largeData, $decrypted, 'Large data should round-trip successfully');
    }

    /**
     * Test numeric data encryption/decryption
     */
    public function testNumericDataEncryption(): void
    {
        $number = 12345.67;
        $encrypted = $this->encryption->encrypt($number);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals((string)$number, $decrypted);
    }

    /**
     * Test boolean values
     */
    public function testBooleanEncryption(): void
    {
        $boolean = true;
        $encrypted = $this->encryption->encrypt($boolean);
        $decrypted = $this->encryption->decrypt($encrypted);

        // Booleans are cast to string, true becomes '1'
        $this->assertEquals('1', $decrypted);
    }

    /**
     * Test multiple encryptions produce different ciphertexts (random nonce)
     */
    public function testMultipleEncryptionsDifferent(): void
    {
        $plaintext = 'Same plaintext';
        
        $encrypted1 = $this->encryption->encrypt($plaintext);
        $encrypted2 = $this->encryption->encrypt($plaintext);
        $encrypted3 = $this->encryption->encrypt($plaintext);

        // All should be different due to random nonce
        $this->assertNotEquals($encrypted1, $encrypted2, 'Different encryptions should have different nonces');
        $this->assertNotEquals($encrypted2, $encrypted3, 'Different encryptions should have different nonces');
        $this->assertNotEquals($encrypted1, $encrypted3, 'Different encryptions should have different nonces');

        // But all should decrypt to same plaintext
        $this->assertEquals($plaintext, $this->encryption->decrypt($encrypted1));
        $this->assertEquals($plaintext, $this->encryption->decrypt($encrypted2));
        $this->assertEquals($plaintext, $this->encryption->decrypt($encrypted3));
    }

    /**
     * Test nested array structures
     */
    public function testNestedArrayStructure(): void
    {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'value' => 'deeply nested'
                        ]
                    ]
                ]
            ]
        ];

        $encrypted = $this->encryption->encrypt($data);
        $decrypted = $this->encryption->decrypt($encrypted, true);

        $this->assertEquals($data, $decrypted);
        $this->assertEquals('deeply nested', $decrypted['level1']['level2']['level3']['level4']['value']);
    }
}
