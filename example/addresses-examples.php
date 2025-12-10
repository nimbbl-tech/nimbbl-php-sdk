<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Addresses API Examples
 * 
 * This example demonstrates how to use the Addresses API
 * 
 * API Documentation: https://nimbbl.biz/docs/category/api-reference/addresses/
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';

use Nimbbl\Api\Api;

// Load configuration
$config = loadConfig();

// Initialize Nimbbl API
$api = new Api(
    $config['access_key'],
    $config['access_secret'],
    $config['api_endpoint'],
    null,
    null,
    $config['log_file'] ?? null
);

echo "=== Addresses API Examples ===\n\n";

// Get token from user input
echo "Enter Token: ";
$token = fgets(STDIN);
if (empty($token)) {
    echo "✗ Error: Token is required\n";
    exit(1);
}

// Example 1: List Addresses
echo "Example 1: List Addresses\n";
echo str_repeat('-', 50) . "\n";
try {
    $addresses = $api->addresses()->listAddresses([
        'user_id' => 'test_user_123', // Replace with actual user_id
        'amount' => 1000,
        'currency' => 'INR'
    ], $token);
    
    if (isset($addresses['items'])) {
        echo "✓ Successfully retrieved " . count($addresses['items']) . " addresses\n";
        if (count($addresses['items']) > 0) {
            $firstAddress = $addresses['items'][0];
            echo "  First address ID: " . ($firstAddress['id'] ?? 'N/A') . "\n";
            echo "  City: " . ($firstAddress['city'] ?? 'N/A') . "\n";
        }
    } else {
        echo "✗ Error: " . print_r($addresses, true) . "\n";
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Example 2: Create Address
echo "Example 2: Create Address\n";
echo str_repeat('-', 50) . "\n";
try {
    // According to API documentation: https://nimbbl.biz/docs/api-reference/create-an-address-v-3/
    // The request body must have an 'addresses' array with required fields
    $newAddress = $api->addresses()->createAddress([
        'user_id' => 'test_user_123', // Optional
        'addresses' => [
            [
                'first_name' => 'John', // Required
                'last_name' => 'Doe', // Required
                'address_1' => '123 Main Street', // Required
                'area' => 'Andheri West', // Required
                'city' => 'Mumbai', // Required
                'state' => 'Maharashtra', // Required
                'pincode' => '400001', // Required
                'address_type' => 'home', // Required
                'street' => 'MG Road', // Optional
                'landmark' => 'Near Metro Station', // Optional
                'label' => 'Home Address', // Optional
                'country' => 'India', // Optional
                'link_as' => 'shipping' // Optional: 'shipping' or 'billing'
            ]
        ]
        // Optional: amount and currency for shipping calculation (should be provided together)
        // 'amount' => 1000,
        // 'currency' => 'INR'
    ], $token);
    
    if (isset($newAddress['error'])) {
        echo "✗ Error: " . print_r($newAddress['error'], true) . "\n";
    } else {
        echo "✓ Address created successfully\n";
        // Response is an array of address objects
        if (isset($newAddress[0]['address']['address_id'])) {
            $createdAddressId = $newAddress[0]['address']['address_id'];
            echo "  Address ID: " . $createdAddressId . "\n";
            echo "  First Name: " . ($newAddress[0]['address']['first_name'] ?? 'N/A') . "\n";
            echo "  City: " . ($newAddress[0]['address']['city'] ?? 'N/A') . "\n";
        } else {
            echo "  Response: " . print_r($newAddress, true) . "\n";
            $createdAddressId = null;
        }
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    $createdAddressId = null;
}

echo "\n\n";

// Example 3: Retrieve Address
if (isset($createdAddressId)) {
    echo "Example 3: Retrieve Address by ID\n";
    echo str_repeat('-', 50) . "\n";
    try {
        $address = $api->addresses()->getAddressById($createdAddressId, $token);
        
        if (isset($address['error'])) {
            echo "✗ Error: " . print_r($address['error'], true) . "\n";
        } else {
            echo "✓ Address retrieved successfully\n";
            echo "  Address ID: " . ($address['address_id'] ?? $address['id'] ?? 'N/A') . "\n";
            echo "  First Name: " . ($address['first_name'] ?? 'N/A') . "\n";
            echo "  Last Name: " . ($address['last_name'] ?? 'N/A') . "\n";
            echo "  City: " . ($address['city'] ?? 'N/A') . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n\n";
    
    // Example 4: Update Address
    echo "Example 4: Update Address\n";
    echo str_repeat('-', 50) . "\n";
    try {
        $updatedAddress = $api->addresses()->updateAddress($createdAddressId, [
            'first_name' => 'John Updated',
            'last_name' => 'Doe Updated',
            'address_1' => '456 Updated Street'
        ], $token);
        
        if (isset($updatedAddress['error'])) {
            echo "✗ Error: " . print_r($updatedAddress['error'], true) . "\n";
        } else {
            echo "✓ Address updated successfully\n";
            echo "  Updated First Name: " . ($updatedAddress['first_name'] ?? 'N/A') . "\n";
            echo "  Updated Last Name: " . ($updatedAddress['last_name'] ?? 'N/A') . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n\n";
    
    // Example 5: Check Address Eligibility (using pincode)
    echo "Example 5: Check Address Eligibility (using pincode)\n";
    echo str_repeat('-', 50) . "\n";
    try {
        $eligibility = $api->addresses()->checkAddressEligibility([
            'pincode' => '400001', // Required: pincode for eligibility check
            'country_code' => 'IND', // Optional: ISO3 country code (default: IND)
            'amount' => 1000, // Optional: order amount to calculate shipping charges
            'currency' => 'INR' // Optional: currency code
        ], $token);
        
        if (isset($eligibility['error'])) {
            echo "✗ Error: " . print_r($eligibility['error'], true) . "\n";
        } else {
            echo "✓ Eligibility check completed\n";
            echo "  Eligible for Shipping: " . (isset($eligibility['is_eligible_for_shipping']) ? ($eligibility['is_eligible_for_shipping'] ? 'Yes' : 'No') : 'N/A') . "\n";
            if (isset($eligibility['max_shipping_charges'])) {
                echo "  Max Shipping Charges: " . $eligibility['max_shipping_charges'] . "\n";
            }
            if (isset($eligibility['pincode_details'])) {
                $details = $eligibility['pincode_details'];
                echo "  City: " . ($details['city'] ?? 'N/A') . "\n";
                echo "  State: " . ($details['state'] ?? 'N/A') . "\n";
                echo "  Country Code: " . ($details['country_code'] ?? 'N/A') . "\n";
            }
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n\n";
    
    // Example 6: Link Address with Order
    echo "Example 6: Link Address with Order\n";
    echo str_repeat('-', 50) . "\n";
    echo "Note: This requires an order_id. Creating a test order first...\n";
    try {
        // First create an order
        $testOrder = $api->orders()->createOrder([
            'invoice_id' => 'test_link_' . time(),
            'amount_before_tax' => 100,
            'tax' => 10,
            'total_amount' => 110,
            'currency' => 'INR',
            'user' => [
                'email' => 'test@example.com',
                'first_name' => 'Test',
                'last_name' => 'User',
                'mobile_number' => '9876543210',
                'country_code' => '+91'
            ]
        ], $token);
        
        if (isset($testOrder['error'])) {
            echo "✗ Error creating test order: " . print_r($testOrder['error'], true) . "\n";
        } else {
            $testOrderId = $testOrder['order_id'] ?? $testOrder['nimbbl_order_id'] ?? null;
            if ($testOrderId) {
                // Link address with order
                $linkResult = $api->addresses()->linkAddressWithOrder([
                    'order_id' => $testOrderId,
                    'address' => [
                        'address_id' => $createdAddressId // Use saved address
                    ],
                    'link_as' => 'shipping' // or 'billing'
                ], $token);
                
                if (isset($linkResult['error'])) {
                    echo "✗ Error: " . print_r($linkResult['error'], true) . "\n";
                } else {
                    echo "✓ Address linked with order successfully\n";
                    echo "  Order ID: {$testOrderId}\n";
                    echo "  Address ID: {$createdAddressId}\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n\n";
}

// Example 7: Import Addresses (using provider/command pattern)
echo "Example 7: Import Addresses (using provider/command pattern)\n";
echo str_repeat('-', 50) . "\n";
echo "Note: Import addresses uses a two-step process: 'auth' then 'verify'\n";
try {
    // Step 1: Auth command
    echo "Step 1: Auth command\n";
    $authResult = $api->addresses()->importAddresses([
        'provider' => 'shiprocket', // Provider name (e.g., 'shiprocket')
        'command' => 'auth' // First step: authentication
    ], $token);
    
    if (isset($authResult['error'])) {
        echo "✗ Error in auth: " . print_r($authResult['error'], true) . "\n";
        echo "  (This is expected if provider is not configured)\n";
    } else {
        echo "✓ Auth command successful\n";
        echo "  Provider: shiprocket\n";
        echo "  Command: auth\n";
        echo "\n  Note: After successful auth, you would receive an OTP.\n";
        echo "  Then call verify command with the OTP.\n";
        
        // Step 2: Verify command (commented out as it requires actual OTP)
        echo "\nStep 2: Verify command (example - requires actual OTP)\n";
        echo "  To complete import, call:\n";
        echo "  \$api->addresses()->importAddresses([\n";
        echo "      'provider' => 'shiprocket',\n";
        echo "      'command' => 'verify',\n";
        echo "      'otp' => '123456' // Actual OTP received\n";
        echo "  ]);\n";
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    echo "  (This is expected if provider is not configured)\n";
}

echo "\n";
echo "=== Addresses API Examples Complete ===\n";
echo "\nFor more information, see: https://nimbbl.biz/docs/category/api-reference/addresses/\n";

