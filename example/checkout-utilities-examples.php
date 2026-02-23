#!/usr/bin/env php
<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Checkout Utilities API Examples
 * 
 * This example demonstrates how to use the Checkout Utilities API
 * 
 * This file can be:
 * 1. Executed standalone: php checkout-utilities-examples.php
 * 2. Included from cli.php to use the functions: listPaymentModesExample(), listBanksExample(), listWalletsExample(), listEMIsExample(), getOffersExample(), getCardBinDataExample(), getCardDetailsExample(), validateUpiVpaExample(), getUpiAppDetailsExample()
 * 
 * API Documentation: https://nimbbl.biz/docs/category/api-reference/checkout-utilities/
 */

// All helper functions are available from cli_output.php
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\CheckoutConstants;

/**
 * List Payment Modes - Function to be called from cli.php or standalone
 */
function listPaymentModesExample()
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
        $result = $api->checkoutUtilities()->listPaymentModes([JsonKeys::ORDER_ID => $orderId]);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Payment modes retrieved successfully!\n");
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * List Banks - Function to be called from cli.php or standalone
 */
function listBanksExample()
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
        $result = $api->checkoutUtilities()->listBanks([JsonKeys::ORDER_ID => $orderId]);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Banks retrieved successfully!\n");
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * List Wallets - Function to be called from cli.php or standalone
 */
function listWalletsExample()
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
        $result = $api->checkoutUtilities()->listWallets([JsonKeys::ORDER_ID => $orderId]);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Wallets retrieved successfully!\n");
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * List EMIs - Function to be called from cli.php or standalone
 */
function listEMIsExample()
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
        $result = $api->checkoutUtilities()->listEMIs([JsonKeys::ORDER_ID => $orderId]);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("EMIs retrieved successfully!\n");
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * Get Offers - Function to be called from cli.php or standalone
 */
function getOffersExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);


    $orderId = getInput("Enter Order ID: ");
    if ($orderId === null) {
        printError("Order ID is required.\n");
        return;
    }
    $paymentModeCode = getInput("Enter Payment Mode Code (card/net_banking/wallet/upi/pay_later/all): ");
    if ($paymentModeCode === null) {
        printError("Payment Mode Code is required.\n");
        return;
    }
    $data = [
        JsonKeys::ORDER_ID => $orderId,
        CheckoutConstants::OPTION_KEY_PAYMENT_MODE_CODE => $paymentModeCode
    ];
    // Optional fields
    $currency = getInput("Enter Currency (optional, press Enter to skip): ", false);
    if ($currency) {
        $data[JsonKeys::CURRENCY] = $currency;
    }
    // Additional fields based on payment mode
    if ($paymentModeCode === 'card') {
        $cardInputType = getInput("Enter Card Input Type (card_pan/merchant_network_token/nimbbl_token_id, optional): ", false);
        if ($cardInputType) {
            $data['card_input_type'] = $cardInputType;
            $card = [];

            if ($cardInputType === 'card_pan') {
                $cardNo = getInput("Enter Card Number (optional): ", false);
                if ($cardNo) {
                    $card['card_no'] = $cardNo;
                }
            } elseif ($cardInputType === 'merchant_network_token') {
                $networkToken = getInput("Enter Network Token (optional): ", false);
                if ($networkToken) {
                    $card['network_token'] = $networkToken;
                }
            } elseif ($cardInputType === 'nimbbl_token_id') {
                $nimbblTokenId = getInput("Enter Nimbbl Token ID (optional): ", false);
                if ($nimbblTokenId) {
                    $card['nimbbl_token_id'] = $nimbblTokenId;
                }
            }

            // Add currency to card object if provided
            if ($currency) {
                $card['currency'] = $currency;
            }

            if (!empty($card)) {
                $data['card'] = $card;
            }
        }
    } elseif ($paymentModeCode === CheckoutConstants::PAYMENT_MODE_NET_BANKING) {
        printInfo("Bank Code is required for net_banking payment mode.");
        echo "   Common bank codes: HDFC, ICICI, SBI, AXIS, KOTAK, etc.\n";
        $bankCode = getInput("Enter Bank Code (default: HDFC): ", false) ?: 'HDFC';
        $data[CheckoutConstants::OPTION_KEY_BANK_CODE] = $bankCode;
    } elseif ($paymentModeCode === CheckoutConstants::PAYMENT_MODE_WALLET) {
        $walletCode = getInput("Enter Wallet Code (optional, press Enter to skip): ", false);
        if ($walletCode) {
            $data[CheckoutConstants::OPTION_KEY_WALLET_CODE] = $walletCode;
        }
    } elseif ($paymentModeCode === CheckoutConstants::PAYMENT_MODE_UPI) {
        $upiId = getInput("Enter UPI ID (optional, press Enter to skip): ", false);
        if ($upiId) {
            $data[CheckoutConstants::OPTION_KEY_UPI_ID] = $upiId;
        }
    }
    try {
        $result = $api->checkoutUtilities()->getOffers($data);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Offers retrieved successfully!\n");
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * Get Card BIN Data - Function to be called from cli.php or standalone
 */
function getCardBinDataExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);


    $cardBin = getInput("Enter Card BIN (first 6 digits): ");
    if ($cardBin === null) {
        printError("Card BIN is required.\n");
        return;
    }
    $orderId = getInput("Enter Order ID (optional, press Enter to skip): ", false);
    $data = ['card_bin' => $cardBin];
    if ($orderId) {
        $data[JsonKeys::ORDER_ID] = $orderId;
    }
    try {
        $result = $api->checkoutUtilities()->getCardBinData($data);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Card BIN data retrieved successfully!\n");
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * Get Card Details - Function to be called from cli.php or standalone
 */
function getCardDetailsExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);


    printInfo("This API requires RSA-encrypted card details.\n");
    printInfo("You need to:\n");
    printInfo("1. Get the Nimbbl public key for encryption (contact help@nimbbl.biz)\n");
    printInfo("2. Format card details as: {\"card_no\":\"4111111111111111\",\"cvv\":\"123\",\"card_holder_name\":\"test\",\"expiry\":\"11/22\"}\n");
    printInfo("3. Encrypt using RSA encryption\n");
    $action = getInput("Enter action (default: PAR): ", false);
    if (empty($action)) {
        $action = 'PAR';
    }
    $cardInputType = getInput("Enter card_input_type (default: card_pan): ", false);
    if (empty($cardInputType)) {
        $cardInputType = 'card_pan';
    }
    $cardDetails = getInput("Enter RSA-encrypted card_details: ");
    if ($cardDetails === null) {
        printError("RSA-encrypted card details are required.\n");
        return;
    }
    $data = [
        'action' => $action,
        'card_input_type' => $cardInputType,
        'card_details' => $cardDetails
    ];
    // Optional device details
    $includeDevice = getInput("Include device details? (y/N): ", false);
    if ($includeDevice && strtolower($includeDevice) === 'y') {
        $device = [];
        $device['accept_header'] = getInput("Accept Header (optional): ", false);
        $device['user_agent'] = getInput("User Agent (optional): ", false);
        $device['browser_language'] = getInput("Browser Language (optional): ", false);
        $device['browser_javascript_enabled'] = getInput("JavaScript Enabled (true/false, optional): ", false);
        $device['browser_java_enabled'] = getInput("Java Enabled (true/false, optional): ", false);
        $device['browser'] = getInput("Browser Name (optional): ", false);
        $device['browser_tz'] = getInput("Browser Timezone (optional): ", false);
        $device['browser_color_depth'] = getInput("Color Depth (optional): ", false);
        $device['browser_screen_height'] = getInput("Screen Height (optional): ", false);
        $device['browser_screen_width'] = getInput("Screen Width (optional): ", false);
        $device['fingerprint'] = getInput("Device Fingerprint (optional): ", false);
        $device['ip_address'] = getInput("IP Address (optional): ", false);
        // Remove empty values
        $device = array_filter($device, function ($value) {
            return $value !== null && $value !== '';
        });
        if (!empty($device)) {
            $data['device'] = $device;
        }
    }
    try {
        $result = $api->checkoutUtilities()->getCardDetails($data);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Card details retrieved successfully!\n");
            if (isset($result['bin'])) {
                echo "  Card BIN: " . ($result['bin']['card_bin'] ?? 'N/A') . "\n";
                echo "  Scheme: " . ($result['bin']['scheme_name'] ?? 'N/A') . "\n";
                echo "  Issuer: " . ($result['bin']['issuer_name'] ?? 'N/A') . "\n";
                echo "  Card Type: " . ($result['bin']['payment_mode'] ?? 'N/A') . "\n";
            }
            if (isset($result['tokenization'])) {
                echo "  PAR: " . ($result['tokenization']['par'] ?? 'N/A') . "\n";
                echo "  Nimbbl Token ID: " . ($result['tokenization']['nimbbl_token_id'] ?? 'N/A') . "\n";
            }
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * Validate UPI VPA - Function to be called from cli.php or standalone
 */
function validateUpiVpaExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);


    $upiId = getInput("Enter UPI ID (e.g., user@paytm): ");
    if ($upiId === null) {
        printError("UPI ID is required.\n");
        return;
    }
    try {
        $result = $api->checkoutUtilities()->validateUpiVpa([JsonKeys::UPI_ID => $upiId]);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("UPI VPA validated!\n");
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * Get UPI App Details - Function to be called from cli.php or standalone
 */
function getUpiAppDetailsExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);



    $platform = getInput("Enter Platform (ios/android): ");
    if ($platform === null) {
        printError("Platform is required.\n");
        return;
    }
    // Validate platform value
    if (!in_array(strtolower($platform), ['ios', 'android'])) {
        printError("Platform must be 'ios' or 'android'.\n");
        return;
    }
    try {
        $result = $api->checkoutUtilities()->getUpiAppDetails(['platform' => strtolower($platform)]);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("UPI app details retrieved successfully!\n");
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

// Only run the full example if this file is executed directly (not included)
if (basename($_SERVER['PHP_SELF']) === 'checkout-utilities-examples.php') {
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

    echo Colors::CYAN . Colors::BOLD . "=== Checkout Utilities API Examples ===" . Colors::RESET . "\n\n";

    // Run all examples sequentially
    echo Colors::BLUE . Colors::BOLD . "Step 1: List Payment Modes" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    listPaymentModesExample();

    echo "\n";

    echo Colors::BLUE . Colors::BOLD . "Step 2: List Banks" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    listBanksExample();

    echo "\n";

    echo Colors::BLUE . Colors::BOLD . "Step 3: List Wallets" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    listWalletsExample();

    echo "\n";

    echo Colors::BLUE . Colors::BOLD . "Step 4: List EMIs" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    listEMIsExample();

    echo "\n";

    echo Colors::BLUE . Colors::BOLD . "Step 5: Get Offers" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    getOffersExample();

    echo "\n";

    echo Colors::BLUE . Colors::BOLD . "Step 6: Get Card BIN Data" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    getCardBinDataExample();

    echo "\n";

    echo Colors::BLUE . Colors::BOLD . "Step 7: Get Card Details" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    getCardDetailsExample();

    echo "\n";

    echo Colors::BLUE . Colors::BOLD . "Step 8: Validate UPI VPA" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    validateUpiVpaExample();

    echo "\n";

    echo Colors::BLUE . Colors::BOLD . "Step 9: Get UPI App Details" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    getUpiAppDetailsExample();

    echo "\n" . Colors::CYAN . Colors::BOLD . "=== Checkout Utilities API Examples Complete ===" . Colors::RESET . "\n";
    echo "\nFor more information, see: https://nimbbl.biz/docs/category/api-reference/checkout-utilities/\n";
}
