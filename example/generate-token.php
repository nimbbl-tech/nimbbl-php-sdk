#!/usr/bin/env php
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
 * 
 * This file can be:
 * 1. Executed standalone: php generate-token.php
 * 2. Included from cli.php to use the function: generateTokenExample()
 */

// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';
require_once __DIR__ . '/utils/cli_output.php';

use Nimbbl\Api\Common\JsonKeys;

/**
 * Generate Token - Function to be called from cli.php or standalone
 */
function generateTokenExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);

    try {
        echo "Generating authentication token...\n";
        echo str_repeat('-', 50) . "\n";
        echo "Request:\n";
        echo "  access_key: " . $config['access_key'] . "\n";
        echo "  access_secret: " . str_repeat('*', strlen($config['access_secret'])) . "\n";
        echo "\n";

        // Use Auth API client to generate token
        $tokenResponse = $api->auth()->generateToken();

        if (isset($tokenResponse['error'])) {
            printError("Token generation failed: " . print_r($tokenResponse['error'], true) . "\n");
        } else {
            printSuccess("Token generated successfully!\n");
            $token = $tokenResponse[JsonKeys::TOKEN] ?? 'N/A';
            $expiresAt = $tokenResponse[JsonKeys::EXPIRES_AT] ?? 'N/A';

            echo "   Token: " . ($token !== 'N/A' ? $token : 'N/A') . "\n";
            echo "   Expires At: {$expiresAt}\n";

            // Token is automatically cached by SDK in Request::cacheToken()
            printInfo("Token has been cached by SDK and will be used automatically in subsequent API calls.\n");
        }
    } catch (Exception $e) {
        printException($e);
    }
}

// Only run the full example if this file is executed directly (not included)
if (basename($_SERVER['PHP_SELF']) === 'generate-token.php') {
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

    echo Colors::CYAN . Colors::BOLD . "=== Generate Token Example ===" . Colors::RESET . "\n\n";
    echo "This example demonstrates how to generate an authentication token.\n";
    echo "Note: The SDK automatically generates tokens when making API calls.\n";
    echo "This example shows the explicit token generation process.\n\n";

    generateTokenExample();

    echo "\n" . Colors::CYAN . Colors::BOLD . "=== Example Complete ===" . Colors::RESET . "\n";
    echo "\nFor more details, see: https://nimbbl.biz/docs/api-reference/generate-token-v-3/\n";
    echo "\nKey Points:\n";
    echo "  • Token is required for all API calls (except generate-token itself)\n";
    echo "  • Token expires in 20 minutes\n";
    echo "  • SDK automatically generates and refreshes tokens\n";
    echo "  • You can create multiple tokens, each with their own expiry\n";
}

