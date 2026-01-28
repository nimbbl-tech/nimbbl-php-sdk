#!/usr/bin/env php
<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Payments API Examples
 * 
 * This example demonstrates how to use the Payments API
 * 
 * This file can be:
 * 1. Executed standalone: php payments-examples.php
 * 2. Included from cli.php to use the functions: initiatePaymentExample(), completePaymentExample(), resendOtpExample()
 * 
 * API Documentation: https://nimbbl.biz/docs/category/api-reference/payments/
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';
require_once __DIR__ . '/utils/cli_output.php';

// All helper functions are available from cli_output.php
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\CheckoutConstants;

/**
 * Initiate Payment - Function to be called from cli.php or standalone
 */
function initiatePaymentExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);

    $orderId = getInput("Enter Order ID: ");
    if ($orderId === null) {
        printError("Order ID is required.\n");
        return;
    }
    $callbackUrl = getInput("Enter Callback URL (mandatory): ");
    if ($callbackUrl === null) {
        printError("Callback URL is required.\n");
        return;
    }
    $paymentMode = getInput("Enter Payment Mode Code (net_banking/credit_card/etc): ", false) ?: 'net_banking';

    // Build payment data
    $paymentData = [
        JsonKeys::ORDER_ID => $orderId,
        CheckoutConstants::OPTION_KEY_PAYMENT_MODE_CODE => $paymentMode,
        CheckoutConstants::OPTION_KEY_CALLBACK_URL => $callbackUrl,
    ];

    if (strtolower($paymentMode) === CheckoutConstants::PAYMENT_MODE_NET_BANKING) {
        printInfo("Bank Code is required for net_banking payment mode.\n");
        echo "   Common bank codes: HDFC, ICICI, SBI, AXIS, KOTAK, etc.\n";
        $bankCode = getInput("Enter Bank Code (default: HDFC): ", false) ?: 'HDFC';
        $paymentData[CheckoutConstants::OPTION_KEY_BANK_CODE] = $bankCode;
    } else {
        $bankCode = getInput("Enter Bank Code (optional, for net_banking only): ", false);
        if ($bankCode) {
            $paymentData[CheckoutConstants::OPTION_KEY_BANK_CODE] = $bankCode;
        }
    }

    try {
        // Initiate payment
        $result = $api->payments()->initiatePayment($paymentData);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Payment initiated successfully!\n");
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * Complete Payment - Function to be called from cli.php or standalone
 */
function completePaymentExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);

    $transactionId = getInput("Enter Transaction ID: ");
    if ($transactionId === null) {
        printError("Transaction ID is required.\n");
        return;
    }
    $paymentFlow = getInput("Enter Payment Flow (auto_debit/otp): ", false) ?: 'auto_debit';
    $data = [
        JsonKeys::TRANSACTION_ID => $transactionId,
        CheckoutConstants::OPTION_KEY_PAYMENT_FLOW => $paymentFlow
    ];

    if (strtolower($paymentFlow) === 'otp') {
        $otp = getInput("Enter OTP: ");
        if ($otp === null) {
            printError("OTP is required for OTP flow.\n");
            return;
        }
        $data['otp'] = $otp;
    }

    try {
        // Complete payment (native OTP/Pay Later flow)
        $result = $api->payments()->completePayment($data);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Payment completed successfully!\n");
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * Resend OTP - Function to be called from cli.php or standalone
 */
function resendOtpExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);



    $transactionId = getInput("Enter Transaction ID: ");
    if ($transactionId === null) {
        printError("Transaction ID is required.\n");
        return;
    }
    try {
        // Resend OTP for payment
        $result = $api->payments()->resendPaymentOtp([JsonKeys::TRANSACTION_ID => $transactionId]);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("OTP resent successfully!\n");
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

// Only run the full example if this file is executed directly (not included)
if (basename($_SERVER['PHP_SELF']) === 'payments-examples.php') {
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

    echo Colors::CYAN . Colors::BOLD . "=== Payments API Examples ===" . Colors::RESET . "\n\n";

    // Run Initiate Payment example
    echo Colors::BLUE . Colors::BOLD . "Step 1: Initiate Payment" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    initiatePaymentExample();

    echo "\n";

    // Run Complete Payment example
    echo Colors::BLUE . Colors::BOLD . "Step 2: Complete Payment" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    completePaymentExample();

    echo "\n";

    // Run Resend OTP example
    echo Colors::BLUE . Colors::BOLD . "Step 3: Resend OTP" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    resendOtpExample();

    echo "\n" . Colors::CYAN . Colors::BOLD . "=== Payments API Examples Complete ===" . Colors::RESET . "\n";
    echo "\nKey Points:\n";
    echo "  • Payment flow depends on the payment mode selected\n";
    echo "  • For most payment modes, customer completes payment on checkout page\n";
    echo "  • OTP flow is used for certain payment methods\n";
    echo "  • Complete Payment API is for Pay Later providers with native OTP\n";
    echo "\nFor more information, see: https://nimbbl.biz/docs/category/api-reference/payments/\n";
}

