<?php
/**
 * Nimbbl PHP SDK - Encryption/Decryption Examples
 * 
 * This example demonstrates how to use the Encryption utility class
 * for encrypting and decrypting payloads as per Nimbbl API documentation.
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

use Nimbbl\Api\Api;
use Nimbbl\Api\Encryption;
use Nimbbl\Api\Exception\NimbblException;

// Load configuration
$config = loadConfig();

// Initialize Nimbbl API
$api = initApi($config);

echo "=== Nimbbl Encryption/Decryption Examples ===\n\n";

// Example 1: Basic Encryption and Decryption
echo "Example 1: Basic Encryption and Decryption\n";
echo str_repeat('-', 50) . "\n";

try {
    // Initialize encryption with access secret
    $encryption = new Encryption($config['access_secret']);
    
    // Data to encrypt (can be string or array)
    $data = [
        'user_id' => 'user_123',
        'email' => 'user@example.com',
        'mobile_number' => '9876543210'
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
        echo "✅ Encryption/Decryption successful - Data matches!\n";
    } else {
        echo "❌ Encryption/Decryption failed - Data mismatch!\n";
    }
} catch (NimbblException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Error Code: " . $e->getErrorCode() . "\n";
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
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
        echo "✅ String encryption/decryption successful!\n";
    } else {
        echo "❌ String encryption/decryption failed!\n";
    }
} catch (NimbblException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
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
        'status' => 'success',
        'message' => '',
        'nimbbl_order_id' => 'o_5aezw98nG8KLozP1',
        'nimbbl_transaction_id' => 'o_5aezw98nG8KLozP1-230808065657',
        'nimbbl_signature' => '7e754ab8859f3ff888545ce73a0848bf131a468e543de5d9872128bc05d085ab',
        'transaction' => [
            'transaction_id' => 'o_5aezw98nG8KLozP1-230808065657',
            'status' => 'succeeded',
            'transaction_amount' => 12.50,
            'transaction_type' => 'payment',
            'transaction_currency' => 'INR'
        ],
        'order' => [
            'invoice_id' => 'INV-12345',
            'status' => 'completed'
        ]
    ];
    
    // Encrypt it (simulating what Nimbbl would do)
    $encryptedResponse = $encryption->encrypt($sampleResponse);
    $checkoutResponse['payload']['encrypted_response'] = $encryptedResponse;
    
    echo "Received from client (encrypted):\n";
    echo "  event_type: " . $checkoutResponse['event_type'] . "\n";
    echo "  encrypted_response: " . substr($encryptedResponse, 0, 64) . "...\n\n";
    
    // Decrypt the response on your server
    echo "Decrypting on server...\n";
    $decrypted = $encryption->decrypt($checkoutResponse['payload']['encrypted_response'], true);
    
    echo "Decrypted response:\n";
    echo "  Status: " . $decrypted['status'] . "\n";
    echo "  Order ID: " . $decrypted['nimbbl_order_id'] . "\n";
    echo "  Transaction ID: " . $decrypted['nimbbl_transaction_id'] . "\n";
    echo "  Transaction Status: " . $decrypted['transaction']['status'] . "\n";
    echo "  Transaction Amount: " . $decrypted['transaction']['transaction_amount'] . " " . $decrypted['transaction']['transaction_currency'] . "\n";
    echo "  Order Status: " . $decrypted['order']['status'] . "\n\n";
    
    // Now you can validate the signature and process the payment
    echo "Next steps:\n";
    echo "  1. Validate the signature using (new Util())->verifyPaymentSignature()\n";
    echo "  2. Check transaction status ('succeeded', 'failed', or 'pending')\n";
    echo "  3. Process the order accordingly\n";
    
    echo "✅ Standard Checkout response decryption successful!\n";
} catch (NimbblException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Example 4: Error Handling
echo "Example 4: Error Handling\n";
echo str_repeat('-', 50) . "\n";

try {
    $encryption = new Encryption($config['access_secret']);
    
    // Try to decrypt invalid data
    $invalidHex = "not_a_valid_hex_string";
    $decrypted = $encryption->decrypt($invalidHex);
} catch (NimbblException $e) {
    echo "✅ Caught expected exception:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   Error Code: " . $e->getErrorCode() . "\n";
} catch (Exception $e) {
    echo "❌ Unexpected exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Example 5: Key Generation Verification
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
                echo "✅ Key generation works with: " . substr($secret, 0, 20) . "...\n";
            }
        } catch (Exception $e) {
            echo "❌ Failed with: " . substr($secret, 0, 20) . "... - " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "=== Examples Complete ===\n";
echo "\nFor more information:\n";
echo "  - Standard Checkout Integration: https://nimbbl.biz/docs/standard-checkout/completing-integration/\n";
echo "  - Encryption/Decryption Guide: https://nimbbl.biz/docs/guides/encrypt-decrypt-payload/\n";

