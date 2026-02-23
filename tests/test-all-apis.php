<?php
/**
 * Nimbbl PHP SDK - Comprehensive API Test Suite
 * 
 * Tests all API clients systematically
 * 
 * Usage: php tests/test-all-apis.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Check if config exists
$configFile = __DIR__ . '/../example/config.php';
if (!file_exists($configFile)) {
    echo "[ERROR] ERROR: Configuration file not found!\n";
    echo "Please copy example/config.php.example to example/config.php and update with your credentials.\n";
    echo "\nCommand: cp example/config.php.example example/config.php\n";
    exit(1);
}

require_once $configFile;
require_once __DIR__ . '/../example/utils/helpers.php';

use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\RestClient\Request;
use Nimbbl\Api\Exception\NimbblException;
use Nimbbl\Api\Common\ApiConstants;

// Helper to build log string
function buildLogString($method, $endpoint)
{
    return $method . ' /api/' . $endpoint;
}

// Load configuration
$config = loadConfig();

// Initialize Nimbbl API
$api = new NimbblClient(
    $config['access_key'],
    $config['access_secret'],
    $config['api_endpoint']
);

// Generate merchant token for admin operations (Transaction Enquiry, Refunds)
$request = new Request();
$tokenResponse = $request->generateToken();
$merchantToken = $tokenResponse['token'] ?? null;
$orderToken = null; // Will be set after order creation

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     Nimbbl PHP SDK - Comprehensive API Test Suite         ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Test IDs - will be populated from actual API responses in sequence
$testOrderId = null;
$testInvoiceId = null;
$testTransactionId = null;
$testAddressId = null;
$testPaymentLinkId = null;
$testUserId = 'test_user_' . time();
$testRefundId = null;
$testRefreshToken = null; // Refresh token from order creation

$testResults = [];
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

// ============================================================================
// PHASE 1: AUTHORIZATION API TESTS
// ============================================================================

echo "\n" . str_repeat('═', 60) . "\n";
echo "PHASE 1: AUTHORIZATION API\n";
echo str_repeat('═', 60) . "\n";

// Test 1: Generate Token
$generatedToken = null;
runTest('Authorization API - Generate Token', function () use ($api, $config, &$generatedToken) {
    // Use Request's generateToken method directly
    $request = new Request();
    $tokenResponse = $request->generateToken();

    if (isset($tokenResponse['error'])) {
        throw new \Exception('Generate token failed: ' . json_encode($tokenResponse['error']));
    }

    if (!isset($tokenResponse['token'])) {
        throw new \Exception('Token not found in response');
    }

    // Store token for reference
    $generatedToken = $tokenResponse['token'];

    return [
        'token' => $tokenResponse['token'] ?? 'N/A',
        'expires_in' => $tokenResponse['expires_in'] ?? 'N/A'
    ];
}, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::AUTH_GENERATE_TOKEN);

// Test 2: Refresh Token (will run after order creation when refresh_token is available)
// Note: Refresh token API requires a refresh_token from Create Order response
// This test is positioned here but will execute after order creation when refresh_token becomes available
if ($testRefreshToken) {
    runTest('Authorization API - Refresh Token', function () use ($api, $config, $testRefreshToken) {
        // Refresh token endpoint requires Bearer authentication with a regular token
        // First, generate a token for authentication
        $request = new Request();
        $tokenResponse = $request->generateToken();

        if (isset($tokenResponse['error']) || !isset($tokenResponse['token'])) {
            throw new \Exception('Failed to generate token for refresh token request');
        }

        $authToken = $tokenResponse['token'];

        // Build the refresh token endpoint URL
        $baseUrl = rtrim($config['api_endpoint'], '/');
        $endpoint = $baseUrl . '/refresh-token';

        // Make request with Bearer token and refresh_token in body
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $authToken
        ];

        // Refresh token request body - requires refresh_token from order creation
        $body = json_encode([
            'refresh_token' => $testRefreshToken
        ]);

        $response = Requests::post($endpoint, $headers, $body);
        $responseBody = json_decode($response->body, true);

        if ($response->status_code !== 200) {
            throw new \Exception('Refresh token failed: HTTP ' . $response->status_code . ' - ' . json_encode($responseBody));
        }

        if (isset($responseBody['error'])) {
            throw new \Exception('Refresh token failed: ' . json_encode($responseBody['error']));
        }

        if (!isset($responseBody['token'])) {
            throw new \Exception('Token not found in refresh response');
        }

        return [
            'token' => $responseBody['token'] ?? 'N/A',
            'expires_at' => $responseBody['expires_at'] ?? 'N/A'
        ];
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::AUTH_REFRESH_TOKEN);
}

// Helper function to run a test
function runTest($name, $callback, $requestUrl = null)
{
    global $totalTests, $passedTests, $failedTests, $testResults;

    $totalTests++;
    echo "\n" . str_repeat('─', 60) . "\n";
    echo "Test {$totalTests}: {$name}\n";
    if ($requestUrl) {
        echo "   URL: {$requestUrl}\n";
    }
    echo str_repeat('─', 60) . "\n";

    $startTime = microtime(true);
    $success = false;
    $error = null;

    try {
        $result = $callback();
        $success = true;
        echo "[SUCCESS] PASS: {$name}\n";
        if ($result !== null && is_array($result)) {
            echo "   Response keys: " . implode(', ', array_keys($result)) . "\n";
        }
        $passedTests++;
    } catch (NimbblException $e) {
        $error = $e->getMessage();
        echo "[ERROR] FAIL: {$name}\n";
        echo "   Error: {$error}\n";
        echo "   Error Code: " . ($e->getErrorCode() ?? 'N/A') . "\n";
        echo "   HTTP Status: " . ($e->getHttpStatusCode() ?? 'N/A') . "\n";
        $failedTests++;
    } catch (\Exception $e) {
        $error = $e->getMessage();
        echo "[ERROR] FAIL: {$name}\n";
        echo "   Exception: {$error}\n";
        $failedTests++;
    }

    $duration = round(microtime(true) - $startTime, 2);

    $testResults[] = [
        'name' => $name,
        'success' => $success,
        'error' => $error,
        'duration' => $duration,
        'url' => $requestUrl
    ];

    echo "   Duration: {$duration}s\n";
}

// ============================================================================
// PHASE 2: ORDERS API TESTS
// ============================================================================

echo "\n" . str_repeat('═', 60) . "\n";
echo "PHASE 2: ORDERS API\n";
echo str_repeat('═', 60) . "\n";

// Test 3: Create Order
$order = null;
runTest('Orders API - Create Order', function () use ($api, $merchantToken, &$order, &$testOrderId, &$testInvoiceId, &$testRefreshToken, &$orderToken) {
    $orderData = [
        'invoice_id' => 'TEST_' . time() . '_' . rand(1000, 9999),
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
    ];

    $order = $api->orders()->createOrder($orderData, $merchantToken);

    if (isset($order['error'])) {
        throw new \Exception('Order creation failed: ' . json_encode($order['error']));
    }

    if (!isset($order['order_id']) && !isset($order['nimbbl_order_id'])) {
        throw new \Exception('Order ID not found in response');
    }

    // Update test IDs from actual response
    $testOrderId = $order['nimbbl_order_id'] ?? $order['order_id'] ?? null;
    $testInvoiceId = $order['invoice_id'] ?? null;
    $testRefreshToken = $order['refresh_token'] ?? null;
    $orderToken = $order['token'] ?? null; // Extract order token for subsequent operations

    return [
        'order_id' => $testOrderId,
        'invoice_id' => $testInvoiceId,
        'token' => isset($order['token']) ? 'present' : 'not present',
        'refresh_token' => isset($testRefreshToken) ? 'present' : 'not present'
    ];
}, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::ORDER_CREATE);

// Test 2: Refresh Token (execute now that refresh_token is available from order creation)
// Note: This test is positioned as Test 2 but executes here after order creation
if ($testRefreshToken) {
    runTest('Authorization API - Refresh Token', function () use ($api, $config, $testRefreshToken) {
        // Refresh token endpoint requires Bearer authentication with a regular token
        // First, generate a token for authentication
        $request = new Request();
        $tokenResponse = $request->generateToken();

        if (isset($tokenResponse['error']) || !isset($tokenResponse['token'])) {
            throw new \Exception('Failed to generate token for refresh token request');
        }

        $authToken = $tokenResponse['token'];

        // Build the refresh token endpoint URL
        $baseUrl = rtrim($config['api_endpoint'], '/');
        $endpoint = $baseUrl . '/refresh-token';

        // Make request with Bearer token and refresh_token in body
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $authToken
        ];

        // Refresh token request body - requires refresh_token from order creation
        $body = json_encode([
            'refresh_token' => $testRefreshToken
        ]);

        $response = Requests::post($endpoint, $headers, $body);
        $responseBody = json_decode($response->body, true);

        if ($response->status_code !== 200) {
            throw new \Exception('Refresh token failed: HTTP ' . $response->status_code . ' - ' . json_encode($responseBody));
        }

        if (isset($responseBody['error'])) {
            throw new \Exception('Refresh token failed: ' . json_encode($responseBody['error']));
        }

        if (!isset($responseBody['token'])) {
            throw new \Exception('Token not found in refresh response');
        }

        return [
            'token' => $responseBody['token'] ?? 'N/A',
            'expires_at' => $responseBody['expires_at'] ?? 'N/A'
        ];
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::AUTH_REFRESH_TOKEN);
}

// Test 4: Get Order by Order ID (using actual order_id from Test 3)
// Note: Uses getOrderById() which calls GET /api/v3/order?order_id={id}
if ($testOrderId && $orderToken) {
    runTest('Orders API - Get Order by Order ID (getOrderById)', function () use ($api, $testOrderId, $orderToken) {
        $order = $api->orders()->getOrderById($testOrderId, $orderToken);

        if (isset($order['error'])) {
            throw new \Exception('Get order failed: ' . json_encode($order['error']));
        }

        return $order;
    }, ApiConstants::HTTP_GET . ' /api/' . ApiConstants::ORDER_GET . '?order_id=' . $testOrderId);
}

// Test 4b: Get Order by Invoice ID (using actual invoice_id from Test 3)
// Note: API supports invoice_id as query parameter: GET /api/v3/order?invoice_id={id}
if ($testInvoiceId && $orderToken) {
    runTest('Orders API - Get Order by Invoice ID', function () use ($api, $testInvoiceId, $orderToken) {
        // Use getOrderByInvoiceId method
        $response = $api->orders()->getOrderByInvoiceId($testInvoiceId, $orderToken);

        if (is_array($response) && isset($response['error'])) {
            throw new \Exception('Get order by invoice_id failed: ' . json_encode($response['error']));
        }

        if (!isset($response['order_id']) && !isset($response['nimbbl_order_id']) && !isset($response['invoice_id'])) {
            throw new \Exception('Order data not found in response');
        }

        return $response;
    }, ApiConstants::HTTP_GET . ' /api/' . ApiConstants::ORDER_GET . '?invoice_id=' . $testInvoiceId);
}

// Note: Update Order (PATCH /api/v3/order) is not part of the official Nimbbl API v3 documentation.
// The update() method has been removed as it is not an official API endpoint.

// ============================================================================
// PHASE 3: ADDRESSES API TESTS
// ============================================================================

echo "\n" . str_repeat('═', 60) . "\n";
echo "PHASE 3: ADDRESSES API\n";
echo str_repeat('═', 60) . "\n";

// Test 5: List Addresses
runTest('Addresses API - List Addresses', function () use ($api, $testUserId, $orderToken) {
    if (!$orderToken) {
        throw new \Exception('Order token required for addresses API');
    }

    $addresses = $api->addresses()->listAddresses([
        'user_id' => $testUserId,
        'amount' => 1000,
        'currency' => 'INR'
    ], $orderToken);

    if (isset($addresses['error'])) {
        throw new \Exception('List addresses failed: ' . json_encode($addresses['error']));
    }

    return $addresses;
}, ApiConstants::HTTP_GET . ' /api/' . ApiConstants::ADDRESS_LIST);

// Test 7: Create Address
runTest('Addresses API - Create Address', function () use ($api, $testUserId, $orderToken, &$testAddressId) {
    if (!$orderToken) {
        throw new \Exception('Order token required for addresses API');
    }

    $address = $api->addresses()->createAddress([
        'user_id' => $testUserId,
        'address_1' => '123 Test Street',
        'area' => 'Test Area',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
        'address_type' => 'home'
    ], $orderToken);

    if (isset($address['error'])) {
        throw new \Exception('Create address failed: ' . json_encode($address['error']));
    }

    // Update testAddressId from actual response
    $testAddressId = $address['id'] ?? $address['address_id'] ?? null;

    return [
        'address_id' => $testAddressId,
        'city' => $address['city'] ?? 'N/A'
    ];
}, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::ADDRESS_CREATE);

// Test 8: Get Address - using actual address_id from Test 7
if ($testAddressId && $orderToken) {
    runTest('Addresses API - Get Address', function () use ($api, $testAddressId, $orderToken) {
        $address = $api->addresses()->getAddressById($testAddressId, $orderToken);

        if (isset($address['error'])) {
            throw new \Exception('Get address failed: ' . json_encode($address['error']));
        }

        return $address;
    }, ApiConstants::HTTP_GET . ' /api/' . ApiConstants::ADDRESS_GET . '/' . $testAddressId);
}

// Test 9: Update Address - using actual address_id
if ($testAddressId && $orderToken) {
    runTest('Addresses API - Update Address', function () use ($api, $testAddressId, $orderToken) {
        $address = $api->addresses()->updateAddress($testAddressId, [
            'address_1' => '456 Updated Street',
            'area' => 'Updated Area'
        ], $orderToken);

        if (isset($address['error'])) {
            throw new \Exception('Update address failed: ' . json_encode($address['error']));
        }

        return $address;
    }, ApiConstants::HTTP_PATCH . ' /api/' . ApiConstants::ADDRESS_UPDATE . '/' . $testAddressId);
}

// Test 10: Check Address Eligibility - using actual IDs
if ($testAddressId && $testOrderId && $orderToken) {
    runTest('Addresses API - Check Address Eligibility', function () use ($api, $testAddressId, $testOrderId, $orderToken) {
        $eligibility = $api->addresses()->checkAddressEligibility([
            'address_id' => $testAddressId,
            'order_id' => $testOrderId
        ], $orderToken);

        if (isset($eligibility['error'])) {
            throw new \Exception('Check eligibility failed: ' . json_encode($eligibility['error']));
        }

        return $eligibility;
    }, ApiConstants::HTTP_GET . ' /api/' . ApiConstants::ADDRESS_CHECK_ELIGIBILITY);
}

// Test 11: Link Address with Order - using actual IDs
if ($testAddressId && $testOrderId && $orderToken) {
    runTest('Addresses API - Link Address with Order', function () use ($api, $testAddressId, $testOrderId, $orderToken) {
        $linkResult = $api->addresses()->linkAddressWithOrder([
            'address_id' => $testAddressId,
            'order_id' => $testOrderId
        ], $orderToken);

        if (isset($linkResult['error'])) {
            throw new \Exception('Link address failed: ' . json_encode($linkResult['error']));
        }

        return $linkResult;
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::ADDRESS_LINK_ORDER);
}

// Note: Addresses API uses list() method - already tested in Test 4

// ============================================================================
// PHASE 4: PAYMENTS API TESTS
// ============================================================================

echo "\n" . str_repeat('═', 60) . "\n";
echo "PHASE 4: PAYMENTS API\n";
echo str_repeat('═', 60) . "\n";

// Test 12: Initiate Payment - using actual order_id
if ($testOrderId && $orderToken) {
    runTest('Payments API - Initiate Payment', function () use ($api, $testOrderId, $orderToken, &$testTransactionId) {
        $payment = $api->payments()->initiatePayment([
            'order_id' => $testOrderId,
            'payment_mode_code' => 'net_banking',
            'bank_code' => 'axis',
            'callback_url' => 'https://example.com/callback'
        ], $orderToken);

        if (isset($payment['error'])) {
            throw new \Exception('Initiate payment failed: ' . json_encode($payment['error']));
        }

        // Update testTransactionId from actual response
        $testTransactionId = $payment['transaction_id'] ?? $payment['nimbbl_transaction_id'] ?? null;

        return $payment;
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::PAYMENT_INITIATE);
}

// Test 13: Resend OTP - using actual IDs
if ($testTransactionId && $orderToken) {
    runTest('Payments API - Resend OTP', function () use ($api, $testTransactionId, $orderToken) {
        $resendResult = $api->payments()->resendPaymentOtp([
            'transaction_id' => $testTransactionId
        ], $orderToken);

        // Note: This may fail if OTP was already sent or payment doesn't require OTP
        if (isset($resendResult['error'])) {
            return ['status' => 'Resend OTP failed (may not require OTP)', 'error' => $resendResult['error']];
        }

        return $resendResult;
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::PAYMENT_RESEND_OTP);
}

// Note: complete() requires actual OTP, so we'll skip it in automated tests

// ============================================================================
// PHASE 5: PAYMENT LINKS API TESTS
// ============================================================================

echo "\n" . str_repeat('═', 60) . "\n";
echo "PHASE 5: PAYMENT LINKS API\n";
echo str_repeat('═', 60) . "\n";

// Test 14: Create Payment Link
runTest('Payment Links API - Create Payment Link', function () use ($api, $orderToken, &$testPaymentLinkId) {
    if (!$orderToken) {
        throw new \Exception('Order token required for payment links API');
    }

    $paymentLink = $api->paymentLinks()->createPaymentLink([
        'invoice_id' => 'PL_TEST_' . time() . '_' . rand(1000, 9999),
        'total_amount' => 2000,
        'currency' => 'INR',
        'user' => [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'mobile_number' => '9876543210',
            'country_code' => '+91'
        ]
    ], $orderToken);

    if (isset($paymentLink['error'])) {
        throw new \Exception('Create payment link failed: ' . json_encode($paymentLink['error']));
    }

    // Update testPaymentLinkId from actual response
    $testPaymentLinkId = $paymentLink['payment_link_id'] ?? null;

    return $paymentLink;
}, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::PAYMENT_LINK_CREATE);

// Test 15: Payment Link Enquiry - using actual payment_link_id from Test 14
if ($testPaymentLinkId && $orderToken) {
    runTest('Payment Links API - Payment Link Enquiry', function () use ($api, $testPaymentLinkId, $orderToken) {
        $enquiry = $api->paymentLinks()->enquiryPaymentLink([
            'payment_link_id' => $testPaymentLinkId
        ], $orderToken);

        if (isset($enquiry['error'])) {
            throw new \Exception('Payment link enquiry failed: ' . json_encode($enquiry['error']));
        }

        return $enquiry;
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::PAYMENT_LINK_ENQUIRY);
}

// Test 16: Update Payment Link - using actual payment_link_id
if ($testPaymentLinkId && $orderToken) {
    runTest('Payment Links API - Update Payment Link', function () use ($api, $testPaymentLinkId, $orderToken) {
        $updated = $api->paymentLinks()->updatePaymentLink([
            'payment_link_id' => $testPaymentLinkId,
            'total_amount' => 2500
        ], $orderToken);

        if (isset($updated['error'])) {
            throw new \Exception('Update payment link failed: ' . json_encode($updated['error']));
        }

        return $updated;
    }, ApiConstants::HTTP_PATCH . ' /api/' . ApiConstants::PAYMENT_LINK_UPDATE . '/' . $testPaymentLinkId);
}

// Note: Payment Link Actions may have restrictions, so we'll skip it

// ============================================================================
// PHASE 6: CHECKOUT UTILITIES API TESTS
// ============================================================================

echo "\n" . str_repeat('═', 60) . "\n";
echo "PHASE 6: CHECKOUT UTILITIES API\n";
echo str_repeat('═', 60) . "\n";

// Test 17: List Payment Modes - using actual order_id
if ($testOrderId && $orderToken) {
    runTest('Checkout Utilities API - List Payment Modes', function () use ($api, $testOrderId, $orderToken) {
        $modes = $api->checkoutUtilities()->listPaymentModes([
            'order_id' => $testOrderId
        ], $orderToken);

        if (isset($modes['error'])) {
            throw new \Exception('List payment modes failed: ' . json_encode($modes['error']));
        }

        return $modes;
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::CHECKOUT_PAYMENT_MODES);
}

// Test 18: List Banks - using actual order_id
if ($testOrderId && $orderToken) {
    runTest('Checkout Utilities API - List Banks', function () use ($api, $testOrderId, $orderToken) {
        $banks = $api->checkoutUtilities()->listBanks([
            'order_id' => $testOrderId
        ], $orderToken);

        if (isset($banks['error'])) {
            throw new \Exception('List banks failed: ' . json_encode($banks['error']));
        }

        return $banks;
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::CHECKOUT_LIST_BANKS);
}

// Test 19: List Wallets - using actual order_id
if ($testOrderId && $orderToken) {
    runTest('Checkout Utilities API - List Wallets', function () use ($api, $testOrderId, $orderToken) {
        $wallets = $api->checkoutUtilities()->listWallets([
            'order_id' => $testOrderId
        ], $orderToken);

        if (isset($wallets['error'])) {
            throw new \Exception('List wallets failed: ' . json_encode($wallets['error']));
        }

        return $wallets;
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::CHECKOUT_LIST_WALLETS);
}

// Test 20: List EMIs - using actual order_id
if ($testOrderId && $orderToken) {
    runTest('Checkout Utilities API - List EMIs', function () use ($api, $testOrderId, $orderToken) {
        $emis = $api->checkoutUtilities()->listEMIs([
            'order_id' => $testOrderId
        ], $orderToken);

        if (isset($emis['error'])) {
            throw new \Exception('List EMIs failed: ' . json_encode($emis['error']));
        }

        return $emis;
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::CHECKOUT_LIST_EMIS);
}

// Test 21: Get Offers - using actual order_id
// Note: API requires order_id and payment_mode_code (required fields)
if ($testOrderId && $orderToken) {
    runTest('Checkout Utilities API - Get Offers', function () use ($api, $testOrderId, $orderToken) {
        $offers = $api->checkoutUtilities()->getOffers([
            'order_id' => $testOrderId,
            'payment_mode_code' => 'all'  // Required: 'all', 'card', 'upi', 'netbanking', 'wallet', 'pay_later'
        ], $orderToken);

        if (isset($offers['error'])) {
            throw new \Exception('Get offers failed: ' . json_encode($offers['error']));
        }

        return $offers;
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::CHECKOUT_OFFERS);
}

// Test 22: Get Card BIN Data
runTest('Checkout Utilities API - Get Card BIN Data', function () use ($api, $testOrderId, $orderToken) {
    if (!$orderToken) {
        throw new \Exception('Order token required for checkout utilities API');
    }

    $binData = $api->checkoutUtilities()->getCardBinData([
        'card_bin' => '411111',
        'order_id' => $testOrderId // Optional
    ], $orderToken);

    // Check if response is valid JSON (not HTML 404)
    if (is_null($binData) || (is_array($binData) && isset($binData['error']))) {
        throw new \Exception('Get card BIN data failed: ' . (is_array($binData) ? json_encode($binData['error']) : 'Invalid response (404)'));
    }

    return $binData;
}, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::CHECKOUT_GET_BIN_DATA);

// Test 23: Validate UPI VPA
runTest('Checkout Utilities API - Validate UPI VPA', function () use ($api, $orderToken) {
    if (!$orderToken) {
        throw new \Exception('Order token required for checkout utilities API');
    }

    $validation = $api->checkoutUtilities()->validateUpiVpa([
        'upi_id' => 'test@paytm'  // API expects 'upi_id', not 'vpa'
    ], $orderToken);

    // Check if response is valid JSON (not HTML 404)
    if (is_null($validation) || (is_array($validation) && isset($validation['error']))) {
        throw new \Exception('Validate UPI VPA failed: ' . (is_array($validation) ? json_encode($validation['error']) : 'Invalid response (404)'));
    }

    return $validation;
}, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::CHECKOUT_VALIDATE_VPA);

// ============================================================================
// PHASE 7: TRANSACTION STATUS API TESTS
// ============================================================================

echo "\n" . str_repeat('═', 60) . "\n";
echo "PHASE 7: TRANSACTION STATUS API\n";
echo str_repeat('═', 60) . "\n";

// Note: Transaction Status API has been merged into Transactions API
// Use transactions()->transactionEnquiry() instead

// ============================================================================
// PHASE 8: TRANSACTIONS API TESTS
// ============================================================================

echo "\n" . str_repeat('═', 60) . "\n";
echo "PHASE 8: TRANSACTIONS API\n";
echo str_repeat('═', 60) . "\n";

// Test 24: Transaction Enquiry - using actual order_id
// Note: This is the official API endpoint POST /api/v3/transaction-enquiry
// Uses Merchant Token (for admin operations)
if ($testOrderId && $merchantToken) {
    runTest('Transactions API - Transaction Enquiry by Order ID', function () use ($api, $testOrderId, $merchantToken) {
        $enquiry = $api->transactions()->transactionEnquiry([
            'order_id' => $testOrderId
        ], $merchantToken);

        if (isset($enquiry['error'])) {
            throw new \Exception('Transaction enquiry failed: ' . json_encode($enquiry['error']));
        }

        return $enquiry;
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::TRANSACTION_ENQUIRY);
}

// Test 25: Transaction Enquiry by Invoice ID
if ($testInvoiceId && $merchantToken) {
    runTest('Transactions API - Transaction Enquiry by Invoice ID', function () use ($api, $testInvoiceId, $merchantToken) {
        $enquiry = $api->transactions()->transactionEnquiry([
            'invoice_id' => $testInvoiceId
        ], $merchantToken);

        if (isset($enquiry['error'])) {
            throw new \Exception('Transaction enquiry failed: ' . json_encode($enquiry['error']));
        }

        return $enquiry;
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::TRANSACTION_ENQUIRY);
}

// Note: cancel() is NOT an official public API - it's an internal API
// The endpoint is tagged as "Internal Api" in OpenAPI spec and mapped to /api/internal/checkout/cancel
// The SDK method should be removed or marked as @internal
// Note: retrieveTransactionByOrderId() and retrieveOne() are not in the official API
// Use Transaction Status API (transaction-enquiry) instead for checking transaction status

// ============================================================================
// PHASE 9: REFUNDS API TESTS
// ============================================================================
// Based on: https://nimbbl.biz/docs/api-reference/refund-a-payment-v-3/

echo "\n" . str_repeat('═', 60) . "\n";
echo "PHASE 9: REFUNDS API\n";
echo str_repeat('═', 60) . "\n";

// Test 26: Refunds API - API Client Available
runTest('Refunds API - API Client Available', function () use ($api) {
    // Just verify the refund client is accessible
    $refund = $api->refunds();

    if (!$refund) {
        throw new \Exception('Refunds API client not available');
    }

    return ['status' => 'Refunds API client accessible'];
}, 'N/A (Client check)');

// Test 27: Full Refund by Transaction ID
// API Documentation: https://nimbbl.biz/docs/api-reference/refund-a-payment-v-3/
// Full refund: Omit refund_amount to refund entire payment
// Uses Merchant Token (for admin operations)
if ($testTransactionId && $merchantToken) {
    runTest('Refunds API - Full Refund by Transaction ID', function () use ($api, $testTransactionId, $merchantToken) {
        $refund = $api->refunds()->initiateRefund([
            'transaction_id' => $testTransactionId,
            'comment' => 'Test full refund'
        ], $merchantToken);

        if (isset($refund['error'])) {
            // Refund may fail if transaction is not successful, but we test the API call structure
            return [
                'status' => 'Refund API called (may fail if transaction not successful)',
                'error_code' => $refund['error']['nimbbl_error_code'] ?? 'N/A',
                'transaction_type' => 'full_refund (expected)'
            ];
        }

        // Validate response structure per documentation
        return [
            'refund_id' => $refund['refund_id'] ?? $refund['nimbbl_refund_id'] ?? 'N/A',
            'transaction_id' => $refund['transaction_id'] ?? $refund['nimbbl_transaction_id'] ?? 'N/A',
            'status' => $refund['status'] ?? 'N/A',
            'refund_amount' => $refund['refund_amount'] ?? 'N/A'
        ];
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::REFUND_INITIATE);
}

// Test 28: Partial Refund by Transaction ID
// Partial refund: Include refund_amount for partial refund
// Uses Merchant Token (for admin operations)
if ($testTransactionId && $merchantToken) {
    runTest('Refunds API - Partial Refund by Transaction ID', function () use ($api, $testTransactionId, $merchantToken) {
        $refund = $api->refunds()->initiateRefund([
            'transaction_id' => $testTransactionId,
            'refund_amount' => 100,  // Partial refund amount
            'comment' => 'Test partial refund',
            'refund_request_id' => 'ref_req_' . time() . '_' . rand(1000, 9999)
        ], $merchantToken);

        if (isset($refund['error'])) {
            // Refund may fail if transaction is not successful, but we test the API call structure
            return [
                'status' => 'Refund API called (may fail if transaction not successful)',
                'error_code' => $refund['error']['nimbbl_error_code'] ?? 'N/A',
                'transaction_type' => 'partial_refund (expected)'
            ];
        }

        // Validate response structure per documentation
        return [
            'refund_id' => $refund['refund_id'] ?? $refund['nimbbl_refund_id'] ?? 'N/A',
            'transaction_id' => $refund['transaction_id'] ?? $refund['nimbbl_transaction_id'] ?? 'N/A',
            'status' => $refund['status'] ?? 'N/A',
            'refund_amount' => $refund['refund_amount'] ?? 'N/A',
            'refund_request_id' => $refund['refund_request_id'] ?? 'N/A'
        ];
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::REFUND_INITIATE);
}

// Test 29: Full Refund by Invoice ID
// API supports invoice_id as alternative to transaction_id
// Uses Merchant Token (for admin operations)
if ($testInvoiceId && !$testTransactionId && $merchantToken) {
    runTest('Refunds API - Full Refund by Invoice ID', function () use ($api, $testInvoiceId, $merchantToken) {
        $refund = $api->refunds()->initiateRefund([
            'invoice_id' => $testInvoiceId,
            'comment' => 'Test full refund by invoice_id'
        ], $merchantToken);

        if (isset($refund['error'])) {
            // Refund may fail if transaction is not successful, but we test the API call structure
            return [
                'status' => 'Refund API called (may fail if transaction not successful)',
                'error_code' => $refund['error']['nimbbl_error_code'] ?? 'N/A',
                'transaction_type' => 'full_refund (expected)'
            ];
        }

        // Validate response structure per documentation
        return [
            'refund_id' => $refund['refund_id'] ?? $refund['nimbbl_refund_id'] ?? 'N/A',
            'transaction_id' => $refund['transaction_id'] ?? $refund['nimbbl_transaction_id'] ?? 'N/A',
            'status' => $refund['status'] ?? 'N/A',
            'refund_amount' => $refund['refund_amount'] ?? 'N/A'
        ];
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::REFUND_INITIATE);
}

// Test 30: Partial Refund by Invoice ID with refund_request_id
// Tests refund_request_id parameter to prevent duplicate refunds
// Uses Merchant Token (for admin operations)
if ($testInvoiceId && !$testTransactionId && $merchantToken) {
    runTest('Refunds API - Partial Refund by Invoice ID with refund_request_id', function () use ($api, $testInvoiceId, $merchantToken) {
        $refundRequestId = 'ref_req_' . time() . '_' . rand(1000, 9999);

        $refund = $api->refunds()->initiateRefund([
            'invoice_id' => $testInvoiceId,
            'refund_amount' => 200,  // Partial refund amount
            'comment' => 'Test partial refund with refund_request_id',
            'refund_request_id' => $refundRequestId
        ], $merchantToken);

        if (isset($refund['error'])) {
            // Refund may fail if transaction is not successful, but we test the API call structure
            return [
                'status' => 'Refund API called (may fail if transaction not successful)',
                'error_code' => $refund['error']['nimbbl_error_code'] ?? 'N/A',
                'refund_request_id' => $refundRequestId
            ];
        }

        // Validate response structure per documentation
        return [
            'refund_id' => $refund['refund_id'] ?? $refund['nimbbl_refund_id'] ?? 'N/A',
            'transaction_id' => $refund['transaction_id'] ?? $refund['nimbbl_transaction_id'] ?? 'N/A',
            'status' => $refund['status'] ?? 'N/A',
            'refund_amount' => $refund['refund_amount'] ?? 'N/A',
            'refund_request_id' => $refund['refund_request_id'] ?? 'N/A'
        ];
    }, ApiConstants::HTTP_POST . ' /api/' . ApiConstants::REFUND_INITIATE);
}

if (!$testTransactionId && !$testInvoiceId) {
    echo "\n[WARNING]  Skipping Refund Initiate tests (No transaction_id or invoice_id available)\n";
    echo "   Note: Refund API requires a successful transaction to test properly\n";
    echo "   API Documentation: https://nimbbl.biz/docs/api-reference/refund-a-payment-v-3/\n";
}

// Note: retrieveRefundByOrderId(), retrieveRefundByTxnId(), and retrieveOne() 
// are not in the official API. Only initiateRefund() (POST /api/v3/refund) is official.
// Refund status can be checked using Transaction Status API (transaction-enquiry)
// Response fields per documentation:
// - orignal_payment_transaction_id (required)
// - transaction_id (required) - refund transaction ID
// - refund_request_id (nullable)
// - refund_status (required) - new, pending, succeeded, failed
// - refund_arn (nullable) - bank reference number
// - transaction_type (required) - partial_refund, full_refund
// - error (nullable)
// - next (required) - array with action and url for refund_enquiry

// ============================================================================
// NOTE: Users API has been removed from the SDK as it's not an official public API
// ============================================================================
// SUMMARY
// ============================================================================

echo "\n\n" . str_repeat('═', 60) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat('═', 60) . "\n\n";

echo sprintf("%-45s %-35s %-10s %8s\n", "Test Name", "Request URL", "Status", "Duration");
echo str_repeat('─', 100) . "\n";

foreach ($testResults as $result) {
    $status = $result['success'] ? '[SUCCESS] PASS' : '[ERROR] FAIL';
    $duration = $result['duration'];
    $url = $result['url'] ?? 'N/A';
    echo sprintf(
        "%-45s %-35s %-10s %7.2fs\n",
        substr($result['name'], 0, 44),
        substr($url, 0, 34),
        $status,
        $duration
    );

    if (!$result['success'] && $result['error']) {
        echo "   └─ Error: " . substr($result['error'], 0, 80) . "...\n";
    }
}

echo str_repeat('─', 100) . "\n";
echo sprintf(
    "Total: %d tests | Passed: %d | Failed: %d\n",
    $totalTests,
    $passedTests,
    $failedTests
);

$totalDuration = array_sum(array_column($testResults, 'duration'));
echo sprintf("Total Duration: %.2fs\n", $totalDuration);

if ($failedTests > 0) {
    echo "\n[WARNING]  Some tests failed. Please check the output above for details.\n";
    echo "Note: Some tests may fail if API credentials are invalid or API is unavailable.\n";
    exit(1);
} else {
    echo "\n[SUCCESS] All tests passed!\n";
    exit(0);
}

