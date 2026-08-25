#!/usr/bin/env php
<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Capture & Void Examples (Pre-Authorization)
 *
 * Demonstrates how to capture or void a pre-authorized payment.
 *
 * A pre-authorization holds funds on the customer's instrument without collecting
 * them. It is only available when the sub-merchant is configured with
 * capture_mode=manual (contact Nimbbl to enable). After the customer completes
 * checkout you will receive a transaction in the `authorized` status (via the
 * `payment_authorized` webhook / callback). You then either:
 *   - CAPTURE  -> collect the held funds (full amount only), or
 *   - VOID     -> release the hold without charging the customer.
 *
 * Both are asynchronous: a `pending` status is normal — confirm the final outcome
 * via the capture_success / void_success webhook or the Transaction Enquiry API.
 *
 * This file can be:
 * 1. Executed standalone: php capture-void-examples.php
 * 2. Included from cli.php to use: captureExample() / voidExample()
 *
 * API Documentation:
 *   - https://nimbbl.biz/docs/api-reference/capture-a-payment-v-3/
 *   - https://nimbbl.biz/docs/api-reference/void-a-payment-v-3/
 */

use Nimbbl\Api\Common\JsonKeys;

/**
 * Capture a pre-authorized payment - Function to be called from cli.php or standalone
 */
function captureExample()
{
    $config = loadConfig();
    $api = initApi($config);

    printInfo("Capture releases the held funds for collection (full amount only).\n");
    $transactionId = getInput("Enter the authorized Transaction ID to capture: ", false);
    if (!$transactionId) {
        printError("Transaction ID is required.\n");
        return;
    }
    $comment = getInput("Enter Comment (optional, e.g. 'Goods dispatched'): ", false);

    $data = [JsonKeys::TRANSACTION_ID => $transactionId];
    if ($comment) {
        $data[JsonKeys::COMMENT] = $comment;
    }

    try {
        $result = $api->payments()->capture($data);
        if (isset($result[JsonKeys::ERROR])) {
            printError("Error: " . print_r($result[JsonKeys::ERROR], true) . "\n");
            return;
        }
        printSuccess("Capture request accepted!\n");
        echo "  Original Payment Txn: " . ($result[JsonKeys::ORIGINAL_PAYMENT_TRANSACTION_ID] ?? 'N/A') . "\n";
        echo "  Capture Txn ID:       " . ($result[JsonKeys::TRANSACTION_ID] ?? 'N/A') . "\n";
        echo "  Capture Status:       " . ($result[JsonKeys::CAPTURE_STATUS] ?? 'N/A') . "\n";
        echo "  Order ID:             " . ($result[JsonKeys::ORDER_ID] ?? 'N/A') . "\n";
        if (($result[JsonKeys::CAPTURE_STATUS] ?? '') === 'pending') {
            printInfo("Status is 'pending' — this is normal. Confirm via the capture_success webhook "
                . "or Transaction Enquiry API before treating the order as paid.\n");
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * Void a pre-authorized payment - Function to be called from cli.php or standalone
 */
function voidExample()
{
    $config = loadConfig();
    $api = initApi($config);

    printInfo("Void cancels the authorization and releases the held funds. This cannot be undone.\n");
    $transactionId = getInput("Enter the authorized Transaction ID to void: ", false);
    if (!$transactionId) {
        printError("Transaction ID is required.\n");
        return;
    }
    $comment = getInput("Enter Comment (optional, e.g. 'Customer cancelled'): ", false);

    $data = [JsonKeys::TRANSACTION_ID => $transactionId];
    if ($comment) {
        $data[JsonKeys::COMMENT] = $comment;
    }

    try {
        $result = $api->payments()->void($data);
        if (isset($result[JsonKeys::ERROR])) {
            printError("Error: " . print_r($result[JsonKeys::ERROR], true) . "\n");
            return;
        }
        printSuccess("Void request accepted!\n");
        echo "  Original Payment Txn: " . ($result[JsonKeys::ORIGINAL_PAYMENT_TRANSACTION_ID] ?? 'N/A') . "\n";
        echo "  Void Txn ID:          " . ($result[JsonKeys::TRANSACTION_ID] ?? 'N/A') . "\n";
        echo "  Void Status:          " . ($result[JsonKeys::VOID_STATUS] ?? 'N/A') . "\n";
        echo "  Order ID:             " . ($result[JsonKeys::ORDER_ID] ?? 'N/A') . "\n";
        if (($result[JsonKeys::VOID_STATUS] ?? '') === 'pending') {
            printInfo("Status is 'pending' — this is normal. Confirm via the void_success webhook "
                . "or Transaction Enquiry API.\n");
        }
    } catch (Exception $e) {
        printException($e);
    }
}

// Only run the full example if this file is executed directly (not included)
if (basename($_SERVER['PHP_SELF']) === 'capture-void-examples.php') {
    $config = loadConfig();
    if (empty($config['access_key']) || $config['access_key'] === 'your_access_key_here') {
        printError("Please update example/config.php with your Nimbbl credentials.\n");
        exit(1);
    }

    echo Colors::CYAN . Colors::BOLD . "=== Pre-Auth Capture / Void Examples ===" . Colors::RESET . "\n\n";

    $action = getInput("Choose action - (c)apture or (v)oid: ", false);
    if (strtolower((string) $action) === 'v') {
        voidExample();
    } else {
        captureExample();
    }

    echo "\nImportant Notes:\n";
    echo "  • Pre-auth requires capture_mode=manual on your sub-merchant (contact Nimbbl).\n";
    echo "  • Capture and void act on a transaction in the 'authorized' status only.\n";
    echo "  • Both are async — a 'pending' status is normal; confirm via webhook / enquiry.\n";
    echo "  • Never fulfil an order on 'authorized' alone — capture first.\n";
}
