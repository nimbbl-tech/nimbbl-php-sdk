<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Exception Handling Examples
 * 
 * This example demonstrates how to handle exceptions from the Nimbbl SDK
 * using the new exception hierarchy.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';

use Nimbbl\Api\Api;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Request;
use Nimbbl\Api\Log\Logger;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Exception\NimbblException;
use Nimbbl\Api\Exception\AuthenticationException;
use Nimbbl\Api\Exception\BadRequestException;
use Nimbbl\Api\Exception\NotFoundException;
use Nimbbl\Api\Exception\RateLimitException;
use Nimbbl\Api\Exception\ServerException;
use Nimbbl\Api\Exception\ApiException;

// Load configuration
$config = loadConfig();

// Initialize logger
$logger = Logger::getInstance($config["log_file"] ?? null);

// Initialize Nimbbl API
$api = initApi($config);

echo "=== Exception Handling Examples ===\n\n";



// Example 1: Basic Exception Handling
echo "Example 1: Basic Exception Handling\n";
echo str_repeat('-', 50) . "\n";
try {
    // This will throw an exception if credentials are invalid
    $order = $api->orders()->createOrder([
        JsonKeys::INVOICE_ID => 'test_' . time(),
        'amount_before_tax' => 900,
        'tax' => 100,
        JsonKeys::TOTAL_AMOUNT => 1000,
        JsonKeys::CURRENCY => 'INR',
        JsonKeys::USER => [
            JsonKeys::EMAIL => 'test@example.com',
            JsonKeys::FIRST_NAME => 'Test',
            JsonKeys::LAST_NAME => 'User',
            JsonKeys::MOBILE_NUMBER => '9876543210',
            JsonKeys::COUNTRY_CODE => '+91'
        ]
    ]);

    echo "[SUCCESS] Order created successfully\n";
} catch (NimbblException $e) {
    echo "[ERROR] Nimbbl Exception caught:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  Error Code: " . ($e->getErrorCode() ?? 'N/A') . "\n";
    echo "  HTTP Status: " . ($e->getHttpStatusCode() ?? 'N/A') . "\n";
    echo "  Request ID: " . ($e->getRequestId() ?? 'N/A') . "\n";
}

echo "\n\n";

// Example 2: Specific Exception Types
echo "Example 2: Handling Specific Exception Types\n";
echo str_repeat('-', 50) . "\n";
try {
    // Try to retrieve a non-existent order
    $order = $api->orders()->getOrderById('non_existent_order_id');
} catch (AuthenticationException $e) {
    echo "[ERROR] Authentication failed (401):\n";
    echo "  " . $e->getMessage() . "\n";
    echo "  → Check your access_key and access_secret\n";
} catch (BadRequestException $e) {
    echo "[ERROR] Bad request (400/422):\n";
    echo "  " . $e->getMessage() . "\n";
    echo "  → Check your request parameters\n";
} catch (NotFoundException $e) {
    echo "[ERROR] Resource not found (404):\n";
    echo "  " . $e->getMessage() . "\n";
    echo "  → The requested resource does not exist\n";
} catch (RateLimitException $e) {
    echo "[ERROR] Rate limit exceeded (429):\n";
    echo "  " . $e->getMessage() . "\n";
    echo "  → Too many requests. Please retry after some time\n";
} catch (ServerException $e) {
    echo "[ERROR] Server error (5xx):\n";
    echo "  " . $e->getMessage() . "\n";
    echo "  → Nimbbl server is experiencing issues. Please retry later\n";
} catch (ApiException $e) {
    echo "[ERROR] API error:\n";
    echo "  " . $e->getMessage() . "\n";
    echo "  Error Code: " . ($e->getErrorCode() ?? 'N/A') . "\n";
} catch (NimbblException $e) {
    echo "[ERROR] General Nimbbl exception:\n";
    echo "  " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "[ERROR] Unexpected exception:\n";
    echo "  " . $e->getMessage() . "\n";
}

echo "\n\n";

// Example 3: Exception Data Access
echo "Example 3: Accessing Exception Data\n";
echo str_repeat('-', 50) . "\n";
try {
    // This will fail with invalid data
    $order = $api->orders()->createOrder([
        JsonKeys::INVOICE_ID => 'test_invalid_' . time(),
        'amount_before_tax' => -90, // Invalid amount (negative)
        'tax' => -10,
        JsonKeys::TOTAL_AMOUNT => -100, // Invalid amount (negative)
        JsonKeys::CURRENCY => 'INR',
        JsonKeys::USER => [
            JsonKeys::EMAIL => 'test@example.com',
            JsonKeys::FIRST_NAME => 'Test',
            JsonKeys::LAST_NAME => 'User',
            JsonKeys::MOBILE_NUMBER => '9876543210',
            JsonKeys::COUNTRY_CODE => '+91'
        ]
    ]);
} catch (BadRequestException $e) {
    echo "[ERROR] Bad Request Exception:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  Error Code: " . ($e->getErrorCode() ?? 'N/A') . "\n";
    echo "  HTTP Status: " . ($e->getHttpStatusCode() ?? 'N/A') . "\n";
    echo "  Request ID: " . ($e->getRequestId() ?? 'N/A') . "\n";

    $errorData = $e->getErrorData();
    if ($errorData) {
        echo "  Error Data: " . json_encode($errorData, JSON_PRETTY_PRINT) . "\n";
    }

    // Convert to array
    $exceptionArray = $e->toArray();
    echo "  Exception Array: " . json_encode($exceptionArray, JSON_PRETTY_PRINT) . "\n";
}

echo "\n\n";

// Example 4: Best Practice - Comprehensive Error Handling
echo "Example 4: Best Practice - Comprehensive Error Handling\n";
echo str_repeat('-', 50) . "\n";

function createOrderSafely($api, $orderData)
{
    try {
        $order = $api->orders()->createOrder($orderData);
        return ['success' => true, 'order' => $order];
    } catch (AuthenticationException $e) {
        // Log authentication error
        Logger::getInstance()->log("Authentication failed: " . $e->getMessage(), Logger::LOG_ERROR, SdkConstants::COMPONENT_REQUEST);
        return [
            'success' => false,
            'error' => 'Authentication failed. Please check your credentials.',
            'type' => 'authentication',
            'http_status' => $e->getHttpStatusCode(),
        ];
    } catch (BadRequestException $e) {
        // Log validation error
        Logger::getInstance()->log("Bad request: " . $e->getMessage(), Logger::LOG_ERROR, SdkConstants::COMPONENT_REQUEST);
        return [
            'success' => false,
            'error' => 'Invalid request. Please check your input data.',
            'type' => 'validation',
            'error_code' => $e->getErrorCode(),
            'http_status' => $e->getHttpStatusCode(),
        ];
    } catch (RateLimitException $e) {
        // Log rate limit error
        Logger::getInstance()->log("Rate limit exceeded: " . $e->getMessage(), Logger::LOG_WARNING, SdkConstants::COMPONENT_REQUEST);
        return [
            'success' => false,
            'error' => 'Too many requests. Please try again later.',
            'type' => 'rate_limit',
            'http_status' => $e->getHttpStatusCode(),
        ];
    } catch (ServerException $e) {
        // Log server error
        Logger::getInstance()->log("Server error: " . $e->getMessage(), Logger::LOG_ERROR, SdkConstants::COMPONENT_REQUEST);
        return [
            'success' => false,
            'error' => 'Server error. Please try again later.',
            'type' => 'server_error',
            'http_status' => $e->getHttpStatusCode(),
        ];
    } catch (NimbblException $e) {
        // Log general Nimbbl error
        Logger::getInstance()->log("Nimbbl error: " . $e->getMessage(), Logger::LOG_ERROR, SdkConstants::COMPONENT_REQUEST);
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'type' => 'nimbbl_error',
            'error_code' => $e->getErrorCode(),
            'http_status' => $e->getHttpStatusCode(),
        ];
    } catch (\Exception $e) {
        // Log unexpected error
        Logger::getInstance()->log("Unexpected error: " . $e->getMessage(), Logger::LOG_ERROR, SdkConstants::COMPONENT_REQUEST);
        return [
            'success' => false,
            'error' => 'An unexpected error occurred.',
            'type' => 'unexpected',
        ];
    }
}

$result = createOrderSafely($api, [
    JsonKeys::INVOICE_ID => 'test_' . time(),
    'amount_before_tax' => 900,
    'tax' => 100,
    JsonKeys::TOTAL_AMOUNT => 1000,
    JsonKeys::CURRENCY => 'INR',
    JsonKeys::USER => [
        JsonKeys::EMAIL => 'test@example.com',
        JsonKeys::FIRST_NAME => 'Test',
        JsonKeys::LAST_NAME => 'User',
        JsonKeys::MOBILE_NUMBER => '9876543210',
        JsonKeys::COUNTRY_CODE => '+91'
    ]
]);

if ($result['success']) {
    echo "[SUCCESS] Order created successfully\n";
} else {
    echo "[ERROR] Order creation failed:\n";
    echo "  Type: " . $result['type'] . "\n";
    echo "  Error: " . $result['error'] . "\n";
    if (isset($result['http_status'])) {
        echo "  HTTP Status: " . $result['http_status'] . "\n";
    }
}

echo "\n";
echo "=== Exception Handling Examples Complete ===\n";
echo "\nKey Points:\n";
echo "  • Use specific exception types for better error handling\n";
echo "  • Always catch NimbblException or more specific types\n";
echo "  • Access error details via getErrorCode(), getRequestId(), etc.\n";
echo "  • Use toArray() for easy serialization\n";
echo "  • Log exceptions appropriately for debugging\n";
echo "  • Provide user-friendly error messages\n";

