<?php
/**
 * Nimbbl PHP SDK - Generate Token Example
 * 
 * This example demonstrates how to generate an authentication token using the Nimbbl PHP SDK
 * 
 * API Documentation: https://nimbbl.biz/docs/api-reference/generate-token-v-3/
 * 
 * Note: The SDK automatically generates tokens internally when making API calls.
 * This example shows how to generate a token explicitly if needed.
 */

// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';

use Nimbbl\Api\Api;
use Nimbbl\Api\Request;

// Load configuration
$config = loadConfig();

echo "=== Generate Token Example ===\n\n";
echo "This example demonstrates how to generate an authentication token.\n";
echo "Note: The SDK automatically generates tokens when making API calls.\n";
echo "This example shows the explicit token generation process.\n\n";

try {
    // Initialize Api to set the access_key and access_secret
    // These are stored as static properties and used by generateToken()
    $api = initApi($config);
    
    echo "Generating authentication token...\n";
    echo str_repeat('-', 50) . "\n";
    echo "Request Body:\n";
    echo "  access_key: " . $config['access_key'] . "\n";
    echo "  access_secret: " . str_repeat('*', strlen($config['access_secret'])) . "\n";
    echo "\n";
    
    // Create a NimbblRequest instance to access generateToken method
    $request = new Request();
    
    // Generate token
    // The generateToken() method uses access_key and access_secret from NimbblApi static properties
    // which were set when we initialized NimbblApi above
    $tokenResponse = $request->generateToken();
    
    if (isset($tokenResponse['error'])) {
        echo "❌ Error: " . print_r($tokenResponse['error'], true) . "\n";
    } else {
        echo "✅ Token generated successfully!\n";
        echo "   Token: " . ($tokenResponse['token'] ?? 'N/A') . "\n";
        
        if (isset($tokenResponse['expires_at'])) {
            echo "   Expires At: " . ($tokenResponse['expires_at'] ?? 'N/A') . "\n";
        }
        
        if (isset($tokenResponse['expires_in'])) {
            echo "   Expires In: " . ($tokenResponse['expires_in'] ?? 'N/A') . " seconds\n";
        }
        
        echo "\n";
        echo "   Note: Token expires in 20 minutes. You can create multiple tokens,\n";
        echo "   each with their own expiry. The SDK automatically refreshes tokens\n";
        echo "   when making API calls.\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";
echo "=== Example Complete ===\n";
echo "\nFor more details, see: https://nimbbl.biz/docs/api-reference/generate-token-v-3/\n";
echo "\nKey Points:\n";
echo "  • Token is required for all API calls (except generate-token itself)\n";
echo "  • Token expires in 20 minutes\n";
echo "  • SDK automatically generates and refreshes tokens\n";
echo "  • You can create multiple tokens, each with their own expiry\n";

