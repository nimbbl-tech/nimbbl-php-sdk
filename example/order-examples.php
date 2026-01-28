#!/usr/bin/env php
<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Order Examples (Create + Get)
 * - Create Order: https://nimbbl.biz/docs/api-reference/create-an-order-v-3/
 * - Get Order:    https://nimbbl.biz/docs/api-reference/get-order-v-3/
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';
require_once __DIR__ . '/utils/cli_output.php';

// All helper functions are available from cli_output.php
use Nimbbl\Api\Common\JsonKeys;

/**
 * Create Order - Function to be called from cli.php or standalone
 */
function createOrderExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);

    printInfo("Enter order details:\n");
    $invoiceId = getInput("Enter Invoice ID (optional, auto-generated if blank): ", false);
    if (empty($invoiceId)) {
        // Auto-generate a unique invoice ID if user leaves it blank
        $invoiceId = 'INV-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
        printInfo("Generated Invoice ID: {$invoiceId}\n");
    }
    $totalAmount = getInput("Enter Total Amount: ");
    if ($totalAmount === null) {
        printError("Total Amount is required.\n");
        return;
    }
    $amountBeforeTax = getInput("Enter Amount Before Tax: ");
    if ($amountBeforeTax === null) {
        printError("Amount Before Tax is required.\n");
        return;
    }
    $tax = getInput("Enter Tax (default: 0): ", false) ?: '0';
    $currency = getInput("Enter Currency (default: INR): ", false) ?: 'INR';

    // User details
    printInfo("\nUser Details:\n");
    $email = getInput("Enter User Email (default: user@example.com): ", false) ?: 'user@example.com';
    $firstName = getInput("Enter User First Name (default: Test): ", false) ?: 'Test';
    $lastName = getInput("Enter User Last Name (optional): ", false);
    $countryCode = getInput("Enter Country Code (default: +91): ", false) ?: '+91';
    $mobileNumber = getInput("Enter Mobile Number (default: 9999999999): ", false) ?: '9999999999';

    // Build order data
    $orderData = [
        JsonKeys::INVOICE_ID => $invoiceId,
        JsonKeys::AMOUNT => floatval($totalAmount),
        JsonKeys::TOTAL_AMOUNT => floatval($totalAmount),
        'amount_before_tax' => floatval($amountBeforeTax),
        'tax' => floatval($tax),
        JsonKeys::CURRENCY => $currency,
        'user' => [
            JsonKeys::EMAIL => $email,
            JsonKeys::FIRST_NAME => $firstName,
            'country_code' => $countryCode,
            JsonKeys::MOBILE_NUMBER => $mobileNumber
        ]
    ];

    if ($lastName) {
        $orderData['user'][JsonKeys::LAST_NAME] = $lastName;
    }

    try {
        // Merchant token is automatically generated and used for authentication
        $order = $api->orders()->createOrder($orderData);
        if (isset($order['error'])) {
            printError("Error: " . print_r($order['error'], true) . "\n");
        } else {
            printSuccess("Order created successfully!\n");
            $orderId = $order[JsonKeys::ORDER_ID] ?? $order[JsonKeys::NIMBBL_ORDER_ID] ?? 'N/A';
            $invoiceIdResult = $order[JsonKeys::INVOICE_ID] ?? 'N/A';
            $orderToken = $order[JsonKeys::TOKEN] ?? 'N/A';
            echo "   Order ID: {$orderId}\n";
            echo "   Invoice ID: {$invoiceIdResult}\n";
            echo "   Order Token: {$orderToken}\n";
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * Get Order by ID - Function to be called from cli.php or standalone
 */
function getOrderByIdExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);
    $orderId = getInput("Enter Order ID: ");
    if ($orderId === null) {
        printError("Order ID is required.\n");
        return;
    }
    try {
        // Merchant token is automatically generated and used for authentication
        $order = $api->orders()->getOrderById($orderId);
        if (isset($order['error'])) {
            printError("Error: " . print_r($order['error'], true) . "\n");
        } else {
            printSuccess("Order retrieved successfully!\n");
            $oid = $order[JsonKeys::ORDER_ID] ?? $order[JsonKeys::NIMBBL_ORDER_ID] ?? 'N/A';
            $iid = $order[JsonKeys::INVOICE_ID] ?? 'N/A';
            $stat = $order[JsonKeys::STATUS] ?? 'N/A';
            $amt = $order[JsonKeys::TOTAL_AMOUNT] ?? 0;
            $curr = $order[JsonKeys::CURRENCY] ?? 'INR';
            echo "   Order ID: {$oid}\n";
            echo "   Invoice ID: {$iid}\n";
            echo "   Status: {$stat}\n";
            echo "   Amount: {$amt} {$curr}\n";
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * Get Order by Invoice ID - Function to be called from cli.php or standalone
 */
function getOrderByInvoiceIdExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);
    $invoiceId = getInput("Enter Invoice ID: ");
    if ($invoiceId === null) {
        printError("Invoice ID is required.\n");
        return;
    }
    try {
        // Merchant token is automatically generated and used for authentication
        $order = $api->orders()->getOrderByInvoiceId($invoiceId);
        if (isset($order['error'])) {
            printError("Error: " . print_r($order['error'], true) . "\n");
        } else {
            printSuccess("Order retrieved successfully!\n");
            $oid = $order[JsonKeys::ORDER_ID] ?? $order[JsonKeys::NIMBBL_ORDER_ID] ?? 'N/A';
            $iid = $order[JsonKeys::INVOICE_ID] ?? 'N/A';
            $stat = $order[JsonKeys::STATUS] ?? 'N/A';
            $amt = $order[JsonKeys::TOTAL_AMOUNT] ?? 0;
            $curr = $order[JsonKeys::CURRENCY] ?? 'INR';
            echo "   Order ID: {$oid}\n";
            echo "   Invoice ID: {$iid}\n";
            echo "   Status: {$stat}\n";
            echo "   Amount: {$amt} {$curr}\n";
        }
    } catch (Exception $e) {
        printException($e);
    }
}

// Only run the full example if this file is executed directly (not included)
if (basename($_SERVER['PHP_SELF']) === 'order-examples.php') {
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

    echo Colors::CYAN . Colors::BOLD . "=== Order Examples (Create + Get) ===" . Colors::RESET . "\n\n";

    // Run Create Order example
    echo Colors::BLUE . Colors::BOLD . "Step 1: Create Order" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    createOrderExample();

    echo "\n";

    // Run Get Order by ID example
    echo Colors::BLUE . Colors::BOLD . "Step 2: Get Order by ID" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    getOrderByIdExample();

    echo "\n";

    // Run Get Order by Invoice ID example
    echo Colors::BLUE . Colors::BOLD . "Step 3: Get Order by Invoice ID" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    getOrderByInvoiceIdExample();

    echo "\n" . Colors::CYAN . Colors::BOLD . "=== Examples Complete ===" . Colors::RESET . "\n";
}
