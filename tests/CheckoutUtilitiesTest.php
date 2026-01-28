<?php
/**
 * Nimbbl PHP SDK - Checkout Utilities API Test
 * 
 * Tests the Checkout Utilities API client with real API calls
 * 
 * Usage: php test-checkout-utilities.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../example/config.php';
require_once __DIR__ . '/../example/utils/helpers.php';

use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\RestClient\Request;

// Load configuration
$config = loadConfig();

// Initialize Nimbbl API
$api = new NimbblClient(
    $config['access_key'],
    $config['access_secret'],
    $config['api_url'],
    $config['api_version']
);

echo "=== Checkout Utilities API Test ===\n\n";

// Generate merchant token first
echo "Step 1: Generating merchant token\n";
echo str_repeat('-', 50) . "\n";
$request = new Request();
$merchantToken = $request->generateToken()['token'];
echo "[SUCCESS] Merchant token generated\n\n";

// First, create an order for context
echo "Step 2: Creating an order for checkout utilities testing\n";
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
    ], $merchantToken);

    if (isset($order['error'])) {
        echo "[ERROR] Error creating order: " . print_r($order['error'], true) . "\n";
        exit(1);
    }

    $orderId = $order['nimbbl_order_id'] ?? $order['order_id'] ?? null;
    $orderToken = $order['token'] ?? null;

    if (!$orderId) {
        echo "[ERROR] Error: Order ID not found in response\n";
        exit(1);
    }

    if (!$orderToken) {
        echo "[ERROR] Error: Order token not found in response\n";
        exit(1);
    }

    echo "[SUCCESS] Order created successfully\n";
    echo "  Order ID: {$orderId}\n";
    echo "  Order Token: " . substr($orderToken, 0, 20) . "...\n";
} catch (Exception $e) {
    echo "[ERROR] Exception creating order: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n\n";

// Test 1: List Payment Modes
echo "Test 1: List Payment Modes\n";
echo str_repeat('-', 50) . "\n";
try {
    $paymentModes = $api->checkoutUtilities()->listPaymentModes([
        'order_id' => $orderId
    ], $orderToken);

    if (isset($paymentModes['error'])) {
        echo "[ERROR] Error: " . print_r($paymentModes['error'], true) . "\n";
    } else {
        echo "[SUCCESS] Payment modes retrieved successfully\n";
        if (isset($paymentModes['payment_modes']) && is_array($paymentModes['payment_modes'])) {
            echo "  Available payment modes: " . count($paymentModes['payment_modes']) . "\n";
            foreach (array_slice($paymentModes['payment_modes'], 0, 3) as $mode) {
                echo "    - " . ($mode['name'] ?? $mode['payment_mode_code'] ?? 'N/A') . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "[ERROR] Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 2: List Banks
echo "Test 2: List Banks (for Netbanking)\n";
echo str_repeat('-', 50) . "\n";
try {
    $banks = $api->checkoutUtilities()->listBanks([
        'order_id' => $orderId
    ], $orderToken);

    if (isset($banks['error'])) {
        echo "[ERROR] Error: " . print_r($banks['error'], true) . "\n";
    } else {
        echo "[SUCCESS] Banks retrieved successfully\n";
        if (isset($banks['bank_list']) && is_array($banks['bank_list'])) {
            echo "  Available banks: " . count($banks['bank_list']) . "\n";
            foreach (array_slice($banks['bank_list'], 0, 5) as $bank) {
                echo "    - " . ($bank['bank_name'] ?? 'N/A') . " (" . ($bank['code'] ?? 'N/A') . ")\n";
            }
        }
    }
} catch (Exception $e) {
    echo "[ERROR] Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 3: List Wallets
echo "Test 3: List Wallets\n";
echo str_repeat('-', 50) . "\n";
try {
    $wallets = $api->checkoutUtilities()->listWallets([
        'order_id' => $orderId
    ], $orderToken);

    if (isset($wallets['error'])) {
        echo "[ERROR] Error: " . print_r($wallets['error'], true) . "\n";
    } else {
        echo "[SUCCESS] Wallets retrieved successfully\n";
        if (isset($wallets['wallets']) && is_array($wallets['wallets'])) {
            echo "  Available wallets: " . count($wallets['wallets']) . "\n";
            foreach (array_slice($wallets['wallets'], 0, 5) as $wallet) {
                echo "    - " . ($wallet['name'] ?? $wallet['wallet_code'] ?? 'N/A') . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "[ERROR] Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 4: List EMIs
echo "Test 4: List EMIs\n";
echo str_repeat('-', 50) . "\n";
try {
    $emis = $api->checkoutUtilities()->listEMIs([
        'order_id' => $orderId
    ], $orderToken);

    if (isset($emis['error'])) {
        echo "[ERROR] Error: " . print_r($emis['error'], true) . "\n";
    } else {
        echo "[SUCCESS] EMIs retrieved successfully\n";
        if (isset($emis['emi_options']) && is_array($emis['emi_options'])) {
            echo "  Available EMI options: " . count($emis['emi_options']) . "\n";
            foreach (array_slice($emis['emi_options'], 0, 3) as $emi) {
                echo "    - " . ($emi['tenure'] ?? 'N/A') . " months\n";
            }
        }
    }
} catch (Exception $e) {
    echo "[ERROR] Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 5: Get Offers
echo "Test 5: Get Offers\n";
echo str_repeat('-', 50) . "\n";
try {
    $offers = $api->checkoutUtilities()->getOffers([
        'order_id' => $orderId,
        'payment_mode_code' => 'all' // Required field
    ], $orderToken);

    if (isset($offers['error'])) {
        echo "[ERROR] Error: " . print_r($offers['error'], true) . "\n";
    } else {
        echo "[SUCCESS] Offers retrieved successfully\n";
        if (isset($offers['offers']) && is_array($offers['offers'])) {
            echo "  Available offers: " . count($offers['offers']) . "\n";
            foreach (array_slice($offers['offers'], 0, 3) as $offer) {
                echo "    - " . ($offer['title'] ?? $offer['offer_id'] ?? 'N/A') . "\n";
            }
        } else {
            echo "  No offers available\n";
        }
    }
} catch (Exception $e) {
    echo "[ERROR] Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 6: Get Card BIN Data
echo "Test 6: Get Card BIN Data\n";
echo str_repeat('-', 50) . "\n";
try {
    $binData = $api->checkoutUtilities()->getCardBinData([
        'card_bin' => '411111', // Test BIN (Visa test card)
        'order_id' => $orderId // Optional
    ], $orderToken);

    if (isset($binData['error'])) {
        echo "[ERROR] Error: " . print_r($binData['error'], true) . "\n";
    } else {
        echo "[SUCCESS] Card BIN data retrieved successfully\n";
        echo "  Card Type: " . ($binData['card_type'] ?? 'N/A') . "\n";
        echo "  Bank: " . ($binData['bank'] ?? 'N/A') . "\n";
    }
} catch (Exception $e) {
    echo "[ERROR] Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 7: Validate UPI VPA
echo "Test 7: Validate UPI VPA\n";
echo str_repeat('-', 50) . "\n";
try {
    $vpaValidation = $api->checkoutUtilities()->validateUpiVpa([
        'upi_id' => 'test@paytm' // Replace with actual UPI ID (API expects 'upi_id', not 'vpa')
    ], $orderToken);

    if (isset($vpaValidation['error'])) {
        echo "[ERROR] Error: " . print_r($vpaValidation['error'], true) . "\n";
    } else {
        echo "[SUCCESS] UPI VPA validation completed\n";
        echo "  Valid: " . (isset($vpaValidation['valid']) ? ($vpaValidation['valid'] ? 'Yes' : 'No') : 'N/A') . "\n";
    }
} catch (Exception $e) {
    echo "[ERROR] Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 8: Get UPI App Details
// Note: This API is not available (returns HTTP 500)
echo "Test 8: Get UPI App Details\n";
echo str_repeat('-', 50) . "\n";
echo "Note: This API is currently not available\n";
echo "Skipping test...\n";
// try {
//     $upiAppDetails = $api->checkoutUtilities()->getUpiAppDetails([
//         'platform' => 'android' // or 'ios'
//     ], $orderToken);
//     
//     if (isset($upiAppDetails['error'])) {
//         echo "[ERROR] Error: " . print_r($upiAppDetails['error'], true) . "\n";
//     } else {
//         echo "[SUCCESS] UPI app details retrieved successfully\n";
//     }
// } catch (Exception $e) {
//     echo "[ERROR] Exception: " . $e->getMessage() . "\n";
// }

echo "\n";
echo "=== Checkout Utilities API Test Complete ===\n";

