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
    $callbackUrl = getInput("Enter Callback URL (optional): ", false);
    $paymentMode = getInput("Enter Payment Mode Code (net_banking/credit_card/etc): ", false) ?: 'net_banking';

    // Build payment data
    $paymentData = [
        JsonKeys::ORDER_ID => $orderId,
        CheckoutConstants::OPTION_KEY_PAYMENT_MODE_CODE => $paymentMode,
        CheckoutConstants::OPTION_KEY_CALLBACK_URL => $callbackUrl,
    ];

    // Payment mode specific data
    switch (strtolower($paymentMode)) {
        case CheckoutConstants::PAYMENT_MODE_NET_BANKING:
            printInfo("Bank Code is required for net_banking payment mode.\n");
            echo "   Common bank codes: HDFC, ICICI, SBI, AXIS, KOTAK, etc.\n";
            $bankCode = getInput("Enter Bank Code (default: HDFC): ", false) ?: 'HDFC';
            $paymentData[CheckoutConstants::OPTION_KEY_BANK_CODE] = $bankCode;
            break;

        case CheckoutConstants::PAYMENT_MODE_UPI:
            $paymentFlow = getInput("Enter UPI Payment Flow (intent/collect): ", false) ?: 'intent';
            $paymentData[CheckoutConstants::OPTION_KEY_PAYMENT_FLOW] = $paymentFlow;

            $upiId = getInput("Enter UPI ID (optional): ", false);
            if (!empty($upiId)) {
                $paymentData['upi_id'] = $upiId;
            }

            $upiAppCode = getInput("Enter UPI App Code (gpay/phonepe/paytm - optional): ", false);
            if (!empty($upiAppCode)) {
                $paymentData['upi_app_code'] = $upiAppCode;
            }
            break;

        case CheckoutConstants::PAYMENT_MODE_WALLET:
            $walletCode = getInput("Enter Wallet Code (e.g., FREECHARGE): ");
            if ($walletCode === null) {
                printError("Wallet Code is required for wallet payment mode.\n");
                return;
            }
            $paymentData['wallet_code'] = $walletCode;
            break;

        case CheckoutConstants::PAYMENT_MODE_CREDIT_CARD:
        case CheckoutConstants::PAYMENT_MODE_DEBIT_CARD:
            $cardNo = getInput("Enter Card Number: ");
            if ($cardNo === null) {
                printError("Card Number is required.\n");
                return;
            }
            $paymentData['card_no'] = $cardNo;

            $cardInputType = getInput("Enter Card Input Type (card_pan/token - default: card_pan): ", false) ?: 'card_pan';
            $paymentData['card_input_type'] = $cardInputType;

            $cvv = getInput("Enter CVV: ");
            if ($cvv === null) {
                printError("CVV is required.\n");
                return;
            }
            $paymentData['cvv'] = $cvv;

            $cardHolderName = getInput("Enter Card Holder Name (optional): ", false);
            if (!empty($cardHolderName)) {
                $paymentData['card_holder_name'] = $cardHolderName;
            }

            $expiry = getInput("Enter Expiry (MM/YY): ");
            if ($expiry === null) {
                printError("Expiry is required (MM/YY format).\n");
                return;
            }
            $paymentData['expiry'] = $expiry;
            break;

        default:
            printInfo("No specific additional data required for {$paymentMode} mode.\n");
            break;
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
        $otp = getInput("Enter OTP (optional): ", false);
        if (!empty($otp)) {
            $data['otp'] = $otp;
        }
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
        printInfo("  - api_host (optional, defaults to SDK base URL)\n");
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

