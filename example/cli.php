#!/usr/bin/env php
<?php
/**
 * Nimbbl PHP SDK - Interactive CLI Menu
 * 
 * Interactive command-line interface for testing all SDK features
 * 
 * Usage: php cli.php
 */

// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';
require_once __DIR__ . '/utils/cli_output.php';

function readInputLine(): ?string
{
    $line = fgets(STDIN);
    if ($line === false) {
        return null;
    }

    return trim($line);
}

function printMenu()
{
    echo Colors::BOLD . "\nSelect an API to test:\n" . Colors::RESET . "\n";
    echo Colors::YELLOW . "=== Authentication ===\n" . Colors::RESET;
    echo "1.  Generate Token\n";
    echo "\n" . Colors::YELLOW . "=== Orders API ===\n" . Colors::RESET;
    echo "2.  Create Order\n";
    echo "3.  Get Order by ID\n";
    echo "4.  Get Order by Invoice ID\n";
    echo "\n" . Colors::YELLOW . "=== Payments API ===\n" . Colors::RESET;
    echo "5.  Initiate Payment\n";
    echo "6.  Complete Payment\n";
    echo "7.  Resend OTP\n";
    echo "8.  Capture Payment\n";
    echo "9.  Void Payment\n";
    echo "\n" . Colors::YELLOW . "=== Payment Links API ===\n" . Colors::RESET;
    echo "10. Create Payment Link\n";
    echo "11. Update Payment Link\n";
    echo "12. Payment Link Enquiry\n";
    echo "13. Payment Link Actions\n";
    echo "\n" . Colors::YELLOW . "=== Addresses API ===\n" . Colors::RESET;
    echo "14. List Addresses\n";
    echo "15. Create Address\n";
    echo "16. Update Address\n";
    echo "17. Delete Address\n";
    echo "18. Import Addresses\n";
    echo "19. Check Address Eligibility\n";
    echo "20. Link Order to Address\n";
    echo "\n" . Colors::YELLOW . "=== Refunds API ===\n" . Colors::RESET;
    echo "21. Initiate Refund\n";
    echo "\n" . Colors::YELLOW . "=== Transactions API ===\n" . Colors::RESET;
    echo "22. Transaction Enquiry\n";
    echo "\n" . Colors::YELLOW . "=== Checkout Utilities API ===\n" . Colors::RESET;
    echo "23. List Payment Modes\n";
    echo "24. List Banks\n";
    echo "25. List Wallets\n";
    echo "26. List EMIs\n";
    echo "27. Get Offers\n";
    echo "28. Get Card BIN Data\n";
    echo "29. Get Card Details\n";
    echo "30. Validate UPI VPA\n";
    echo "31. Get UPI App Details\n";
    echo "\n" . Colors::YELLOW . "=== Webhooks ===\n" . Colors::RESET;
    echo "32. Webhook Handling\n";
    echo "\n" . Colors::YELLOW . "=== Examples ===\n" . Colors::RESET;
    echo "33. Encryption Examples\n";
    echo "34. Exception Handling Examples\n";
    echo "\n0.  Exit\n\n";
    echo Colors::CYAN . "Enter your choice: " . Colors::RESET;
}

// Load configuration
$config = loadConfig();

// Validate configuration
if (empty($config['access_key']) || $config['access_key'] === 'your_access_key_here') {
    printError("Please update example/config.php with your Nimbbl credentials.\n");
    printInfo("Copy config.php.example to config.php and update:\n");
    printInfo("  - access_key\n");
    printInfo("  - access_secret\n");
    printInfo("  - api_host (optional, defaults to SDK base URL)\n");
    exit(1);
}

printHeader("=== Nimbbl PHP SDK - Interactive CLI Menu ===");

// Initialize Nimbbl API (with log file from config)
$api = initApi($config);

// Main menu loop
while (true) {
    printMenu();
    $choice = readInputLine();
    echo "\n";

    if ($choice === null) {
        printInfo("No interactive input detected. Exiting.\n");
        exit(0);
    }

    if (empty($choice) || $choice === '0') {
        printInfo("Goodbye!\n");
        exit(0);
    }

    printSeparator();

    try {
        switch ($choice) {
            case '1':
                echo Colors::BOLD . "Generate Token\n" . Colors::RESET;
                require_once __DIR__ . '/generate-token.php';
                generateTokenExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/generate-token-v-3/', 'Generate Token API');
                break;

            case '2':
                echo Colors::BOLD . "Create Order\n" . Colors::RESET;
                require_once __DIR__ . '/order-examples.php';
                createOrderExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/create-an-order-v-3/', 'Create Order API');
                break;

            case '3':
                echo Colors::BOLD . "Get Order by ID\n" . Colors::RESET;
                require_once __DIR__ . '/order-examples.php';
                getOrderByIdExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/get-order-v-3/', 'Get Order API');
                break;

            case '4':
                echo Colors::BOLD . "Get Order by Invoice ID\n" . Colors::RESET;
                require_once __DIR__ . '/order-examples.php';
                getOrderByInvoiceIdExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/get-order-v-3/', 'Get Order API');
                break;

            // Payments API
            case '5':
                echo Colors::BOLD . "Initiate Payment\n" . Colors::RESET;
                require_once __DIR__ . '/payments-examples.php';
                initiatePaymentExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/initiate-a-payment-v-3/', 'Initiate Payment API');
                break;

            case '6':
                echo Colors::BOLD . "Complete Payment\n" . Colors::RESET;
                require_once __DIR__ . '/payments-examples.php';
                completePaymentExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/complete-payment-v-3/', 'Complete Payment API');
                break;

            case '7':
                echo Colors::BOLD . "Resend OTP\n" . Colors::RESET;
                require_once __DIR__ . '/payments-examples.php';
                resendOtpExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/resend-otp-v-3/', 'Resend OTP API');
                break;

            // Payments API - Pre-Auth Capture / Void
            case '8':
                echo Colors::BOLD . "Capture Payment\n" . Colors::RESET;
                require_once __DIR__ . '/capture-void-examples.php';
                captureExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/capture-a-payment-v-3/', 'Capture a Payment API');
                break;

            case '9':
                echo Colors::BOLD . "Void Payment\n" . Colors::RESET;
                require_once __DIR__ . '/capture-void-examples.php';
                voidExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/void-a-payment-v-3/', 'Void a Payment API');
                break;

            // Payment Links API
            case '10':
                echo Colors::BOLD . "Create Payment Link\n" . Colors::RESET;
                require_once __DIR__ . '/payment-links-examples.php';
                createPaymentLinkExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/create-a-payment-link-v-3/', 'Create Payment Link API');
                break;

            case '11':
                echo Colors::BOLD . "Update Payment Link\n" . Colors::RESET;
                require_once __DIR__ . '/payment-links-examples.php';
                updatePaymentLinkExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/update-a-payment-link-v-3/', 'Update Payment Link API');
                break;

            case '12':
                echo Colors::BOLD . "Payment Link Enquiry\n" . Colors::RESET;
                require_once __DIR__ . '/payment-links-examples.php';
                enquiryPaymentLinkExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/payment-link-enquiry-v-3/', 'Payment Link Enquiry API');
                break;

            case '13':
                echo Colors::BOLD . "Payment Link Actions\n" . Colors::RESET;
                require_once __DIR__ . '/payment-links-examples.php';
                performPaymentLinkActionsExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/payment-link-actions-v-3/', 'Payment Link Actions API');
                break;

            // Addresses API - cases 14-20
            case '14':
                echo Colors::BOLD . "List Addresses\n" . Colors::RESET;
                echo Colors::CYAN . "Query Parameters: user_id, amount, currency\n" . Colors::RESET;
                require_once __DIR__ . '/addresses-examples.php';
                listAddressesExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/list-addresses-v-3/', 'List Addresses API');
                break;

            case '15':
                echo Colors::BOLD . "Create Address\n" . Colors::RESET;
                require_once __DIR__ . '/addresses-examples.php';
                createAddressExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/create-an-address-v-3/', 'Create Address API');
                break;

            case '16':
                echo Colors::BOLD . "Update Address\n" . Colors::RESET;
                require_once __DIR__ . '/addresses-examples.php';
                updateAddressExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/update-an-address-v-3/', 'Update Address API');
                break;

            case '17':
                echo Colors::BOLD . "Delete Address\n" . Colors::RESET;
                require_once __DIR__ . '/addresses-examples.php';
                deleteAddressExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/delete-an-address-v-3/', 'Delete Address API');
                break;

            case '18':
                echo Colors::BOLD . "Import Addresses\n" . Colors::RESET;
                require_once __DIR__ . '/addresses-examples.php';
                importAddressesExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/import-addresses-v-3/', 'Import Addresses API');
                break;

            case '19':
                echo Colors::BOLD . "Check Address Eligibility\n" . Colors::RESET;
                require_once __DIR__ . '/addresses-examples.php';
                checkAddressEligibilityExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/check-address-eligibility-v-3/', 'Check Address Eligibility API');
                break;

            case '20':
                echo Colors::BOLD . "Link Order to Address\n" . Colors::RESET;
                require_once __DIR__ . '/addresses-examples.php';
                linkAddressWithOrderExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/link-address-with-order-v-3/', 'Link Address with Order API');
                break;

            // Refunds API
            case '21':
                echo Colors::BOLD . "Initiate Refund\n" . Colors::RESET;
                require_once __DIR__ . '/refund-examples.php';
                initiateRefundExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/refund-a-payment-v-3/', 'Initiate Refund API');
                break;

            // Transactions API
            case '22':
                echo Colors::BOLD . "Transaction Enquiry\n" . Colors::RESET;
                require_once __DIR__ . '/transaction-status.php';
                transactionEnquiryExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/transaction-enquiry-v-3/', 'Transaction Enquiry API');
                break;

            // Checkout Utilities API - cases 23-31
            case '23':
                echo Colors::BOLD . "List Payment Modes\n" . Colors::RESET;
                require_once __DIR__ . '/checkout-utilities-examples.php';
                listPaymentModesExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/list-of-payment-modes-v-3/', 'List Payment Modes API');
                break;

            case '24':
                echo Colors::BOLD . "List Banks\n" . Colors::RESET;
                require_once __DIR__ . '/checkout-utilities-examples.php';
                listBanksExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/list-of-banks-v-3/', 'List Banks API');
                break;

            case '25':
                echo Colors::BOLD . "List Wallets\n" . Colors::RESET;
                require_once __DIR__ . '/checkout-utilities-examples.php';
                listWalletsExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/list-of-wallets-v-3/', 'List Wallets API');
                break;

            case '26':
                echo Colors::BOLD . "List EMIs\n" . Colors::RESET;
                require_once __DIR__ . '/checkout-utilities-examples.php';
                listEMIsExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/list-of-em-is-v-3/', 'List EMIs API');
                break;

            case '27':
                echo Colors::BOLD . "Get Offers\n" . Colors::RESET;
                require_once __DIR__ . '/checkout-utilities-examples.php';
                getOffersExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/offers-v-3/', 'Get Offers API');
                break;

            case '28':
                echo Colors::BOLD . "Get Card BIN Data\n" . Colors::RESET;
                require_once __DIR__ . '/checkout-utilities-examples.php';
                getCardBinDataExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/get-card-bin-data-v-3/', 'Get Card BIN Data API');
                break;

            case '29':
                echo Colors::BOLD . "Get Card Details\n" . Colors::RESET;
                require_once __DIR__ . '/checkout-utilities-examples.php';
                getCardDetailsExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/get-card-details-v-3/', 'Get Card Details API');
                break;

            case '30':
                echo Colors::BOLD . "Validate UPI VPA\n" . Colors::RESET;
                require_once __DIR__ . '/checkout-utilities-examples.php';
                validateUpiVpaExample();
                printDocLink('https://nimbbl.biz/docs/api-reference/validate-upi-vpa-v-3/', 'Validate UPI VPA API');
                break;

            case '31':
                echo Colors::BOLD . "Get UPI App Details\n" . Colors::RESET;
                require_once __DIR__ . '/checkout-utilities-examples.php';
                getUpiAppDetailsExample();
                break;

            // Webhooks
            case '32':
                echo Colors::BOLD . "Webhook Handling\n" . Colors::RESET;
                require_once __DIR__ . '/webhook-handler.php';
                displayWebhookInfo();
                printDocLink('https://nimbbl.biz/docs/standard-checkout/completing-integration/keeping-system-updated/', 'Webhooks Documentation');
                break;

            // Examples
            case '33':
                echo Colors::BOLD . "Encryption Examples\n" . Colors::RESET;
                require_once __DIR__ . '/encryption-examples.php';
                runEncryptionExamples();
                printDocLink('https://nimbbl.biz/docs/guides/encrypt-decrypt-payload/', 'Encryption/Decryption Guide');
                break;

            case '34':
                echo Colors::BOLD . "Exception Handling Examples\n" . Colors::RESET;
                require_once __DIR__ . '/exception-handling-examples.php';
                break;

            default:
                printError("Invalid choice. Please select a valid number from the menu.\n");
                break;
        }
    } catch (\Throwable $e) {
        printException($e);
        if (!empty($config['debug_logging'])) {
            echo "Check logs/nimbbl_debug.log for details.\n";
        }
    }

    printSeparator();
    echo "\nPress Enter to continue...";
    if (fgets(STDIN) === false) {
        echo "\n";
        exit(0);
    }
    echo "\n";
}
