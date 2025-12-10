<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Payment Links API Examples
 * 
 * This example demonstrates how to use the Payment Links API
 * 
 * API Documentation: https://nimbbl.biz/docs/category/api-reference/payment-link/
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';

use Nimbbl\Api\Api;

// Load configuration
$config = loadConfig();

// Initialize Nimbbl API
$api = initApi($config);

echo "=== Payment Links API Examples ===\n\n";

// Get token from user input
echo "Enter Token: ";
$token = fgets(STDIN);
if (empty($token)) {
    echo "✗ Error: Token is required\n";
    exit(1);
}

// Example 1: Create Payment Link
echo "Example 1: Create Payment Link\n";
echo str_repeat('-', 50) . "\n";
try {
    $paymentLink = $api->paymentLinks()->createPaymentLink([
        'invoice_id' => 'PL_TEST_' . time() . '_' . rand(1000, 9999),
        'total_amount' => 1000,
        'currency' => 'INR',
        'description' => 'Test Payment Link',
        'user' => [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'mobile_number' => '9876543210',
            'country_code' => '+91'
        ],
        'expires_at' => date('Y-m-d H:i:s', time() + (7 * 24 * 60 * 60)) // 7 days from now
    ], $token);
    
    if (isset($paymentLink['error'])) {
        echo "✗ Error: " . print_r($paymentLink['error'], true) . "\n";
        exit(1);
    }
    
    echo "✓ Payment link created successfully\n";
    echo "  Payment Link ID: " . ($paymentLink['payment_link_id'] ?? 'N/A') . "\n";
    echo "  Short URL: " . ($paymentLink['short_url'] ?? 'N/A') . "\n";
    $paymentLinkId = $paymentLink['payment_link_id'] ?? null;
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n\n";

// Example 2: Payment Link Enquiry
if (isset($paymentLinkId)) {
    echo "Example 2: Payment Link Enquiry\n";
    echo str_repeat('-', 50) . "\n";
    try {
        $enquiry = $api->paymentLinks()->enquiryPaymentLink([
            'payment_link_id' => $paymentLinkId
        ], $token);
        
        if (isset($enquiry['error'])) {
            echo "✗ Error: " . print_r($enquiry['error'], true) . "\n";
        } else {
            echo "✓ Payment link enquiry successful\n";
            echo "  Status: " . ($enquiry['status'] ?? 'N/A') . "\n";
            echo "  Amount: " . ($enquiry['amount'] ?? 'N/A') . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n\n";
    
    // Example 3: Update Payment Link
    echo "Example 3: Update Payment Link\n";
    echo str_repeat('-', 50) . "\n";
    try {
        $updatedLink = $api->paymentLinks()->updatePaymentLink([
            'payment_link_id' => $paymentLinkId,
            'total_amount' => 1500
        ], $token);
        
        if (isset($updatedLink['error'])) {
            echo "✗ Error: " . print_r($updatedLink['error'], true) . "\n";
        } else {
            echo "✓ Payment link updated successfully\n";
            echo "  Updated Amount: " . ($updatedLink['amount'] ?? 'N/A') . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n\n";
    
    // Example 4: Payment Link Actions (Send and Cancel)
    echo "Example 4: Payment Link Actions\n";
    echo str_repeat('-', 50) . "\n";
    echo "Available actions: 'send' (send payment link via email/SMS), 'cancel' (cancel payment link)\n";
    
    // Action 1: Send payment link
    echo "\n4a. Send Payment Link\n";
    try {
        $sendAction = $api->paymentLinks()->performPaymentLinkActions([
            'payment_link_id' => $paymentLinkId,
            'action' => 'send' // Send payment link to customer
        ], $token);
        
        if (isset($sendAction['error'])) {
            echo "✗ Error: " . print_r($sendAction['error'], true) . "\n";
            echo "  (This is expected if action is not supported or link is already sent)\n";
        } else {
            echo "✓ Payment link send action executed successfully\n";
            echo "  Action: send\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "  (This is expected if action is not supported)\n";
    }
    
    // Action 2: Cancel payment link
    echo "\n4b. Cancel Payment Link\n";
    try {
        $cancelAction = $api->paymentLinks()->performPaymentLinkActions([
            'payment_link_id' => $paymentLinkId,
            'action' => 'cancel' // Cancel the payment link
        ], $token);
        
        if (isset($cancelAction['error'])) {
            echo "✗ Error: " . print_r($cancelAction['error'], true) . "\n";
            echo "  (This is expected if action is not supported or link is already cancelled)\n";
        } else {
            echo "✓ Payment link cancel action executed successfully\n";
            echo "  Action: cancel\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "  (This is expected if action is not supported)\n";
    }
    
    echo "\n\n";
}

echo "=== Payment Links API Examples Complete ===\n";
echo "\nFor more information, see: https://nimbbl.biz/docs/category/api-reference/payment-link/\n";

