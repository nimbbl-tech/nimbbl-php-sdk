<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Transaction Status Example
 * 
 * This example demonstrates how to check transaction status using the Nimbbl PHP SDK
 * 
 * API Documentation: https://nimbbl.biz/docs/api-reference/transaction-enquiry-v-3/
 * 
 * Note: Transaction Status API is available in both S2S and Client SDKs
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';

use Nimbbl\Api\Api;

// Load configuration
$config = loadConfig();

// Initialize Nimbbl API
$api = initApi($config);

echo "=== Transaction Status Examples ===\n\n";

// Get token from user input
echo "Enter Token: ";
$token = fgets(STDIN);
if (empty($token)) {
    echo "✗ Error: Token is required\n";
    exit(1);
}

// Example 1: Transaction Enquiry by Order ID
echo "Example 1: Transaction Enquiry by Order ID\n";
echo str_repeat('-', 50) . "\n";

try {
    // Replace with actual order_id from your account
    $enquiryData = [
        'order_id' => 'o_BXyGDl4epOb2ezLX',
    ];
    
    echo "Checking transaction status for order: {$enquiryData['order_id']}\n";
    
    // Use the Transactions API client
    $transaction = $api->transactions()->transactionEnquiry($enquiryData, $token);
    
    if (isset($transaction['error'])) {
        echo "✗ Error: " . print_r($transaction->error, true) . "\n";
    } else {
        echo "✓ Transaction status retrieved successfully!\n";
        
        // Handle response structure (can be object or array)
        $orderData = is_array($transaction) ? $transaction : (array)$transaction;
        $orderInfo = $orderData['order'] ?? $orderData;
        
        $orderId = $orderInfo['nimbbl_order_id'] ?? $orderInfo['order_id'] ?? $orderData['order_id'] ?? 'N/A';
        $orderStatus = $orderInfo['status'] ?? $orderData['order_status'] ?? 'N/A';
        
        echo "  Order ID: {$orderId}\n";
        echo "  Order Status: {$orderStatus}\n";
        
        // Handle transactions array
        $transactions = $orderData['transaction'] ?? $orderData['transactions'] ?? [];
        if (is_array($transactions) && count($transactions) > 0) {
            echo "  Transactions: " . count($transactions) . "\n";
            foreach ($transactions as $index => $txn) {
                $txnId = is_array($txn) ? ($txn['transaction_id'] ?? $txn['nimbbl_transaction_id'] ?? 'N/A') : 'N/A';
                $txnStatus = is_array($txn) ? ($txn['status'] ?? 'N/A') : 'N/A';
                echo "    " . ($index + 1) . ". Transaction ID: {$txnId} | Status: {$txnStatus}\n";
            }
        } else {
            echo "  Transactions: 0\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Example 2: Transaction Enquiry by Invoice ID
echo "Example 2: Transaction Enquiry by Invoice ID\n";
echo str_repeat('-', 50) . "\n";

try {
    // Replace with actual invoice_id from your account
    $enquiryData = [
        'invoice_id' => 'shopify-s191',
    ];
    
    echo "Checking transaction status for invoice: {$enquiryData['invoice_id']}\n";
    
    $transaction = $api->transactions()->transactionEnquiry($enquiryData, $token);
    
    if (isset($transaction['error'])) {
        echo "✗ Error: " . print_r($transaction['error'], true) . "\n";
    } else {
        echo "✓ Transaction status retrieved successfully!\n";
        
        // Handle response structure (array)
        $orderData = $transaction;
        $orderInfo = $orderData['order'] ?? $orderData;
        
        $invoiceId = $orderInfo['invoice_id'] ?? $orderData['invoice_id'] ?? 'N/A';
        $orderStatus = $orderInfo['status'] ?? $orderData['order_status'] ?? 'N/A';
        
        echo "  Invoice ID: {$invoiceId}\n";
        echo "  Order Status: {$orderStatus}\n";
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Example 3: Transaction Enquiry by Transaction ID
echo "Example 3: Transaction Enquiry by Transaction ID\n";
echo str_repeat('-', 50) . "\n";

try {
    // Replace with actual transaction_id from your account
    $enquiryData = [
        'transaction_id' => 'o_QVzRMzP6k2o6D5jn-240711123924',
    ];
    
    echo "Checking transaction status for transaction: {$enquiryData['transaction_id']}\n";
    
    $transaction = $api->transactions()->transactionEnquiry($enquiryData, $token);
    
    if (isset($transaction['error'])) {
        echo "✗ Error: " . print_r($transaction['error'], true) . "\n";
    } else {
        echo "✓ Transaction status retrieved successfully!\n";
        
        // Handle response structure (array)
        $txnData = $transaction;
        $txnInfo = $txnData['transaction'] ?? (isset($txnData['transaction'][0]) ? $txnData['transaction'][0] : $txnData);
        
        $txnId = $txnInfo['transaction_id'] ?? $txnInfo['nimbbl_transaction_id'] ?? $txnData['transaction_id'] ?? 'N/A';
        $status = $txnInfo['status'] ?? $txnData['status'] ?? 'N/A';
        $amount = $txnInfo['amount'] ?? $txnData['amount'] ?? 0;
        $currency = $txnInfo['currency'] ?? $txnData['currency'] ?? 'INR';
        
        echo "  Transaction ID: {$txnId}\n";
        echo "  Status: {$status}\n";
        echo "  Amount: " . ($amount) . " " . ($currency) . "\n";
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n";
echo "=== Examples Complete ===\n";
echo "\nKey Points:\n";
echo "  • Transaction Status API gets the latest status of order and transactions\n";
echo "  • Can query by order_id, invoice_id, or transaction_id\n";
echo "  • Returns comprehensive transaction and order status information\n";
echo "  • Use this to check the current state of a transaction\n";
echo "\nFor more details, see: https://nimbbl.biz/docs/api-reference/transaction-enquiry-v-3/\n";

