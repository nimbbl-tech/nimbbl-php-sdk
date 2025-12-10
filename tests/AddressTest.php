<?php
/**
 * Nimbbl PHP SDK - Addresses API Test
 * 
 * Tests the Addresses API client with real API calls
 * 
 * Usage: php test-addresses.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../example/config.php';
require_once __DIR__ . '/../example/utils/helpers.php';

use Nimbbl\Api\Api;
use Nimbbl\Api\Request;

// Load configuration
$config = loadConfig();

// Initialize Nimbbl API
$api = new Api(
    $config['access_key'],
    $config['access_secret'],
    $config['api_url'],
    $config['api_version']
);

echo "=== Addresses API Test ===\n\n";

// Generate merchant token first
echo "Step 1: Generating merchant token\n";
echo str_repeat('-', 50) . "\n";
$request = new Request();
$merchantToken = $request->generateToken()['token'];

// Create order to get order token
echo "Step 2: Creating an order to get order token\n";
echo str_repeat('-', 50) . "\n";
$order = $api->orders()->createOrder([
    'invoice_id' => 'ADDR_TEST_' . time(),
    'amount_before_tax' => 100,
    'tax' => 0,
    'total_amount' => 100,
    'currency' => 'INR',
    'user' => [
        'email' => 'test@example.com',
        'first_name' => 'Test',
        'last_name' => 'User',
        'mobile_number' => '9876543210',
        'country_code' => '+91'
    ]
], $merchantToken);

$orderToken = $order['token'] ?? null;
if (!$orderToken) {
    echo "✗ Error: Order token not available\n";
    exit(1);
}
echo "✓ Order token obtained\n\n";

// Test 1: List Addresses
echo "Test 1: List Addresses\n";
echo str_repeat('-', 50) . "\n";
try {
    $addresses = $api->addresses()->listAddresses([
        'user_id' => 'test_user_123' // Replace with actual user_id
    ], $orderToken);
    
    if (isset($addresses['items'])) {
        echo "✓ Successfully retrieved " . count($addresses['items']) . " addresses\n";
        if (count($addresses['items']) > 0) {
            echo "  First address ID: " . ($addresses['items'][0]['id'] ?? $addresses['items'][0]['address_id'] ?? 'N/A') . "\n";
        }
    } else {
        echo "✗ Error: " . print_r($addresses, true) . "\n";
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 2: Create Address
echo "Test 2: Create Address\n";
echo str_repeat('-', 50) . "\n";
try {
    $newAddress = $api->addresses()->createAddress([
        'user_id' => 'test_user_123',
        'address_1' => '123 Main Street',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
        'address_type' => 'home'
    ], $orderToken);
    
    if (isset($newAddress['error'])) {
        echo "✗ Error: " . print_r($newAddress['error'], true) . "\n";
    } else {
        echo "✓ Address created successfully\n";
        echo "  Address ID: " . ($newAddress['id'] ?? $newAddress['address_id'] ?? 'N/A') . "\n";
        $createdAddressId = $newAddress['id'] ?? $newAddress['address_id'] ?? null;
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    $createdAddressId = null;
}

echo "\n\n";

// Test 3: Retrieve Address (if we created one)
if (isset($createdAddressId) && $orderToken) {
    echo "Test 3: Retrieve Address by ID\n";
    echo str_repeat('-', 50) . "\n";
    try {
        $address = $api->addresses()->getAddressById($createdAddressId, $orderToken);
        
        if (isset($address['error'])) {
            echo "✗ Error: " . print_r($address['error'], true) . "\n";
        } else {
            echo "✓ Address retrieved successfully\n";
            echo "  Address ID: " . ($address['id'] ?? $address['address_id'] ?? 'N/A') . "\n";
            echo "  City: " . ($address['city'] ?? 'N/A') . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n\n";
    
    // Test 4: Update Address
    echo "Test 4: Update Address\n";
    echo str_repeat('-', 50) . "\n";
    try {
        $updatedAddress = $api->addresses()->updateAddress($createdAddressId, [
            'address_1' => '456 Updated Street',
            'city' => 'Delhi'
        ], $orderToken);
        
        if (isset($updatedAddress['error'])) {
            echo "✗ Error: " . print_r($updatedAddress['error'], true) . "\n";
        } else {
            echo "✓ Address updated successfully\n";
            echo "  Updated City: " . ($updatedAddress['city'] ?? 'N/A') . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n\n";
    
    // Test 5: Check Address Eligibility
    $orderId = $order['nimbbl_order_id'] ?? $order['order_id'] ?? null;
    if ($orderId) {
        echo "Test 5: Check Address Eligibility\n";
        echo str_repeat('-', 50) . "\n";
        try {
            $eligibility = $api->addresses()->checkAddressEligibility([
                'address_id' => $createdAddressId,
                'order_id' => $orderId
            ], $orderToken);
            
            if (isset($eligibility['error'])) {
                echo "✗ Error: " . print_r($eligibility['error'], true) . "\n";
            } else {
                echo "✓ Eligibility check completed\n";
                echo "  Eligible: " . (isset($eligibility['eligible']) ? ($eligibility['eligible'] ? 'Yes' : 'No') : 'N/A') . "\n";
            }
        } catch (Exception $e) {
            echo "✗ Exception: " . $e->getMessage() . "\n";
        }
        
        echo "\n\n";
    }
    
    // Test 6: Delete Address (cleanup)
    echo "Test 6: Delete Address (Cleanup)\n";
    echo str_repeat('-', 50) . "\n";
    try {
        $deleteResult = $api->addresses()->deleteAddress($createdAddressId, $orderToken);
        
        if (isset($deleteResult['error'])) {
            echo "✗ Error: " . print_r($deleteResult['error'], true) . "\n";
        } else {
            echo "✓ Address deleted successfully\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n\n";
}

// Test 7: Import Addresses
// Note: Import requires CSV file upload - skipping in automated test
echo "Test 7: Import Addresses\n";
echo str_repeat('-', 50) . "\n";
echo "Note: Import addresses requires CSV file upload\n";
echo "Please use the addresses-examples.php file for import testing\n";

echo "\n";
echo "=== Addresses API Test Complete ===\n";

