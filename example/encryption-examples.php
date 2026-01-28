#!/usr/bin/env php
<?php
/**
 * Nimbbl PHP SDK - Encryption/Decryption Examples
 * 
 * This example demonstrates how to use the Encryption utility class
 * for encrypting and decrypting payloads as per Nimbbl API documentation.
 * 
 * This file can be:
 * 1. Executed standalone: php encryption-examples.php
 * 2. Included from cli.php to use the function: runEncryptionExamples()
 * 
 * The encryption utility is primarily used to decrypt responses from
 * Standard Checkout integration when your client forwards the checkout
 * response to your server.
 * 
 * References:
 * - Standard Checkout Integration: https://nimbbl.biz/docs/standard-checkout/completing-integration/
 * - Encryption/Decryption Guide: https://nimbbl.biz/docs/guides/encrypt-decrypt-payload/
 * 
 * Important:
 * - Encrypted payloads are not enabled by default
 * - Please reach out to support@nimbbl.tech if you want this functionality
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';
require_once __DIR__ . '/utils/cli_output.php';

use Nimbbl\Api\Encryption;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\PayloadHelperUtils;
use Nimbbl\Api\Common\SignatureVerifier;
use Nimbbl\Api\Exception\NimbblException;

/**
 * Run Encryption Examples - Function to be called from cli.php or standalone
 */
function runEncryptionExamples()
{
    // Load configuration
    $config = loadConfig();

    echo "=== Nimbbl Encryption/Decryption Examples ===\n\n";

    // Example 1: Basic Encryption and Decryption
    echo "Example 1: Basic Encryption and Decryption\n";
    echo str_repeat('-', 50) . "\n";

    try {
        // Initialize encryption with access secret
        $encryption = new Encryption($config['access_secret']);

        // Data to encrypt (can be string or array)
        $data = [
            JsonKeys::USER_ID => 'user_123',
            JsonKeys::EMAIL => 'user@example.com',
            JsonKeys::MOBILE_NUMBER => '9876543210'
        ];

        echo "Original data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

        // Encrypt the data
        $encrypted = $encryption->encrypt($data);
        echo "Encrypted (hex): " . substr($encrypted, 0, 64) . "...\n";
        echo "Encrypted length: " . strlen($encrypted) . " characters\n\n";

        // Decrypt the data
        $decrypted = $encryption->decrypt($encrypted, true); // true = return as array
        echo "Decrypted data: " . json_encode($decrypted, JSON_PRETTY_PRINT) . "\n";

        // Verify data matches
        if ($data === $decrypted) {
            printSuccess("Encryption/Decryption successful - Data matches!");
        } else {
            printError("Encryption/Decryption failed - Data mismatch!");
        }
    } catch (NimbblException $e) {
        printError("Error: " . $e->getMessage());
        echo "   Error Code: " . $e->getErrorCode() . "\n";
    } catch (Exception $e) {
        printError("Exception: " . $e->getMessage());
    }

    echo "\n\n";

    // Example 2: Encrypting String Data
    echo "Example 2: Encrypting String Data\n";
    echo str_repeat('-', 50) . "\n";

    try {
        $encryption = new Encryption($config['access_secret']);

        $plaintext = "This is a sensitive message that needs to be encrypted.";
        echo "Original: {$plaintext}\n\n";

        $encrypted = $encryption->encrypt($plaintext);
        echo "Encrypted (hex): " . substr($encrypted, 0, 64) . "...\n\n";

        $decrypted = $encryption->decrypt($encrypted);
        echo "Decrypted: {$decrypted}\n";

        if ($plaintext === $decrypted) {
            printSuccess("String encryption/decryption successful!");
        } else {
            printError("String encryption/decryption failed!");
        }
    } catch (NimbblException $e) {
        printError("Error: " . $e->getMessage());
    } catch (Exception $e) {
        printError("Exception: " . $e->getMessage());
    }

    echo "\n\n";

    // Example 3: Decrypting Standard Checkout Response
    echo "Example 3: Decrypting Standard Checkout Response\n";
    echo str_repeat('-', 50) . "\n";
    echo "This is the primary use case - decrypting responses from Standard Checkout\n";
    echo "Reference: https://nimbbl.biz/docs/standard-checkout/completing-integration/\n\n";

    try {
        $encryption = new Encryption($config['access_secret']);

        // Simulate Standard Checkout response from client
        // In real scenario, this would come from your client (web/mobile app)
        $checkoutResponse = [
            'event_type' => 'globalHandleCheckoutResponse',
            'payload' => [
                'encrypted_response' => null // Will be set after encryption for demo
            ]
        ];

        // First, create a sample response that would be encrypted
        $sampleResponse = [
            JsonKeys::STATUS => 'success',
            JsonKeys::MESSAGE => '',
            JsonKeys::NIMBBL_ORDER_ID => 'o_5aezw98nG8KLozP1',
            JsonKeys::NIMBBL_TRANSACTION_ID => 'o_5aezw98nG8KLozP1-230808065657',
            JsonKeys::NIMBBL_SIGNATURE => '7e754ab8859f3ff888545ce73a0848bf131a468e543de5d9872128bc05d085ab',
            JsonKeys::TRANSACTION => [
                JsonKeys::TRANSACTION_ID => 'o_5aezw98nG8KLozP1-230808065657',
                JsonKeys::STATUS => 'succeeded',
                JsonKeys::TRANSACTION_AMOUNT => 12.50,
                JsonKeys::TRANSACTION_TYPE => 'payment',
                JsonKeys::TRANSACTION_CURRENCY => 'INR'
            ],
            JsonKeys::ORDER => [
                JsonKeys::INVOICE_ID => 'INV-12345',
                JsonKeys::STATUS => 'completed'
            ]
        ];

        // Encrypt it (simulating what Nimbbl would do)
        $encryptedResponse = $encryption->encrypt($sampleResponse);
        $checkoutResponse['payload']['encrypted_response'] = $encryptedResponse;

        echo "Received from client (encrypted):\n";
        echo "  event_type: " . $checkoutResponse['event_type'] . "\n";
        echo "  encrypted_response: " . substr($encryptedResponse, 0, 64) . "...\n\n";

        // Decrypt the response on your server using PayloadHelperUtils
        // This handles encryption, unwrapping, and globalHandleCheckoutResponse automatically
        echo "Decrypting on server using PayloadHelperUtils::parseResponse()...\n";
        $decrypted = PayloadHelperUtils::parseResponse(json_encode($checkoutResponse), $config['access_secret']);

        echo "Decrypted response:\n";
        echo "  Status: " . ($decrypted[JsonKeys::STATUS] ?? 'N/A') . "\n";
        echo "  Order ID: " . ($decrypted[JsonKeys::NIMBBL_ORDER_ID] ?? 'N/A') . "\n";
        echo "  Transaction ID: " . ($decrypted[JsonKeys::TRANSACTION][JsonKeys::TRANSACTION_ID] ?? 'N/A') . "\n";
        echo "  Transaction Status: " . ($decrypted[JsonKeys::TRANSACTION][JsonKeys::STATUS] ?? 'N/A') . "\n";
        echo "  Transaction Amount: " . ($decrypted[JsonKeys::TRANSACTION][JsonKeys::TRANSACTION_AMOUNT] ?? 'N/A') . " " . ($decrypted[JsonKeys::TRANSACTION][JsonKeys::TRANSACTION_CURRENCY] ?? 'N/A') . "\n";
        echo "  Order Status: " . ($decrypted[JsonKeys::ORDER][JsonKeys::STATUS] ?? 'N/A') . "\n\n";

        // Now you can validate the signature and process the payment
        echo "Next steps:\n";
        echo "  1. Validate the signature using SignatureVerifier::verifyCallbackSignature()\n";
        echo "  2. Check transaction status ('succeeded', 'failed', or 'pending')\n";
        echo "  3. Process the order accordingly\n";

        // Example: Verify signature
        $verifier = new SignatureVerifier();
        $verifyResult = $verifier->verifyCallbackSignature($decrypted, $config['access_secret']);
        if ($verifyResult['success']) {
            printSuccess("Signature verification successful!");
        } else {
            printError("Signature verification failed: " . ($verifyResult['message'] ?? 'Unknown error'));
        }

        printSuccess("Standard Checkout response decryption successful!");
    } catch (NimbblException $e) {
        printError("Error: " . $e->getMessage());
    } catch (Exception $e) {
        printError("Exception: " . $e->getMessage());
    }

    echo "\n\n";

    // Example 4: Handling Base64-Encoded Callback Responses
    echo "Example 4: Handling Base64-Encoded Callback Responses\n";
    echo str_repeat('-', 50) . "\n";
    echo "This demonstrates how to handle payment callbacks that may be base64-encoded\n";
    echo "PayloadHelperUtils::parseResponse() automatically detects and handles both formats\n\n";

    try {
        // Simulate a base64-encoded callback response (as would come from redirect mode)
        $sampleCallback = [
            JsonKeys::STATUS => 'success',
            JsonKeys::MESSAGE => '',
            JsonKeys::NIMBBL_ORDER_ID => 'o_5aezw98nG8KLozP1',
            JsonKeys::TRANSACTION => [
                JsonKeys::TRANSACTION_ID => 'o_5aezw98nG8KLozP1-230808065657',
                JsonKeys::STATUS => 'succeeded',
                JsonKeys::TRANSACTION_AMOUNT => 12.50,
                JsonKeys::TRANSACTION_TYPE => 'payment',
                JsonKeys::TRANSACTION_CURRENCY => 'INR',
                JsonKeys::SIGNATURE => '7e754ab8859f3ff888545ce73a0848bf131a468e543de5d9872128bc05d085ab',
            ],
            JsonKeys::ORDER => [
                JsonKeys::INVOICE_ID => 'INV-12345',
                JsonKeys::STATUS => 'completed'
            ]
        ];

        $jsonCallback = json_encode($sampleCallback);
        $base64Callback = base64_encode($jsonCallback);

        echo "Base64-encoded callback: " . substr($base64Callback, 0, 64) . "...\n\n";

        // ParseResponse automatically detects base64 and decodes it
        echo "Parsing with PayloadHelperUtils::parseResponse() (auto-detects base64)...\n";
        $parsed = PayloadHelperUtils::parseResponse($base64Callback, $config['access_secret']);

        echo "Parsed response:\n";
        echo "  Status: " . ($parsed[JsonKeys::STATUS] ?? 'N/A') . "\n";
        echo "  Order ID: " . ($parsed[JsonKeys::NIMBBL_ORDER_ID] ?? 'N/A') . "\n";
        echo "  Transaction ID: " . ($parsed[JsonKeys::TRANSACTION][JsonKeys::TRANSACTION_ID] ?? 'N/A') . "\n";
        echo "  Transaction Status: " . ($parsed[JsonKeys::TRANSACTION][JsonKeys::STATUS] ?? 'N/A') . "\n\n";

        // Also works with regular JSON strings
        echo "Parsing regular JSON string (not base64)...\n";
        $parsed2 = PayloadHelperUtils::parseResponse($jsonCallback, $config['access_secret']);
        echo "  Status: " . ($parsed2[JsonKeys::STATUS] ?? 'N/A') . "\n\n";

        printSuccess("Base64 and JSON parsing both work correctly!");
    } catch (NimbblException $e) {
        printError("Error: " . $e->getMessage());
    } catch (Exception $e) {
        printError("Exception: " . $e->getMessage());
    }

    echo "\n\n";

    // Example 5: Error Handling
    echo "Example 4: Error Handling\n";
    echo str_repeat('-', 50) . "\n";

    try {
        $encryption = new Encryption($config['access_secret']);

        // Try to decrypt invalid data
        $invalidHex = "not_a_valid_hex_string";
        $decrypted = $encryption->decrypt($invalidHex);
    } catch (NimbblException $e) {
        printSuccess("Caught expected exception:");
        echo "   Message: " . $e->getMessage() . "\n";
        echo "   Error Code: " . $e->getErrorCode() . "\n";
    } catch (Exception $e) {
        printError("Unexpected exception: " . $e->getMessage());
    }

    echo "\n\n";

    // Example 6: Key Generation Verification
    echo "Example 5: Key Generation Verification\n";
    echo str_repeat('-', 50) . "\n";

    try {
        // Test with different access secret formats
        $testSecrets = [
            'access_secret_a1x7BxYkRpB4p5H',
            'a1x7BxYkRpB4p5H', // Without prefix
        ];

        foreach ($testSecrets as $secret) {
            try {
                $encryption = new Encryption($secret);
                $testData = "test";
                $encrypted = $encryption->encrypt($testData);
                $decrypted = $encryption->decrypt($encrypted);

                if ($testData === $decrypted) {
                    printSuccess("Key generation works with: " . substr($secret, 0, 20) . "...");
                }
            } catch (Exception $e) {
                printError("Failed with: " . substr($secret, 0, 20) . "... - " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        printError("Error: " . $e->getMessage());
    }

    echo "\n";
    echo "=== Examples Complete ===\n";
    echo "\nFor more information:\n";
    echo "  - Standard Checkout Integration: https://nimbbl.biz/docs/standard-checkout/completing-integration/\n";
    echo "  - Encryption/Decryption Guide: https://nimbbl.biz/docs/guides/encrypt-decrypt-payload/\n";
}

// Only run the full example if this file is executed directly (not included)
if (basename($_SERVER['PHP_SELF']) === 'encryption-examples.php') {
    // Validate configuration early
    $config = loadConfig();
    if (empty($config['access_key']) || $config['access_key'] === 'your_access_key_here') {
        printError("Please update example/config.php with your Nimbbl credentials.\n");
        printInfo("Copy config.php.example to config.php and update:\n");
        printInfo("  - access_key\n");
        printInfo("  - access_secret\n");
        printInfo("  - api_url (optional, defaults to UAT)\n");
        exit(1);
    }

    runEncryptionExamples();
}
