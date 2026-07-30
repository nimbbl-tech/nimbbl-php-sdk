<?php
/**
 * Nimbbl PHP SDK - Payment Links API Test
 * 
 * Tests the Payment Links API client with real API calls
 * 
 * Usage: php test-payment-links.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../example/config.php';
require_once __DIR__ . '/../../example/utils/helpers.php';

use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\RestClient\Request;

// Load configuration
$config = loadConfig();

// Initialize Nimbbl API
$api = new NimbblClient(
    $config['access_key'],
    $config['access_secret'],
    $config['api_endpoint']
);

echo "=== Payment Links API Test ===\n\n";

// Generate merchant token first
echo "Step 1: Generating merchant token\n";
echo str_repeat('-', 50) . "\n";
$request = new Request();
$merchantToken = $request->generateToken()['token'];

// Create order to get order token
echo "Step 2: Creating an order to get order token\n";
echo str_repeat('-', 50) . "\n";
$order = $api->orders()->createOrder([
    'invoice_id' => 'PL_TEST_' . time(),
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

$orderToken = $order['token'] ?? null;
if (!$orderToken) {
    echo "[ERROR] Error: Order token not available\n";
    exit(1);
}
echo "[SUCCESS] Order token obtained\n\n";

// Test 1: Create Payment Link
echo "Test 1: Create Payment Link\n";
echo str_repeat('-', 50) . "\n";
try {
    $paymentLink = $api->paymentLinks()->createPaymentLink([
        'invoice_id' => 'PL_TEST_' . time() . '_' . rand(1000, 9999),
        'amount' => 1000,
        'currency' => 'INR',
        'user' => [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'mobile_number' => '9876543210',
            'country_code' => '+91'
        ],
        'expires_at' => time() + 86400
    ], $merchantToken);

    if (isset($paymentLink['error'])) {
        echo "[ERROR] Error: " . print_r($paymentLink['error'], true) . "\n";
        exit(1);
    }

    echo "[SUCCESS] Payment link created successfully\n";
    echo "  Payment Link ID: " . ($paymentLink['payment_link_id'] ?? 'N/A') . "\n";
    echo "  Short URL: " . ($paymentLink['short_url'] ?? 'N/A') . "\n";
    $paymentLinkId = $paymentLink['payment_link_id'] ?? null;
} catch (Exception $e) {
    echo "[ERROR] Exception: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n\n";

// Test 2: Payment Link Enquiry
if (isset($paymentLinkId) && $orderToken) {
    echo "Test 2: Payment Link Enquiry\n";
    echo str_repeat('-', 50) . "\n";
    try {
        $enquiry = $api->paymentLinks()->enquiryPaymentLink([
            'payment_link_id' => $paymentLinkId
        ], $orderToken);

        if (isset($enquiry['error'])) {
            echo "[ERROR] Error: " . print_r($enquiry['error'], true) . "\n";
        } else {
            echo "[SUCCESS] Payment link enquiry successful\n";
            echo "  Status: " . ($enquiry['status'] ?? 'N/A') . "\n";
            echo "  Amount: " . ($enquiry['amount'] ?? 'N/A') . "\n";
        }
    } catch (Exception $e) {
        echo "[ERROR] Exception: " . $e->getMessage() . "\n";
    }

    echo "\n\n";

    // Test 3: Update Payment Link
    echo "Test 3: Update Payment Link\n";
    echo str_repeat('-', 50) . "\n";
    try {
        $updatedLink = $api->paymentLinks()->updatePaymentLink([
            'payment_link_id' => $paymentLinkId,
            'total_amount' => 1500
        ], $orderToken);

        if (isset($updatedLink['error'])) {
            echo "[ERROR] Error: " . print_r($updatedLink['error'], true) . "\n";
        } else {
            echo "[SUCCESS] Payment link updated successfully\n";
            echo "  Updated Amount: " . ($updatedLink['amount'] ?? 'N/A') . "\n";
        }
    } catch (Exception $e) {
        echo "[ERROR] Exception: " . $e->getMessage() . "\n";
    }

    echo "\n\n";

    // Test 4: Payment Link Actions (e.g., cancel, pause, resume)
    echo "Test 4: Payment Link Actions\n";
    echo str_repeat('-', 50) . "\n";
    try {
        $action = $api->paymentLinks()->performPaymentLinkActions([
            'payment_link_id' => $paymentLinkId,
            'action' => 'cancel' // or 'send'
        ], $orderToken);

        if (isset($action['error'])) {
            echo "[ERROR] Error: " . print_r($action['error'], true) . "\n";
            echo "  (This is expected if action is not supported or link is already cancelled)\n";
        } else {
            echo "[SUCCESS] Payment link action executed successfully\n";
            echo "  Action: cancel\n";
        }
    } catch (Exception $e) {
        echo "[ERROR] Exception: " . $e->getMessage() . "\n";
        echo "  (This is expected if action is not supported)\n";
    }

    echo "\n\n";
}

echo "=== Payment Links API Test Complete ===\n";

