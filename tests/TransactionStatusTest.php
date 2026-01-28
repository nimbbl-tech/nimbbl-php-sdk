<?php
/**
 * Nimbbl PHP SDK - Transaction Status API Test
 * 
 * Tests the Transaction Status API client with real API calls
 * 
 * Usage: php test-transaction-status.php
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

echo "=== Transaction Status API Test ===\n\n";

// Generate merchant token first
echo "Step 1: Generating merchant token\n";
echo str_repeat('-', 50) . "\n";
$request = new Request();
$merchantToken = $request->generateToken()['token'];
echo "[SUCCESS] Merchant token generated\n\n";

// First, create an order to test transaction status
echo "Step 2: Creating an order for transaction status testing\n";
echo str_repeat('-', 50) . "\n";
try {
    $order = $api->orders()->createOrder([
        'invoice_id' => 'test_status_' . time(),
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
    $invoiceId = $order['invoice_id'] ?? null;

    if (!$orderId) {
        echo "[ERROR] Error: Order ID not found in response\n";
        exit(1);
    }

    echo "[SUCCESS] Order created successfully\n";
    echo "  Order ID: {$orderId}\n";
    echo "  Invoice ID: {$invoiceId}\n";
} catch (Exception $e) {
    echo "[ERROR] Exception creating order: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n\n";

// Test 1: Transaction Enquiry by Order ID
// Note: Transaction Status API has been merged into Transactions API
echo "Test 1: Transaction Enquiry by Order ID\n";
echo str_repeat('-', 50) . "\n";
try {
    $status = $api->transactions()->transactionEnquiry([
        'order_id' => $orderId
    ], $merchantToken);

    if (isset($status['error'])) {
        echo "[ERROR] Error: " . print_r($status['error'], true) . "\n";
    } else {
        echo "[SUCCESS] Transaction status retrieved successfully\n";

        // Display transaction data
        if (isset($status['transaction']) && is_array($status['transaction'])) {
            $transactions = is_array($status['transaction'][0] ?? null) ? $status['transaction'] : [$status['transaction']];
            echo "  Transactions: " . count($transactions) . "\n";
            foreach ($transactions as $index => $txn) {
                echo "    " . ($index + 1) . ". Transaction ID: " . ($txn['transaction_id'] ?? $txn['nimbbl_transaction_id'] ?? 'N/A') .
                    " | Status: " . ($txn['status'] ?? 'N/A') . "\n";
            }
        }

        // Display order data
        if (isset($status['order']) && is_array($status['order'])) {
            $orderData = $status['order'];
            echo "  Order ID: " . ($orderData['nimbbl_order_id'] ?? $orderData['order_id'] ?? 'N/A') . "\n";
            echo "  Order Status: " . ($orderData['status'] ?? 'N/A') . "\n";
        }
    }
} catch (Exception $e) {
    echo "[ERROR] Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 2: Transaction Enquiry by Invoice ID
if (isset($invoiceId)) {
    echo "Test 2: Transaction Enquiry by Invoice ID\n";
    echo str_repeat('-', 50) . "\n";
    try {
        $status = $api->transactions()->transactionEnquiry([
            'invoice_id' => $invoiceId
        ], $merchantToken);

        if (isset($status['error'])) {
            echo "[ERROR] Error: " . print_r($status['error'], true) . "\n";
        } else {
            echo "[SUCCESS] Transaction status retrieved successfully\n";
            if (isset($status['order']) && is_array($status['order'])) {
                echo "  Invoice ID: " . ($status['order']['invoice_id'] ?? 'N/A') . "\n";
                echo "  Order Status: " . ($status['order']['status'] ?? 'N/A') . "\n";
            }
        }
    } catch (Exception $e) {
        echo "[ERROR] Exception: " . $e->getMessage() . "\n";
    }

    echo "\n\n";
}

// Test 3: Transaction Enquiry by Transaction ID (if available)
echo "Test 3: Transaction Enquiry by Transaction ID\n";
echo str_repeat('-', 50) . "\n";
echo "Note: This requires an existing transaction_id\n";
echo "You can use a transaction_id from a previous payment\n\n";

// Example: Replace with actual transaction_id if you have one
$exampleTransactionId = 'o_XXXXXXXX-240711123924'; // Replace with actual transaction ID

try {
    $status = $api->transactions()->transactionEnquiry([
        'transaction_id' => $exampleTransactionId
    ], $merchantToken);

    if (isset($status['error'])) {
        echo "[ERROR] Error: " . print_r($status['error'], true) . "\n";
        echo "  (This is expected if the transaction_id doesn't exist)\n";
    } else {
        echo "[SUCCESS] Transaction status retrieved successfully\n";
        if (isset($status['transaction']) && is_array($status['transaction'])) {
            $txn = is_array($status['transaction'][0] ?? null) ? $status['transaction'][0] : $status['transaction'];
            echo "  Transaction ID: " . ($txn['transaction_id'] ?? $txn['nimbbl_transaction_id'] ?? 'N/A') . "\n";
            echo "  Status: " . ($txn['status'] ?? 'N/A') . "\n";
            echo "  Amount: " . formatAmount($txn['amount'] ?? 0, $txn['currency'] ?? 'INR') . "\n";
        }
    }
} catch (Exception $e) {
    echo "[ERROR] Exception: " . $e->getMessage() . "\n";
    echo "  (This is expected if the transaction_id doesn't exist)\n";
}

echo "\n";
echo "=== Transaction Enquiry API Test Complete ===\n";
echo "\nKey Points:\n";
echo "  • Transaction Enquiry API gets the latest status of order and transactions\n";
echo "  • Can query by order_id, invoice_id, or transaction_id\n";
echo "  • Returns comprehensive transaction and order status information\n";
echo "  • Uses Merchant Token (from generateToken())\n";
echo "  • Use this to check the current state of a transaction\n";

