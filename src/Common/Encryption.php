<?php

namespace Nimbbl\Api\Common;

use Exception;
use Nimbbl\Api\Common\HttpStatusCodes;
use Nimbbl\Api\Log\Logger;
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
    private string $encryptionKey;

    /**
     * Number of SHA256 iterations for key generation
     * @var int
     */
    private int $keyIterations;

    /**
     * Constructor
     * 
     * @param string $accessSecret Access secret from Nimbbl dashboard
     * @param int $keyIterations Number of SHA256 iterations (default: 1)
     *                           Note: Any mismatch with merchant settings will fail encryption/decryption
     * 
     * ⚠️ SECURITY WARNING:
     * The default single iteration (1) is cryptographically weak.
     * This is maintained for backward compatibility with existing merchant configurations.
     * For new implementations, consider requesting merchant to increase iterations
     * or implement PBKDF2 with higher iteration counts.
     * 
     * @throws NimbblException If the access secret is empty
     */
    public function __construct(string $accessSecret, int $keyIterations = 1)
    {
        if (empty($accessSecret)) {
            throw new NimbblException(
                'Access secret is required for encryption',
                'INVALID_ACCESS_SECRET',
                null,
                HttpStatusCodes::BAD_REQUEST
            );
        }

        if ($keyIterations < 1) {
            throw new NimbblException(
                'keyIterations must be >= 1',
                ErrorCodes::ENCRYPTION_ERROR,
                null,
                HttpStatusCodes::BAD_REQUEST
            );
        }

        $this->keyIterations = $keyIterations;
        $this->generateKey($accessSecret);
    }

    /**
     * Generate encryption key from access secret using SHA256
     * 
     * Steps:
     * 1. Remove "access_secret_" prefix from access secret
     * 2. Generate SHA256 hash (with optional iterations)
     * 3. Returns 32-byte key suitable for AES-256-GCM
     * 
     * ⚠️ NOTE: For improved security, consider using PBKDF2 instead:
     * Current implementation: iterated hash('sha256', $secret, true) (keyIterations times)
     * Recommended: hash_pbkdf2('sha256', $secret, $salt, 100000)
     * 
     * @param string $accessSecret Access secret
     * @return void
     */
    private function generateKey(string $accessSecret): void
    {
        // Remove "access_secret_" prefix
        $keyString = str_replace('access_secret_', '', $accessSecret);

        // Generate SHA256 hash (with iterations)
        // Note: This iterates the hash itself, not using PBKDF2
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
    public function encrypt($data): string
    {
        $logger = Logger::getInstance();
        try {
            $logger->debug("Encryption::encrypt() called - Input type: " . gettype($data));

            // Convert data to string/bytes
            if (is_array($data)) {
                $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $logger->debug("Encryption::encrypt() - Converted array to JSON, length: " . strlen($data));
            } elseif (!is_string($data)) {
                $data = (string) $data;
                $logger->debug("Encryption::encrypt() - Converted to string, length: " . strlen($data));
            } else {
                $logger->debug("Encryption::encrypt() - Input is string, length: " . strlen($data));
            }

            $plaintext = $data;

            // Check if OpenSSL supports AES-GCM
            if (!in_array('aes-256-gcm', openssl_get_cipher_methods())) {
                throw new NimbblException(
                    'AES-256-GCM cipher is not available. Please ensure OpenSSL extension is installed and supports GCM mode.',
                    'ENCRYPTION_NOT_SUPPORTED',
                    null,
                    HttpStatusCodes::INTERNAL_SERVER_ERROR
                );
            }

            // Generate random nonce (IV)
            $logger->debug("Encryption::encrypt() - Generating random nonce (length: " . self::GCM_NONCE_LENGTH . " bytes)");
            $nonce = openssl_random_pseudo_bytes(self::GCM_NONCE_LENGTH);
            if ($nonce === false) {
                $logger->error("Encryption::encrypt() - Failed to generate random nonce");
                throw new NimbblException(
                    'Failed to generate random nonce',
                    'NONCE_GENERATION_FAILED',
                    null,
                    HttpStatusCodes::INTERNAL_SERVER_ERROR
                );
            }
            $logger->debug("Encryption::encrypt() - Nonce generated successfully");

            // Encrypt with AES-256-GCM
            $logger->debug("Encryption::encrypt() - Encrypting with AES-256-GCM, plaintext length: " . strlen($plaintext));
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
                $error = openssl_error_string();
                $logger->error("Encryption::encrypt() - Encryption failed: " . $error);
                throw new NimbblException(
                    'Encryption failed: ' . $error,
                    'ENCRYPTION_FAILED',
                    null,
                    HttpStatusCodes::INTERNAL_SERVER_ERROR
                );
            }
            $logger->debug("Encryption::encrypt() - Encryption successful, ciphertext length: " . strlen($ciphertext));

            // Verify tag length
            if (strlen($tag) !== self::GCM_TAG_LENGTH) {
                $logger->error("Encryption::encrypt() - Tag length mismatch. Expected: " . self::GCM_TAG_LENGTH . ", Got: " . strlen($tag));
                throw new NimbblException(
                    'Authentication tag length mismatch. Expected ' . self::GCM_TAG_LENGTH . ' bytes, got ' . strlen($tag),
                    'TAG_LENGTH_MISMATCH',
                    null,
                    HttpStatusCodes::INTERNAL_SERVER_ERROR
                );
            }
            $logger->debug("Encryption::encrypt() - Tag verified, length: " . strlen($tag));

            // Concatenate: nonce + ciphertext + tag
            $encryptedData = $nonce . $ciphertext . $tag;
            $logger->debug("Encryption::encrypt() - Concatenated encrypted data, total length: " . strlen($encryptedData) . " bytes");

            // Convert to hex string
            $hexResult = bin2hex($encryptedData);
            $logger->debug("Encryption::encrypt() - Converted to hex string, length: " . strlen($hexResult));
            return $hexResult;
        } catch (NimbblException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new NimbblException(
                'Encryption error: ' . $e->getMessage(),
                'ENCRYPTION_ERROR',
                null,
                HttpStatusCodes::INTERNAL_SERVER_ERROR,
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
    public function decrypt(string $encryptedData, bool $returnAsArray = false)
    {
        $logger = Logger::getInstance();
        try {
            $logger->debug("Encryption::decrypt() called - Input length: " . strlen($encryptedData) . ", returnAsArray: " . ($returnAsArray ? 'true' : 'false'));

            // Convert hex string to bytes
            $logger->debug("Encryption::decrypt() - Converting hex string to bytes");
            $encryptedBytes = hex2bin($encryptedData);
            if ($encryptedBytes === false) {
                $logger->error("Encryption::decrypt() - Invalid hex string provided");
                throw new NimbblException(
                    'Invalid hex string provided for decryption',
                    'INVALID_HEX_STRING',
                    null,
                    HttpStatusCodes::BAD_REQUEST
                );
            }
            $logger->debug("Encryption::decrypt() - Hex conversion successful, bytes length: " . strlen($encryptedBytes));

            // Verify minimum length (nonce + tag = 32 bytes minimum)
            $minLength = self::GCM_NONCE_LENGTH + self::GCM_TAG_LENGTH;
            if (strlen($encryptedBytes) < $minLength) {
                $logger->error("Encryption::decrypt() - Encrypted data too short. Expected: {$minLength}, Got: " . strlen($encryptedBytes));
                throw new NimbblException(
                    'Encrypted data too short. Expected at least ' . $minLength . ' bytes, got ' . strlen($encryptedBytes),
                    'INVALID_ENCRYPTED_DATA',
                    null,
                    HttpStatusCodes::BAD_REQUEST
                );
            }

            // Extract nonce (first 16 bytes)
            $nonce = substr($encryptedBytes, 0, self::GCM_NONCE_LENGTH);
            $logger->debug("Encryption::decrypt() - Extracted nonce, length: " . strlen($nonce));

            // Extract tag (last 16 bytes)
            $tag = substr($encryptedBytes, -self::GCM_TAG_LENGTH);
            $logger->debug("Encryption::decrypt() - Extracted tag, length: " . strlen($tag));

            // Extract ciphertext (middle bytes)
            $ciphertext = substr($encryptedBytes, self::GCM_NONCE_LENGTH, -self::GCM_TAG_LENGTH);
            $logger->debug("Encryption::decrypt() - Extracted ciphertext, length: " . strlen($ciphertext));

            // Check if OpenSSL supports AES-GCM
            if (!in_array('aes-256-gcm', openssl_get_cipher_methods())) {
                throw new NimbblException(
                    'AES-256-GCM cipher is not available. Please ensure OpenSSL extension is installed and supports GCM mode.',
                    'DECRYPTION_NOT_SUPPORTED',
                    null,
                    HttpStatusCodes::INTERNAL_SERVER_ERROR
                );
            }

            // Decrypt with AES-256-GCM
            $logger->debug("Encryption::decrypt() - Decrypting with AES-256-GCM");
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
                $logger->error("Encryption::decrypt() - Decryption failed: " . ($error ?: 'Unknown error'));
                throw new NimbblException(
                    'Decryption failed. The encrypted data may be corrupted or the key is incorrect. ' . ($error ?: ''),
                    'DECRYPTION_FAILED',
                    null,
                    HttpStatusCodes::BAD_REQUEST
                );
            }
            $logger->debug("Encryption::decrypt() - Decryption successful, plaintext length: " . strlen($plaintext));

            // Return as array if requested and data is valid JSON
            if ($returnAsArray) {
                $logger->debug("Encryption::decrypt() - Attempting to decode JSON");
                $decoded = json_decode($plaintext, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $logger->debug("Encryption::decrypt() - JSON decode successful, returning array");
                    $logger->debug("Encryption::decrypt() - Decrypted payload (PII-masked): " . CentralMasker::maskBody($plaintext));
                    return $decoded;
                } else {
                    $logger->debug("Encryption::decrypt() - JSON decode failed: " . json_last_error_msg() . ", returning plaintext");
                }
            }

            $logger->debug("Encryption::decrypt() - Returning plaintext");
            $logger->debug("Encryption::decrypt() - Decrypted payload (PII-masked): " . CentralMasker::maskBody($plaintext));
            return $plaintext;
        } catch (NimbblException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new NimbblException(
                'Decryption error: ' . $e->getMessage(),
                'DECRYPTION_ERROR',
                null,
                HttpStatusCodes::INTERNAL_SERVER_ERROR,
                ['original_exception' => $e->getMessage()],
                $e
            );
        }
    }
}

