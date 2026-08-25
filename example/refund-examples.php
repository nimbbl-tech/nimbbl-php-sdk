#!/usr/bin/env php
<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Refund Examples
 * 
 * This example demonstrates how to process refunds using the Nimbbl PHP SDK
 * 
 * This file can be:
 * 1. Executed standalone: php refund-examples.php
 * 2. Included from cli.php to use the function: initiateRefundExample()
 * 
 * API Documentation: https://nimbbl.biz/docs/api-reference/refund-a-payment-v-3/
 */

// All helper functions are available from cli_output.php
use Nimbbl\Api\Common\JsonKeys;

/**
 * Initiate Refund - Function to be called from cli.php or standalone
 */
function initiateRefundExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);

    printInfo("Enter either transaction_id OR invoice_id (at least one required)\n");
    $transactionId = getInput("Enter Transaction ID (or press Enter to skip): ", false);
    $invoiceId = getInput("Enter Invoice ID (or press Enter to skip): ", false);
    if (!$transactionId && !$invoiceId) {
        printError("Either Transaction ID or Invoice ID is required.\n");
        return;
    }
    $refundAmount = getInput("Enter Refund Amount (or press Enter for full refund): ", false);
    $comment = getInput("Enter Refund Comment (optional): ", false);
    $refundRequestId = getInput("Enter Refund Request ID (optional, for idempotency): ", false);
    $data = [];
    if ($transactionId) {
        $data[JsonKeys::TRANSACTION_ID] = $transactionId;
    }
    if ($invoiceId) {
        $data[JsonKeys::INVOICE_ID] = $invoiceId;
    }
    if ($refundAmount) {
        $data[JsonKeys::REFUND_AMOUNT] = floatval($refundAmount);
    }
    if ($comment) {
        $data['comment'] = $comment;
    }
    if ($refundRequestId) {
        $data['refund_request_id'] = $refundRequestId;
    }
    // Order line items support (optional)
    $includeOrderLineItems = getInput("Include order line items? (y/N): ", false);
    if ($includeOrderLineItems && strtolower($includeOrderLineItems) === 'y') {
        $orderLineItems = [];
        while (true) {
            $skuId = getInput("Enter SKU ID (or press Enter to finish): ", false);
            if (!$skuId)
                break;
            $item = ['sku_id' => $skuId];
            $serialNumbers = getInput("Enter Serial Numbers (comma-separated, optional): ", false);
            if ($serialNumbers) {
                $item['serial_numbers'] = array_map('trim', explode(',', $serialNumbers));
            }
            $orderLineItems[] = $item;
        }
        if (!empty($orderLineItems)) {
            $data['order_line_items'] = $orderLineItems;
        }
    }
    try {
        $result = $api->refunds()->initiateRefund($data);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Refund initiated successfully!\n");
            // Display refund information
            if (is_array($result)) {
                echo "  Refund ID: " . ($result['refund_id'] ?? $result['nimbbl_refund_id'] ?? 'N/A') . "\n";
                echo "  Transaction ID: " . ($result[JsonKeys::TRANSACTION_ID] ?? $result[JsonKeys::NIMBBL_TRANSACTION_ID] ?? 'N/A') . "\n";
                echo "  Refund Amount: " . ($result[JsonKeys::REFUND_AMOUNT] ?? 0) . " " . ($result[JsonKeys::CURRENCY] ?? 'INR') . "\n";
                echo "  Status: " . ($result[JsonKeys::STATUS] ?? 'N/A') . "\n";
                if (isset($result['comment'])) {
                    echo "  Comment: " . $result['comment'] . "\n";
                }
            } else {
                print_r($result);
            }
        }
    } catch (Exception $e) {
        printException($e);
    }
}

// Only run the full example if this file is executed directly (not included)
if (basename($_SERVER['PHP_SELF']) === 'refund-examples.php') {
    // Validate configuration early
    $config = loadConfig();
    if (empty($config['access_key']) || $config['access_key'] === 'your_access_key_here') {
        printError("Please update example/config.php with your Nimbbl credentials.\n");
        printInfo("Copy config.php.example to config.php and update:\n");
        printInfo("  - access_key\n");
        printInfo("  - access_secret\n");
        printInfo("  - api_host (optional, defaults to SDK base URL)\n");
        exit(1);
    }

    echo Colors::CYAN . Colors::BOLD . "=== Refund Examples ===" . Colors::RESET . "\n\n";

    // Run Initiate Refund example
    echo Colors::BLUE . Colors::BOLD . "Step 1: Initiate Refund" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    initiateRefundExample();

    echo "\n" . Colors::CYAN . Colors::BOLD . "=== Refund Examples Complete ===" . Colors::RESET . "\n";
    echo "\nImportant Notes:\n";
    echo "  • Refund transactions once requested cannot be rolled back\n";
    echo "  • Be very sure of the amount before initiating a refund\n";
    echo "  • Use refund_request_id to avoid duplicate refund requests\n";
    echo "  • Use Transaction Status API to check refund status\n";
    echo "\nFor more details, see: https://nimbbl.biz/docs/api-reference/refund-a-payment-v-3/\n";
}
