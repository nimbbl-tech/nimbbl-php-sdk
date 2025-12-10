<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Payments API Examples
 * 
 * This example demonstrates how to use the Payments API
 * 
 * API Documentation: https://nimbbl.biz/docs/category/api-reference/payments/
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';

use Nimbbl\Api\Api;

// Load configuration
$config = loadConfig();

// Initialize Nimbbl API
$api = initApi($config);

echo "=== Payments API Examples ===\n\n";

// Get token from user input
echo "Enter Token: ";
$token = trim(fgets(STDIN));
if (empty($token)) {
    echo "✗ Error: Token is required\n";
    exit(1);
}

// Get order_id from user input
echo "Enter Order ID: ";
$orderId = trim(fgets(STDIN));
if (empty($orderId)) {
    echo "✗ Error: Order ID is required\n";
    exit(1);
}

echo "\nUsing Order ID: {$orderId}\n";
echo str_repeat('-', 50) . "\n\n";

// Example 1: Initiate Payment
echo "Example 1: Initiate Payment\n";
echo str_repeat('-', 50) . "\n";
try {
    $paymentInit = $api->payments()->initiatePayment([
        'order_id' => $orderId,
        'payment_mode_code' => 'net_banking',
        'bank_code' => 'axis',
        'callback_url' => 'https://google.com'
    ], $token);
    
    if (isset($paymentInit['error'])) {
        echo "✗ Error: " . print_r($paymentInit['error'], true) . "\n";
    } else {
        echo "✓ Payment initiated successfully\n";
        echo "  Transaction ID: " . ($paymentInit['transaction_id'] ?? 'N/A') . "\n";
        echo "  Status: " . ($paymentInit['status'] ?? 'N/A') . "\n";
        $transactionId = $paymentInit['transaction_id'] ?? null;
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Example 2: Resend OTP
echo "Example 2: Resend OTP\n";
echo str_repeat('-', 50) . "\n";
echo "Enter Transaction ID: ";
$transactionId = trim(fgets(STDIN));
if (empty($transactionId)) {
    echo "⚠ Skipping - Transaction ID is required\n";
} else {
    try {
        $resendOtp = $api->payments()->resendPaymentOtp([
            'transaction_id' => $transactionId
        ], $token);
        
        if (isset($resendOtp['error'])) {
            echo "✗ Error: " . print_r($resendOtp['error'], true) . "\n";
            echo "  (This is expected if payment doesn't require OTP)\n";
        } else {
            echo "✓ OTP resent successfully\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "  (This is expected if payment doesn't require OTP)\n";
    }
}

echo "\n\n";

// Example 3: Complete Payment (for Pay Later providers with auto_debit)
echo "Example 3: Complete Payment (Pay Later with auto_debit)\n";
echo str_repeat('-', 50) . "\n";
echo "Note: This is only for certain Pay Later providers that require native OTP experience\n";
echo "      Payment flow can be 'auto_debit' or 'otp'\n";
echo "Enter Transaction ID: ";
$completeTransactionId = trim(fgets(STDIN));
if (empty($completeTransactionId)) {
    echo "⚠ Skipping - Transaction ID is required\n";
} else {
    echo "Enter Payment Flow (auto_debit or otp): ";
    $paymentFlow = trim(fgets(STDIN));
    if (empty($paymentFlow)) {
        $paymentFlow = 'auto_debit'; // Default
    }
    
    $completeData = [
        'transaction_id' => $completeTransactionId,
        'payment_flow' => $paymentFlow
    ];
    
    // If payment flow is 'otp', prompt for OTP
    if (strtolower($paymentFlow) === 'otp') {
        echo "Enter OTP: ";
        $otp = trim(fgets(STDIN));
        if (!empty($otp)) {
            $completeData['otp'] = $otp;
        }
    }
    
    try {
        $completePayment = $api->payments()->completePayment($completeData, $token);
        
        if (isset($completePayment['error'])) {
            echo "✗ Error: " . print_r($completePayment['error'], true) . "\n";
            echo "  (This is expected if payment doesn't require completion or flow is different)\n";
        } else {
            echo "✓ Payment completed successfully\n";
            echo "  Status: " . ($completePayment['payment_status'] ?? $completePayment['status'] ?? 'N/A') . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "  (This is expected if payment doesn't require completion)\n";
    }
}

echo "\n";
echo "=== Payments API Examples Complete ===\n";
echo "\nKey Points:\n";
echo "  • Payment flow depends on the payment mode selected\n";
echo "  • For most payment modes, customer completes payment on checkout page\n";
echo "  • OTP flow is used for certain payment methods\n";
echo "  • Complete Payment API is for Pay Later providers with native OTP\n";
echo "\nFor more information, see: https://nimbbl.biz/docs/category/api-reference/payments/\n";

