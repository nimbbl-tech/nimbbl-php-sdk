<?php

namespace Nimbbl\Api;

use Exception;
use Nimbbl\Api\Exception\NimbblException;

/**
 * Nimbbl Symmetric Encryption for Payload/Response encryption and decryption
 * 
 * Implements AES-GCM Encryption/Decryption as per Nimbbl API documentation.
 * 
 * Primary Use Case:
 * This utility is primarily used to decrypt responses from Standard Checkout integration.
 * When your client (web/mobile app) forwards the checkout response to your server,
 * it may include an 'encrypted_response' field that needs to be decrypted.
 * 
 * References:
 * - Standard Checkout Integration: https://nimbbl.biz/docs/standard-checkout/completing-integration/
 * - Encryption/Decryption Guide: https://nimbbl.biz/docs/guides/encrypt-decrypt-payload/
 * 
 * Key Features:
 * - AES-GCM encryption mode
 * - Encrypted payload format: 16 bytes nonce + encrypted payload + 16 bytes auth tag
 * - Hex string output for encrypted data
 * 
 * Usage Example (Standard Checkout):
 * ```php
 * // Client forwards checkout response to your server
 * $checkoutResponse = [
 *     'event_type' => 'globalHandleCheckoutResponse',
 *     'payload' => [
 *         'encrypted_response' => '3164351ca6195e9871cca9de3117cb8f...'
 *     ]
 * ];
 * 
 * // Decrypt on server
 * $encryption = new Encryption($accessSecret);
 * $decrypted = $encryption->decrypt($checkoutResponse['payload']['encrypted_response'], true);
 * 
 * // Now validate signature and process payment
 * $status = $decrypted['status']; // 'success', 'failed', or 'pending'
 * $orderId = $decrypted['nimbbl_order_id'];
 * ```
 */
class Encryption
{
    /**
     * GCM tag length in bytes
     */
    const GCM_TAG_LENGTH = 16;

    /**
     * GCM nonce length in bytes
     */
    const GCM_NONCE_LENGTH = 16;

    /**
     * Encryption key (32 bytes for AES-256)
     * @var string
     */
    private $encryptionKey;

    /**
     * Number of SHA256 iterations for key generation
     * @var int
     */
    private $keyIterations;

    /**
     * Constructor
     * 
     * @param string $accessSecret Access secret from Nimbbl dashboard
     * @param int $keyIterations Number of SHA256 iterations (default: 1)
     *                           Note: Any mismatch with merchant settings will fail encryption/decryption
     */
    public function __construct($accessSecret, $keyIterations = 1)
    {
        if (empty($accessSecret)) {
            throw new NimbblException(
                'Access secret is required for encryption',
                'INVALID_ACCESS_SECRET',
                400
            );
        }

        $this->keyIterations = $keyIterations;
        $this->generateKey($accessSecret);
    }

    /**
     * Generate encryption key from access secret
     * 
     * Steps:
     * 1. Remove "access_secret_" prefix from access secret
     * 2. Generate SHA256 hash (with optional iterations)
     * 
     * @param string $accessSecret Access secret
     * @return void
     */
    private function generateKey($accessSecret)
    {
        // Remove "access_secret_" prefix
        $keyString = str_replace('access_secret_', '', $accessSecret);
        
        // Generate SHA256 hash (with iterations)
        $byteKey = $keyString;
        for ($i = 0; $i < $this->keyIterations; $i++) {
            $byteKey = hash('sha256', $byteKey, true); // true = raw binary output
        }
        
        $this->encryptionKey = $byteKey;
    }

    /**
     * Encrypt data using AES-GCM
     * 
     * Encrypted payload format:
     * - First 16 bytes: Nonce (IV)
     * - Middle bytes: Encrypted payload
     * - Last 16 bytes: Authentication tag
     * 
     * @param string|array $data Data to encrypt (string, array, or JSON-encodable data)
     * @return string Hex-encoded encrypted string
     * @throws NimbblException If encryption fails
     */
    public function encrypt($data)
    {
        try {
            // Convert data to string/bytes
            if (is_array($data)) {
                $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } elseif (!is_string($data)) {
                $data = (string) $data;
            }

            $plaintext = $data;

            // Check if OpenSSL supports AES-GCM
            if (!in_array('aes-256-gcm', openssl_get_cipher_methods())) {
                throw new NimbblException(
                    'AES-256-GCM cipher is not available. Please ensure OpenSSL extension is installed and supports GCM mode.',
                    'ENCRYPTION_NOT_SUPPORTED',
                    500
                );
            }

            // Generate random nonce (IV)
            $nonce = openssl_random_pseudo_bytes(self::GCM_NONCE_LENGTH);
            if ($nonce === false) {
                throw new NimbblException(
                    'Failed to generate random nonce',
                    'NONCE_GENERATION_FAILED',
                    500
                );
            }

            // Encrypt with AES-256-GCM
            $tag = '';
            $ciphertext = openssl_encrypt(
                $plaintext,
                'aes-256-gcm',
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $nonce,
                $tag,
                '',
                16 // Tag length
            );

            if ($ciphertext === false) {
                throw new NimbblException(
                    'Encryption failed: ' . openssl_error_string(),
                    'ENCRYPTION_FAILED',
                    500
                );
            }

            // Verify tag length
            if (strlen($tag) !== self::GCM_TAG_LENGTH) {
                throw new NimbblException(
                    'Authentication tag length mismatch. Expected ' . self::GCM_TAG_LENGTH . ' bytes, got ' . strlen($tag),
                    'TAG_LENGTH_MISMATCH',
                    500
                );
            }

            // Concatenate: nonce + ciphertext + tag
            $encryptedData = $nonce . $ciphertext . $tag;

            // Convert to hex string
            return bin2hex($encryptedData);
        } catch (NimbblException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new NimbblException(
                'Encryption error: ' . $e->getMessage(),
                'ENCRYPTION_ERROR',
                null,
                500,
                ['original_exception' => $e->getMessage()],
                $e
            );
        }
    }

    /**
     * Decrypt data using AES-GCM
     * 
     * @param string $encryptedData Hex-encoded encrypted string
     * @param bool $returnAsArray If true, return decoded JSON as array; otherwise return string
     * @return string|array Decrypted data
     * @throws NimbblException If decryption fails
     */
    public function decrypt($encryptedData, $returnAsArray = false)
    {
        try {
            // Convert hex string to bytes
            $encryptedBytes = hex2bin($encryptedData);
            if ($encryptedBytes === false) {
                throw new NimbblException(
                    'Invalid hex string provided for decryption',
                    'INVALID_HEX_STRING',
                    400
                );
            }

            // Verify minimum length (nonce + tag = 32 bytes minimum)
            $minLength = self::GCM_NONCE_LENGTH + self::GCM_TAG_LENGTH;
            if (strlen($encryptedBytes) < $minLength) {
                throw new NimbblException(
                    'Encrypted data too short. Expected at least ' . $minLength . ' bytes, got ' . strlen($encryptedBytes),
                    'INVALID_ENCRYPTED_DATA',
                    400
                );
            }

            // Extract nonce (first 16 bytes)
            $nonce = substr($encryptedBytes, 0, self::GCM_NONCE_LENGTH);

            // Extract tag (last 16 bytes)
            $tag = substr($encryptedBytes, -self::GCM_TAG_LENGTH);

            // Extract ciphertext (middle bytes)
            $ciphertext = substr($encryptedBytes, self::GCM_NONCE_LENGTH, -self::GCM_TAG_LENGTH);

            // Check if OpenSSL supports AES-GCM
            if (!in_array('aes-256-gcm', openssl_get_cipher_methods())) {
                throw new NimbblException(
                    'AES-256-GCM cipher is not available. Please ensure OpenSSL extension is installed and supports GCM mode.',
                    'DECRYPTION_NOT_SUPPORTED',
                    500
                );
            }

            // Decrypt with AES-256-GCM
            $plaintext = openssl_decrypt(
                $ciphertext,
                'aes-256-gcm',
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $nonce,
                $tag
            );

            if ($plaintext === false) {
                $error = openssl_error_string();
                throw new NimbblException(
                    'Decryption failed. The encrypted data may be corrupted or the key is incorrect. ' . ($error ?: ''),
                    'DECRYPTION_FAILED',
                    400
                );
            }

            // Return as array if requested and data is valid JSON
            if ($returnAsArray) {
                $decoded = json_decode($plaintext, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }

            return $plaintext;
        } catch (NimbblException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new NimbblException(
                'Decryption error: ' . $e->getMessage(),
                'DECRYPTION_ERROR',
                null,
                500,
                ['original_exception' => $e->getMessage()],
                $e
            );
        }
    }

    /**
     * Get the encryption key (for debugging purposes only)
     * 
     * @return string Hex representation of encryption key
     */
    public function getEncryptionKeyHex()
    {
        return bin2hex($this->encryptionKey);
    }
}

