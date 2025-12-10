<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Checkout Utilities API Examples
 * 
 * This example demonstrates how to use the Checkout Utilities API
 * 
 * API Documentation: https://nimbbl.biz/docs/category/api-reference/checkout-utilities/
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';

use Nimbbl\Api\Api;

// Load configuration
$config = loadConfig();

// Initialize Nimbbl API
$api = initApi($config);

echo "=== Checkout Utilities API Examples ===\n\n";

// Get token from user input
echo "Enter Token: ";
$token = fgets(STDIN);
if (empty($token)) {
    echo "✗ Error: Token is required\n";
    exit(1);
}

// First, create an order for context
echo "Step 1: Creating an order for checkout utilities testing\n";
echo str_repeat('-', 50) . "\n";
try {
    $order = $api->orders()->createOrder([
        'invoice_id' => 'test_checkout_' . time(),
        'amount_before_tax' => 900,
        'tax' => 100,
        'total_amount' => 1000,
        'currency' => 'INR',
        'user' => [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'mobile_number' => '9876543210',
            'country_code' => '+91'
        ]
    ], $token);
    
    if (isset($order->error)) {
        echo "✗ Error creating order: " . print_r($order->error, true) . "\n";
        exit(1);
    }
    
    $orderId = $order->order_id ?? $order->attributes['order_id'] ?? null;
    if (!$orderId) {
        echo "✗ Error: Order ID not found in response\n";
        exit(1);
    }
    
    echo "✓ Order created successfully\n";
    echo "  Order ID: {$orderId}\n";
} catch (Exception $e) {
    echo "✗ Exception creating order: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n\n";

// Example 1: List Payment Modes
echo "Example 1: List Payment Modes\n";
echo str_repeat('-', 50) . "\n";
try {
    $paymentModes = $api->checkoutUtilities()->listPaymentModes([
        'order_id' => $orderId,
        'total_amount' => 1000,
        'currency' => 'INR'
    ], $token);
    
    if (isset($paymentModes['error'])) {
        echo "✗ Error: " . print_r($paymentModes['error'], true) . "\n";
    } else {
        echo "✓ Payment modes retrieved successfully\n";
        if (isset($paymentModes['fast_payment_modes']) || isset($paymentModes['other_payment_modes'])) {
            $fastCount = isset($paymentModes['fast_payment_modes']['items']) ? count($paymentModes['fast_payment_modes']['items']) : 0;
            $otherCount = isset($paymentModes['other_payment_modes']['items']) ? count($paymentModes['other_payment_modes']['items']) : 0;
            $totalCount = $fastCount + $otherCount;
            echo "  Available payment modes: {$totalCount} ({$fastCount} fast, {$otherCount} other)\n";
            
            // Show sample payment modes from fast_payment_modes
            if ($fastCount > 0) {
                $sampleModes = array_slice($paymentModes['fast_payment_modes']['items'], 0, 3);
                foreach ($sampleModes as $mode) {
                    $modeName = $mode['payment_mode'] ?? $mode['name'] ?? 'N/A';
                    echo "    - " . $modeName . "\n";
                }
            }
        } else {
            echo "  No payment modes available\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Example 2: List Banks
echo "Example 2: List Banks (for Netbanking)\n";
echo str_repeat('-', 50) . "\n";
try {
    $banks = $api->checkoutUtilities()->listBanks([
        'order_id' => $orderId
        // Note: If order_id is provided, total_amount and currency are not required
        // If order_id is not provided, use: 'total_amount' => 1000, 'currency' => 'INR'
    ], $token);
    
    if (isset($banks['error'])) {
        echo "✗ Error: " . print_r($banks['error'], true) . "\n";
    } else {
        echo "✓ Banks retrieved successfully\n";
        if (isset($banks['bank_list']) && is_array($banks['bank_list'])) {
            echo "  Available banks: " . count($banks['bank_list']) . "\n";
            foreach (array_slice($banks['bank_list'], 0, 5) as $bank) {
                echo "    - " . ($bank['bank_name'] ?? 'N/A') . " (" . ($bank['code'] ?? 'N/A') . ")\n";
            }
        }
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Example 3: List Wallets
echo "Example 3: List Wallets\n";
echo str_repeat('-', 50) . "\n";
try {
    $wallets = $api->checkoutUtilities()->listWallets([
        'order_id' => $orderId,
        'total_amount' => 1000,
        'currency' => 'INR'
    ], $token);
    
    if (isset($wallets['error'])) {
        echo "✗ Error: " . print_r($wallets['error'], true) . "\n";
    } else {
        echo "✓ Wallets retrieved successfully\n";
        if (isset($wallets['wallets']) && is_array($wallets['wallets'])) {
            echo "  Available wallets: " . count($wallets['wallets']) . "\n";
            foreach (array_slice($wallets['wallets'], 0, 5) as $wallet) {
                echo "    - " . ($wallet['name'] ?? 'N/A') . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Example 4: List EMIs
echo "Example 4: List EMIs\n";
echo str_repeat('-', 50) . "\n";
try {
    $emis = $api->checkoutUtilities()->listEMIs([
        'order_id' => $orderId,
        'total_amount' => 1000,
        'currency' => 'INR'
    ], $token);
    
    if (isset($emis['error'])) {
        echo "✗ Error: " . print_r($emis['error'], true) . "\n";
    } else {
        echo "✓ EMIs retrieved successfully\n";
        if (isset($emis['emi_options']) && is_array($emis['emi_options'])) {
            echo "  Available EMI options: " . count($emis['emi_options']) . "\n";
            foreach (array_slice($emis['emi_options'], 0, 3) as $emi) {
                echo "    - " . ($emi['tenure'] ?? 'N/A') . " months\n";
            }
        }
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Example 5: Get Offers
echo "Example 5: Get Offers\n";
echo str_repeat('-', 50) . "\n";
try {
    $offers = $api->checkoutUtilities()->getOffers([
        'order_id' => $orderId,
        'total_amount' => 1000,
        'currency' => 'INR'
    ], $token);
    
    if (isset($offers['error'])) {
        echo "✗ Error: " . print_r($offers['error'], true) . "\n";
    } else {
        echo "✓ Offers retrieved successfully\n";
        if (isset($offers['offers']) && is_array($offers['offers'])) {
            echo "  Available offers: " . count($offers['offers']) . "\n";
            foreach (array_slice($offers['offers'], 0, 3) as $offer) {
                echo "    - " . ($offer['title'] ?? 'N/A') . "\n";
            }
        } else {
            echo "  No offers available\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Example 6: Get Card BIN Data
echo "Example 6: Get Card BIN Data\n";
echo str_repeat('-', 50) . "\n";
try {
    $binData = $api->checkoutUtilities()->getCardBinData([
        'card_bin' => '411111', // Test BIN (Visa test card)
        'order_id' => $orderId
    ], $token);
    
    if (isset($binData['error'])) {
        echo "✗ Error: " . print_r($binData['error'], true) . "\n";
    } else {
        echo "✓ Card BIN data retrieved successfully\n";
        echo "  Card Type: " . ($binData['card_type'] ?? 'N/A') . "\n";
        echo "  Bank: " . ($binData['bank'] ?? 'N/A') . "\n";
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Example 7: Validate UPI VPA
echo "Example 7: Validate UPI VPA\n";
echo str_repeat('-', 50) . "\n";
try {
    $vpaValidation = $api->checkoutUtilities()->validateUpiVpa([
        'upi_id' => 'test@upi', // Replace with actual UPI ID (API expects 'upi_id', not 'vpa')
        'order_id' => $orderId
    ], $token);
    
    if (isset($vpaValidation['error'])) {
        echo "✗ Error: " . print_r($vpaValidation['error'], true) . "\n";
    } else {
        echo "✓ UPI VPA validation completed\n";
        echo "  Valid: " . (isset($vpaValidation['valid']) ? ($vpaValidation['valid'] ? 'Yes' : 'No') : 'N/A') . "\n";
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Example 8: Get UPI App Details
echo "Example 8: Get UPI App Details\n";
echo str_repeat('-', 50) . "\n";
try {
    $upiAppDetails = $api->checkoutUtilities()->getUpiAppDetails([
        'platform' => 'ios' // or 'android'
    ], $token);
    
    if (isset($upiAppDetails['error'])) {
        echo "✗ Error: " . print_r($upiAppDetails['error'], true) . "\n";
    } else {
        echo "✓ UPI app details retrieved successfully\n";
        if (isset($upiAppDetails['ios']) && is_array($upiAppDetails['ios'])) {
            echo "  Available iOS UPI apps: " . count($upiAppDetails['ios']) . "\n";
            foreach (array_slice($upiAppDetails['ios'], 0, 3) as $app) {
                $appName = $app['upi_app_name'] ?? 'N/A';
                $appCode = $app['upi_app_code'] ?? 'N/A';
                echo "    - {$appName} ({$appCode})\n";
            }
        } elseif (isset($upiAppDetails['android']) && is_array($upiAppDetails['android'])) {
            echo "  Available Android UPI apps: " . count($upiAppDetails['android']) . "\n";
            foreach (array_slice($upiAppDetails['android'], 0, 3) as $app) {
                $appName = $app['upi_app_name'] ?? 'N/A';
                $appCode = $app['upi_app_code'] ?? 'N/A';
                echo "    - {$appName} ({$appCode})\n";
            }
        }
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n";
echo "=== Checkout Utilities API Examples Complete ===\n";
echo "\nFor more information, see: https://nimbbl.biz/docs/category/api-reference/checkout-utilities/\n";

