<?php
/**
 * Nimbbl PHP SDK - Payments API Test
 * 
 * Tests the Payments API client with real API calls
 * 
 * Usage: php test-payments.php
 * 
 * Note: This requires an existing order_id from a created order
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

echo "=== Payments API Test ===\n\n";

// Generate merchant token first
echo "Step 1: Generating merchant token\n";
echo str_repeat('-', 50) . "\n";
$request = new Request();
$merchantToken = $request->generateToken()['token'];
echo "✓ Merchant token generated\n\n";

// First, create an order to test payment initiation
echo "Step 2: Creating an order for payment testing\n";
echo str_repeat('-', 50) . "\n";
try {
    $order = $api->orders()->createOrder([
        'invoice_id' => 'test_payment_' . time(),
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
        echo "✗ Error creating order: " . print_r($order['error'], true) . "\n";
        exit(1);
    }
    
    $orderId = $order['nimbbl_order_id'] ?? $order['order_id'] ?? null;
    $orderToken = $order['token'] ?? null;
    
    if (!$orderId) {
        echo "✗ Error: Order ID not found in response\n";
        exit(1);
    }
    
    if (!$orderToken) {
        echo "✗ Error: Order token not found in response\n";
        exit(1);
    }
    
    echo "✓ Order created successfully\n";
    echo "  Order ID: {$orderId}\n";
    echo "  Order Token: " . substr($orderToken, 0, 20) . "...\n";
} catch (Exception $e) {
    echo "✗ Exception creating order: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n\n";

// Test 1: Initiate Payment
echo "Test 1: Initiate Payment\n";
echo str_repeat('-', 50) . "\n";
try {
    $paymentInit = $api->payments()->initiatePayment([
        'order_id' => $orderId,
        'payment_mode_code' => 'net_banking', // or 'card', 'upi', 'wallet', etc.
        'bank_code' => 'axis', // Required for netbanking
        'callback_url' => 'https://example.com/callback'
    ], $orderToken);
    
    if (isset($paymentInit['error'])) {
        echo "✗ Error: " . print_r($paymentInit['error'], true) . "\n";
    } else {
        echo "✓ Payment initiated successfully\n";
        echo "  Transaction ID: " . ($paymentInit['transaction_id'] ?? $paymentInit['nimbbl_transaction_id'] ?? 'N/A') . "\n";
        echo "  Status: " . ($paymentInit['status'] ?? 'N/A') . "\n";
        $transactionId = $paymentInit['transaction_id'] ?? $paymentInit['nimbbl_transaction_id'] ?? null;
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 2: Resend OTP (if payment requires OTP)
if (isset($transactionId) && $orderToken) {
    echo "Test 2: Resend OTP\n";
    echo str_repeat('-', 50) . "\n";
    try {
        $resendOtp = $api->payments()->resendPaymentOtp([
            'transaction_id' => $transactionId
        ], $orderToken);
        
        if (isset($resendOtp['error'])) {
            echo "✗ Error: " . print_r($resendOtp['error'], true) . "\n";
        } else {
            echo "✓ OTP resent successfully\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n\n";
    
    // Test 3: Complete Payment (for Pay Later providers that require native OTP)
    echo "Test 3: Complete Payment (Pay Later with OTP)\n";
    echo str_repeat('-', 50) . "\n";
    echo "Note: This is only for certain Pay Later providers\n";
    try {
        $completePayment = $api->payments()->completePayment([
            'transaction_id' => $transactionId,
            'payment_flow' => 'otp',
            'otp' => '123456' // Replace with actual OTP
        ], $orderToken);
        
        if (isset($completePayment['error'])) {
            echo "✗ Error: " . print_r($completePayment['error'], true) . "\n";
            echo "  (This is expected if payment doesn't require OTP completion)\n";
        } else {
            echo "✓ Payment completed successfully\n";
            echo "  Status: " . ($completePayment['status'] ?? 'N/A') . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "  (This is expected if payment doesn't require OTP completion)\n";
    }
    
    echo "\n\n";
}

echo "=== Payments API Test Complete ===\n";
echo "\nNote: Payment flow depends on the payment mode selected.\n";
echo "For most payment modes, the customer completes payment on the checkout page.\n";

