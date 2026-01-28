#!/usr/bin/env php
<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Transaction Status Examples
 * 
 * This example demonstrates how to check transaction status using the Nimbbl PHP SDK
 * 
 * This file can be:
 * 1. Executed standalone: php transaction-status.php
 * 2. Included from cli.php to use the function: transactionEnquiryExample()
 * 
 * API Documentation: https://nimbbl.biz/docs/api-reference/transaction-enquiry-v-3/
 * 
 * Note: Transaction Status API is available in both S2S and Client SDKs
 */

// All helper functions are available from cli_output.php
use Nimbbl\Api\Common\JsonKeys;

/**
 * Transaction Enquiry - Function to be called from cli.php or standalone
 */
function transactionEnquiryExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);

    printInfo("Enter one of: transaction_id, order_id, or invoice_id (at least one required)\n");
    $transactionId = getInput("Enter Transaction ID (or press Enter to skip): ", false);
    $orderId = getInput("Enter Order ID (or press Enter to skip): ", false);
    $invoiceId = getInput("Enter Invoice ID (or press Enter to skip): ", false);
    $transactionData = [];
    if ($transactionId) {
        $transactionData[JsonKeys::TRANSACTION_ID] = $transactionId;
    }
    if ($orderId) {
        $transactionData[JsonKeys::ORDER_ID] = $orderId;
    }
    if ($invoiceId) {
        $transactionData[JsonKeys::INVOICE_ID] = $invoiceId;
    }
    if (empty($transactionData)) {
        printError("At least one of Transaction ID, Order ID, or Invoice ID is required.\n");
        return;
    }
    try {
        $result = $api->transactions()->transactionEnquiry($transactionData);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Transaction enquiry successful!\n");
            if (is_array($result)) {
                // Display transactions
                if (isset($result['transaction']) && is_array($result['transaction']) && !empty($result['transaction'])) {
                    echo "\nTransaction(s):\n";
                    foreach ($result['transaction'] as $idx => $transaction) {
                        echo "  Transaction " . ($idx + 1) . ":\n";
                        echo "    Transaction ID: " . ($transaction[JsonKeys::TRANSACTION_ID] ?? $transaction[JsonKeys::NIMBBL_TRANSACTION_ID] ?? 'N/A') . "\n";
                        echo "    Status: " . ($transaction[JsonKeys::STATUS] ?? 'N/A') . "\n";
                        echo "    Amount: " . ($transaction[JsonKeys::AMOUNT] ?? 0) . " " . ($transaction[JsonKeys::CURRENCY] ?? 'INR') . "\n";
                        if (isset($transaction[JsonKeys::PAYMENT_MODE_CODE])) {
                            echo "    Payment Mode: " . $transaction[JsonKeys::PAYMENT_MODE_CODE] . "\n";
                        }
                    }
                } else {
                    echo "\nNo transactions found for this order.\n";
                }

                // Display order information
                if (isset($result['order']) && is_array($result['order'])) {
                    echo "\nOrder:\n";
                    $order = $result['order'];
                    echo "  Order ID: " . ($order[JsonKeys::NIMBBL_ORDER_ID] ?? 'N/A') . "\n";
                    echo "  Invoice ID: " . ($order[JsonKeys::INVOICE_ID] ?? 'N/A') . "\n";
                    echo "  Status: " . ($order[JsonKeys::STATUS] ?? 'N/A') . "\n";
                    echo "  Amount: " . ($order[JsonKeys::TOTAL_AMOUNT] ?? 0) . " " . ($order[JsonKeys::CURRENCY] ?? 'INR') . "\n";
                    if (isset($order['offer_discount']) && $order['offer_discount'] > 0) {
                        echo "  Offer Discount: " . ($order['offer_discount']) . " " . ($order[JsonKeys::CURRENCY] ?? 'INR') . "\n";
                    }
                }
            } else {
                // Fallback: display raw result if structure is unexpected
                print_r($result);
            }
        }
    } catch (Exception $e) {
        printException($e);
    }
}

// Only run the full example if this file is executed directly (not included)
if (basename($_SERVER['PHP_SELF']) === 'transaction-status.php') {
    // Validate configuration early
    $config = loadConfig();
    if (empty($config['access_key']) || $config['access_key'] === 'your_access_key_here') {
        printError("Please update example/config.php with your Nimbbl credentials.\n");
        printInfo("Copy config.php.example to config.php and update:\n");
        printInfo("  - access_key\n");
        printInfo("  - access_secret\n");
        printInfo("  - api_url (optional, defaults to UAT)\n");
        exit(1);
    }

    echo Colors::CYAN . Colors::BOLD . "=== Transaction Status Examples ===" . Colors::RESET . "\n\n";

    // Run Transaction Enquiry example
    echo Colors::BLUE . Colors::BOLD . "Step 1: Transaction Enquiry" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    transactionEnquiryExample();

    echo "\n" . Colors::CYAN . Colors::BOLD . "=== Transaction Status Examples Complete ===" . Colors::RESET . "\n";
    echo "\nKey Points:\n";
    echo "  • Transaction Status API gets the latest status of order and transactions\n";
    echo "  • Can query by order_id, invoice_id, or transaction_id\n";
    echo "  • Returns comprehensive transaction and order status information\n";
    echo "  • Use this to check the current state of a transaction\n";
    echo "\nFor more details, see: https://nimbbl.biz/docs/api-reference/transaction-enquiry-v-3/\n";
}
