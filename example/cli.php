#!/usr/bin/env php
<?php
/**
 * Nimbbl PHP SDK - Interactive CLI Menu (Event-Based)
 * 
 * Event-based CLI that starts with token generation, then provides
 * a menu to run various examples and test SDK functionality.
 * 
 * Usage: php cli.php
 */

// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';

use Nimbbl\Api\Api;

// ANSI color codes for terminal output
class Colors {
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
}

function printHeader() {
    echo Colors::CYAN . Colors::BOLD;
    echo "=== Nimbbl PHP SDK - Event-Based CLI ===\n";
    echo Colors::RESET . "\n";
}

function printStep($stepNumber, $stepName) {
    echo "\n" . Colors::BLUE . Colors::BOLD;
    echo "Step {$stepNumber}: {$stepName}\n";
    echo str_repeat('-', 60) . Colors::RESET . "\n";
}

function printMenu() {
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
    echo "\n" . Colors::YELLOW . "=== Payment Links API ===\n" . Colors::RESET;
    echo "8.  Create Payment Link\n";
    echo "9.  Update Payment Link\n";
    echo "10. Payment Link Enquiry\n";
    echo "11. Payment Link Actions\n";
    echo "\n" . Colors::YELLOW . "=== Addresses API ===\n" . Colors::RESET;
    echo "12. List Addresses\n";
    echo "13. Create Address\n";
    echo "14. Update Address\n";
    echo "15. Delete Address\n";
    echo "16. Get Address by ID\n";
    echo "17. Import Addresses\n";
    echo "18. Check Address Eligibility\n";
    echo "19. Link Order to Address\n";
    echo "\n" . Colors::YELLOW . "=== Refunds API ===\n" . Colors::RESET;
    echo "20. Initiate Refund\n";
    echo "\n" . Colors::YELLOW . "=== Transactions API ===\n" . Colors::RESET;
    echo "21. Transaction Enquiry\n";
    echo "\n" . Colors::YELLOW . "=== Checkout Utilities API ===\n" . Colors::RESET;
    echo "22. List Payment Modes\n";
    echo "23. List Banks\n";
    echo "24. List Wallets\n";
    echo "25. List EMIs\n";
    echo "26. Get Offers\n";
    echo "27. Get Card BIN Data\n";
    echo "28. Validate UPI VPA\n";
    echo "29. Get UPI App Details\n";
    echo "\n" . Colors::YELLOW . "=== Webhooks ===\n" . Colors::RESET;
    echo "30. Webhook Handling\n";
    echo "\n" . Colors::YELLOW . "=== Examples ===\n" . Colors::RESET;
    echo "31. Encryption Examples\n";
    echo "32. Run All Examples\n";
    echo "\n0.  Exit\n\n";
    echo Colors::CYAN . "Enter your choice: " . Colors::RESET;
}

function printSuccess($message) {
    echo Colors::GREEN . "✓ " . $message . Colors::RESET . "\n";
}

function printError($message) {
    echo Colors::RED . "✗ " . $message . Colors::RESET . "\n";
}

function printInfo($message) {
    echo Colors::CYAN . "ℹ " . $message . Colors::RESET . "\n";
}

function printWarning($message) {
    echo Colors::YELLOW . "⚠ " . $message . Colors::RESET . "\n";
}

function printSeparator() {
    echo Colors::BLUE . str_repeat("=", 60) . Colors::RESET . "\n";
}

function printDocLink($url, $description = '') {
    echo "\n" . Colors::CYAN . "📚 Reference: " . Colors::RESET;
    echo Colors::BLUE . $url . Colors::RESET;
    if ($description) {
        echo " - " . $description;
    }
    echo "\n";
}

function getTokenInput() {
    echo "Enter Token: ";
    $token = trim(fgets(STDIN));
    if (empty($token)) {
        printError("Token is required.\n");
        return null;
    }
    return $token;
}

function getMerchantTokenInput() {
    // Priority: 1. User-provided token (highest), 2. Cached token (no fallback)
    $cachedToken = \Nimbbl\Api\Request::getCachedToken();
    
    if ($cachedToken !== null) {
        printInfo("Cached token available. You can provide a token or press Enter to use cached token.\n");
        echo "Enter Merchant Token (or press Enter to use cached token): ";
        $userToken = trim(fgets(STDIN));
        
        // If user provided a token, use it (highest priority)
        if (!empty($userToken)) {
            return $userToken;
        }
        
        // If user didn't provide token, return null to let SDK use cached token
        return null;
    } else {
        // No cached token - token is required
        printInfo("No cached token found. Token is required.\n");
        echo "Enter Merchant Token: ";
        $userToken = trim(fgets(STDIN));
        
        if (empty($userToken)) {
            printError("Merchant Token is required. Use option 1 to generate one.\n");
            return null;
        }
        
        return $userToken;
    }
}

function getOrderTokenInput() {
    // Priority: 1. User-provided token (highest), 2. Cached token (no fallback)
    $cachedToken = \Nimbbl\Api\Request::getCachedToken();
    
    if ($cachedToken !== null) {
        printInfo("Cached token available. You can provide a token or press Enter to use cached token.\n");
        echo "Enter Order Token (or press Enter to use cached token): ";
        $userToken = trim(fgets(STDIN));
        
        // If user provided a token, use it (highest priority)
        if (!empty($userToken)) {
            return $userToken;
        }
        
        // If user didn't provide token, return null to let SDK use cached token
        return null;
    } else {
        // No cached token - token is required
        printInfo("No cached token found. Token is required.\n");
        echo "Enter Order Token: ";
        $userToken = trim(fgets(STDIN));
        
        if (empty($userToken)) {
            printError("Order Token is required. Create an order first to get the token.\n");
            return null;
        }
        
        return $userToken;
    }
}

function getInput($prompt, $required = true) {
    echo $prompt;
    $input = trim(fgets(STDIN));
    if ($required && empty($input)) {
        return null;
    }
    return $input;
}

function printException($e) {
    // Check if it's a NimbblException with full error data
    if ($e instanceof \Nimbbl\Api\Exception\NimbblException) {
        printError("Exception: " . $e->getMessage() . "\n");
        
        $errorCode = $e->getErrorCode();
        $httpStatusCode = $e->getHttpStatusCode();
        $requestId = $e->getRequestId();
        $errorData = $e->getErrorData();
        
        if ($errorCode) {
            echo "  Error Code: {$errorCode}\n";
        }
        if ($httpStatusCode) {
            echo "  HTTP Status: {$httpStatusCode}\n";
        }
        if ($requestId) {
            echo "  Request ID: {$requestId}\n";
        }
        if ($errorData && !empty($errorData)) {
            echo "  Full Error Response:\n";
            // Format the error data with proper indentation
            $formatted = json_encode($errorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $lines = explode("\n", $formatted);
            foreach ($lines as $line) {
                echo "  " . $line . "\n";
            }
        }
    } else {
        // Regular exception - just show message
        printError("Exception: " . $e->getMessage() . "\n");
    }
}

// Load configuration
$config = loadConfig();

// Validate configuration
if (empty($config['access_key']) || $config['access_key'] === 'your_access_key_here') {
    printError("Please update example/config.php with your Nimbbl credentials.\n");
    printInfo("Copy config.php.example to config.php and update:\n");
    printInfo("  - access_key\n");
    printInfo("  - access_secret\n");
    printInfo("  - api_url (optional, defaults to UAT)\n");
    exit(1);
}

printHeader();

// Initialize Nimbbl API (with log file from config)
$api = initApi($config);

// Main menu loop
while (true) {
    printMenu();
    $choice = fgets(STDIN);
    echo "\n";
    
    // Remove newline for comparison (but keep original for switch)
    $choiceTrimmed = rtrim($choice, "\n\r");
    
    if ($choiceTrimmed === '0') {
        printInfo("Goodbye!\n");
        exit(0);
    }
    
    printSeparator();
    
    try {
        switch ($choiceTrimmed) {
            case '1':
                echo Colors::BOLD . "Generate Token\n" . Colors::RESET;
                try {
                    echo "Generating authentication token...\n";
                    echo str_repeat('-', 50) . "\n";
                    echo "Request:\n";
                    echo "  access_key: " . $config['access_key'] . "\n";
                    echo "  access_secret: " . str_repeat('*', strlen($config['access_secret'])) . "\n";
                    echo "\n";
                    
                    // Use Auth API client to generate token
                    $tokenResponse = $api->auth()->generateToken();
                    
                    if (isset($tokenResponse['error'])) {
                        printError("Token generation failed: " . print_r($tokenResponse['error'], true) . "\n");
                    } else {
                        printSuccess("Token generated successfully!\n");
                        $token = $tokenResponse['token'] ?? 'N/A';
                        $expiresAt = $tokenResponse['expires_at'] ?? 'N/A';
                        
                        echo "   Token: " . ($token !== 'N/A' ? $token : 'N/A') . "\n";
                        echo "   Expires At: {$expiresAt}\n";
                        
                        // Token is automatically cached by SDK in Request::cacheToken()
                        printInfo("Token has been cached by SDK and will be used automatically in subsequent API calls.\n");
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/generate-token-v-3/', 'Generate Token API');
                break;
                
            case '2':
                echo Colors::BOLD . "Create Order\n" . Colors::RESET;
                $_SERVER['SCRIPT_NAME'] = __FILE__;
                require __DIR__ . '/create-order.php';
                printDocLink('https://nimbbl.biz/docs/api-reference/create-an-order-v-3/', 'Create Order API');
                break;
                
            case '3':
                echo Colors::BOLD . "Get Order by ID\n" . Colors::RESET;
                $token = getOrderTokenInput(); // Returns null if SDK should use cached token
                $orderId = getInput("Enter Order ID: ");
                if ($orderId === null) {
                    printError("Order ID is required.\n");
                    break;
                }
                try {
                    $order = $api->orders()->getOrderById($orderId, $token);
                    if (isset($order['error'])) {
                        printError("Error: " . print_r($order['error'], true) . "\n");
                    } else {
                        printSuccess("Order retrieved successfully!\n");
                        echo "Order ID: " . ($order['order_id'] ?? $order['nimbbl_order_id'] ?? 'N/A') . "\n";
                        echo "Status: " . ($order['status'] ?? 'N/A') . "\n";
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/get-order-v-3/', 'Get Order API');
                break;
                
            case '4':
                echo Colors::BOLD . "Get Order by Invoice ID\n" . Colors::RESET;
                $token = getOrderTokenInput(); // Returns null if SDK should use cached token
                $invoiceId = getInput("Enter Invoice ID: ");
                if ($invoiceId === null) {
                    printError("Invoice ID is required.\n");
                    break;
                }
                try {
                    $order = $api->orders()->getOrderByInvoiceId($invoiceId, $token);
                    if (isset($order['error'])) {
                        printError("Error: " . print_r($order['error'], true) . "\n");
                    } else {
                        printSuccess("Order retrieved successfully!\n");
                        $oid = $order['order_id'] ?? $order['nimbbl_order_id'] ?? 'N/A';
                        $iid = $order['invoice_id'] ?? 'N/A';
                        $stat = $order['status'] ?? 'N/A';
                        $amt = $order['total_amount'] ?? 0;
                        $curr = $order['currency'] ?? 'INR';
                        echo "   Order ID: {$oid}\n";
                        echo "   Invoice ID: {$iid}\n";
                        echo "   Status: {$stat}\n";
                        echo "   Amount: " . ($amt) . " " . ($curr) . "\n";
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/get-order-v-3/', 'Get Order API');
                break;
                
            // Payments API
            case '5':
                echo Colors::BOLD . "Initiate Payment\n" . Colors::RESET;
                $token = getOrderTokenInput(); // null means use SDK's cached token
                $orderId = getInput("Enter Order ID: ");
                if ($orderId === null) {
                    printError("Order ID is required.\n");
                    break;
                }
                $paymentMode = getInput("Enter Payment Mode Code (net_banking/credit_card/etc): ", false) ?: 'net_banking';
                
                // Bank code is mandatory for net_banking
                if (strtolower($paymentMode) === 'net_banking') {
                    echo "   ℹ Bank Code is required for net_banking payment mode.\n";
                    echo "   Common bank codes: HDFC, ICICI, SBI, AXIS, KOTAK, etc.\n";
                    $bankCode = getInput("Enter Bank Code (default: HDFC): ", false) ?: 'HDFC';
                    $data = ['order_id' => $orderId, 'payment_mode_code' => $paymentMode, 'callback_url' => $callbackUrl, 'bank_code' => $bankCode];
                } else {
                    $bankCode = getInput("Enter Bank Code (optional, for net_banking only): ", false);
                    $data = ['order_id' => $orderId, 'payment_mode_code' => $paymentMode, 'callback_url' => $callbackUrl];
                    if ($bankCode) $data['bank_code'] = $bankCode;
                }
                try {
                    $result = $api->payments()->initiatePayment($data, $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Payment initiated successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/initiate-a-payment-v-3/', 'Initiate Payment API');
                break;
                
            case '6':
                echo Colors::BOLD . "Complete Payment\n" . Colors::RESET;
                $token = getOrderTokenInput(); // null means use SDK's cached token
                $transactionId = getInput("Enter Transaction ID: ");
                if ($transactionId === null) {
                    printError("Transaction ID is required.\n");
                    break;
                }
                $paymentFlow = getInput("Enter Payment Flow (auto_debit/otp): ", false) ?: 'auto_debit';
                $data = ['transaction_id' => $transactionId, 'payment_flow' => $paymentFlow];
                if (strtolower($paymentFlow) === 'otp') {
                    $otp = getInput("Enter OTP: ", false);
                    if ($otp) $data['otp'] = $otp;
                }
                try {
                    $result = $api->payments()->completePayment($data, $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Payment completed successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/complete-payment-v-3/', 'Complete Payment API');
                break;
                
            case '7':
                echo Colors::BOLD . "Resend OTP\n" . Colors::RESET;
                $token = getOrderTokenInput(); // null means use SDK's cached token
                $transactionId = getInput("Enter Transaction ID: ");
                if ($transactionId === null) {
                    printError("Transaction ID is required.\n");
                    break;
                }
                try {
                    $result = $api->payments()->resendPaymentOtp(['transaction_id' => $transactionId], $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("OTP resent successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/resend-otp-v-3/', 'Resend OTP API');
                break;
                
            // Payment Links API
            case '8':
                echo Colors::BOLD . "Create Payment Link\n" . Colors::RESET;
                $token = getMerchantTokenInput(); // null means use SDK's cached token
                
                printInfo("Enter payment link details:\n");
                $invoiceId = getInput("Enter Invoice ID: ");
                if ($invoiceId === null) {
                    printError("Invoice ID is required.\n");
                    break;
                }
                $totalAmount = getInput("Enter Total Amount: ");
                if ($totalAmount === null) {
                    printError("Total Amount is required.\n");
                    break;
                }
                $currency = getInput("Enter Currency (INR/USD/etc): ", false) ?: 'INR';
                
                // User details
                printInfo("\nUser Details:\n");
                $email = getInput("Enter User Email: ");
                $firstName = getInput("Enter User First Name: ");
                $lastName = getInput("Enter User Last Name: ", false);
                $countryCode = getInput("Enter Country Code (e.g., +91): ", false) ?: '+91';
                $mobileNumber = getInput("Enter Mobile Number: ");
                
                // Order line items
                printInfo("\nOrder Line Items (at least one required):\n");
                $orderLineItems = [];
                $addMoreItems = true;
                $itemNum = 1;
                while ($addMoreItems) {
                    echo "Item {$itemNum}:\n";
                    $skuId = getInput("  SKU ID: ");
                    $title = getInput("  Title: ");
                    $description = getInput("  Description: ", false);
                    $rate = getInput("  Rate: ");
                    $quantity = getInput("  Quantity: ");
                    $amountBeforeTax = getInput("  Amount Before Tax: ");
                    $tax = getInput("  Tax: ", false) ?: '0';
                    $itemTotalAmount = getInput("  Total Amount: ");
                    $imageUrl = getInput("  Image URL (optional): ", false);
                    
                    $item = [
                        'sku_id' => $skuId,
                        'title' => $title,
                        'rate' => floatval($rate), // number
                        'quantity' => intval($quantity), // int
                        'amount_before_tax' => floatval($amountBeforeTax), // number (matching working curl)
                        'tax' => floatval($tax), // number (matching working curl)
                        'total_amount' => floatval($itemTotalAmount) // number (matching working curl)
                    ];
                    if ($description) $item['description'] = $description;
                    if ($imageUrl) $item['image_url'] = $imageUrl;
                    
                    $addSerialNumbers = getInput("  Add Serial Numbers? (y/N): ", false);
                    if (strtolower($addSerialNumbers) === 'y') {
                        $serialNumbers = [];
                        $addMoreSerials = true;
                        while ($addMoreSerials) {
                            $serial = getInput("    Serial Number (or press Enter to finish): ", false);
                            if ($serial) {
                                $serialNumbers[] = $serial;
                            } else {
                                $addMoreSerials = false;
                            }
                        }
                        if (!empty($serialNumbers)) {
                            $item['serial_numbers'] = $serialNumbers;
                        }
                    }
                    
                    $orderLineItems[] = $item;
                    $itemNum++;
                    
                    $addMore = getInput("Add another item? (y/N): ", false);
                    $addMoreItems = (strtolower($addMore) === 'y');
                }
                
                // Required field: expires_at
                printInfo("\nRequired Field:\n");
                $expiresAt = getInput("Expires At (YYYY-MM-DD HH:MM:SS, required, UTC): ");
                if ($expiresAt === null) {
                    printError("Expires At is required.\n");
                    break;
                }
                
                // Ask if user wants to use defaults for optional fields
                // If yes, skip all optional field prompts
                $useDefaults = getInput("\nUse default values for optional fields? (y/N): ", false);
                $useDefaultsFlag = (strtolower($useDefaults) === 'y');
                
                if ($useDefaultsFlag) {
                    // Use defaults from working curl example
                    $description = "xxx";
                    $termsAndConditions = "xxx";
                    $callbackUrl = "https://www.google.com";
                    $sendSms = 'y'; // true
                    $sendEmail = 'y'; // true
                    $bankAccount = [
                        'account_number' => '037801513988',
                        'name' => 'Vasudha Maini',
                        'ifsc' => 'ICIC0000378'
                    ];
                    $customAttributes = [
                        'Name' => 'Vasudha',
                        'Place' => 'Delhi',
                        'Animal' => 'Tiger',
                        'Thing' => 'Pen'
                    ];
                    $topLevelSerialNumbers = [
                        '359043372654548',
                        '359043371395481'
                    ];
                    $notificationScheduledAt = null; // Not in curl example
                    $emailTemplateId = null; // Not in curl example
                    $smsTemplateId = null; // Not in curl example
                    printInfo("Using defaults from curl example:\n");
                    printInfo("  description='xxx', terms_and_conditions='xxx', callback_url='https://www.google.com'\n");
                    printInfo("  send_sms=true, send_email=true\n");
                    printInfo("  bank_account: account_number='037801513988', name='Vasudha Maini', ifsc='ICIC0000378'\n");
                    printInfo("  custom_attributes: Name='Vasudha', Place='Delhi', Animal='Tiger', Thing='Pen'\n");
                    printInfo("  top-level serial_numbers: ['359043372654548', '359043371395481']\n");
                } else {
                    // Only ask optional fields if NOT using defaults
                    printInfo("\nOptional Fields (press Enter to skip any):\n");
                    $description = getInput("Description (optional): ", false);
                    $termsAndConditions = getInput("Terms and Conditions (optional): ", false);
                    $callbackUrl = getInput("Callback URL (optional): ", false);
                    
                    // Defaults: send_sms = true, send_email = false (as per API docs)
                    $sendSms = getInput("Send SMS? (Y/n, default: Y): ", false);
                    $sendEmail = getInput("Send Email? (y/N, default: N): ", false);
                    
                    // Notification scheduling (optional)
                    $notificationScheduledAt = getInput("Notification Scheduled At (YYYY-MM-DD HH:MM:SS, optional): ", false);
                    $emailTemplateId = getInput("Email Template ID (optional): ", false);
                    $smsTemplateId = getInput("SMS Template ID (optional): ", false);
                    
                    // Bank account (optional)
                    $addBankAccount = getInput("Add Bank Account? (y/N): ", false);
                    $bankAccount = null;
                    if (strtolower($addBankAccount) === 'y') {
                        $accountNumber = getInput("  Account Number: ");
                        $accountName = getInput("  Account Name: ");
                        $ifsc = getInput("  IFSC: ");
                        $bankAccount = [
                            'account_number' => $accountNumber,
                            'name' => $accountName,
                            'ifsc' => $ifsc
                        ];
                    }
                    
                    // Custom attributes (optional)
                    $addCustomAttributes = getInput("Add Custom Attributes? (y/N): ", false);
                    $customAttributes = null;
                    if (strtolower($addCustomAttributes) === 'y') {
                        $customAttributes = [];
                        $addMoreAttrs = true;
                        while ($addMoreAttrs) {
                            $key = getInput("  Attribute Key (or press Enter to finish): ", false);
                            if ($key) {
                                $value = getInput("  Attribute Value: ");
                                $customAttributes[$key] = $value;
                            } else {
                                $addMoreAttrs = false;
                            }
                        }
                    }
                    
                    // Serial numbers at top level (optional) - not in API docs but supported by API
                    $addTopLevelSerials = getInput("Add Top-Level Serial Numbers? (y/N): ", false);
                    $topLevelSerialNumbers = null;
                    if (strtolower($addTopLevelSerials) === 'y') {
                        $topLevelSerialNumbers = [];
                        $addMoreSerials = true;
                        while ($addMoreSerials) {
                            $serial = getInput("  Serial Number (or press Enter to finish): ", false);
                            if ($serial) {
                                $topLevelSerialNumbers[] = $serial;
                            } else {
                                $addMoreSerials = false;
                            }
                        }
                    }
                }
                
                // Build request data
                // Set defaults: send_sms = true, send_email = false (as per API docs)
                // But if using defaults flag, both are true
                if ($useDefaultsFlag) {
                    $sendSmsValue = true;
                    $sendEmailValue = true;
                } else {
                    $sendSmsValue = ($sendSms === '' || strtolower($sendSms) === 'y' || strtolower($sendSms) === 'yes') ? true : false;
                    $sendEmailValue = (strtolower($sendEmail) === 'y' || strtolower($sendEmail) === 'yes') ? true : false;
                }
                
                $data = [
                    'invoice_id' => $invoiceId,
                    'total_amount' => floatval($totalAmount), // double as per API docs
                    'currency' => $currency,
                    'expires_at' => $expiresAt, // required, YYYY-MM-DD HH:MM:ss format, UTC
                    'order_line_items' => $orderLineItems,
                    'user' => [
                        'email' => $email,
                        'first_name' => $firstName,
                        'country_code' => $countryCode, // default: +91
                        'mobile_number' => $mobileNumber
                    ],
                    'send_sms' => $sendSmsValue, // boolean, default: true
                    'send_email' => $sendEmailValue // boolean, default: false
                ];
                
                if ($lastName) $data['user']['last_name'] = $lastName;
                if ($description) $data['description'] = $description;
                if ($termsAndConditions) $data['terms_and_conditions'] = $termsAndConditions;
                if ($callbackUrl) $data['callback_url'] = $callbackUrl;
                if ($notificationScheduledAt) $data['notification_scheduled_at'] = $notificationScheduledAt;
                if ($emailTemplateId) $data['email_template_id'] = $emailTemplateId;
                if ($smsTemplateId) $data['sms_template_id'] = $smsTemplateId;
                if ($bankAccount) $data['bank_account'] = $bankAccount;
                if ($customAttributes) $data['custom_attributes'] = $customAttributes;
                if ($topLevelSerialNumbers) $data['serial_numbers'] = $topLevelSerialNumbers;
                
                try {
                    $result = $api->paymentLinks()->createPaymentLink($data, $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Payment link created successfully!\n");
                        if (isset($result['payment_link_id'])) {
                            echo "  Payment Link ID: " . $result['payment_link_id'] . "\n";
                        }
                        if (isset($result['short_url'])) {
                            echo "  Short URL: " . $result['short_url'] . "\n";
                        }
                        if (!isset($result['success']) || !$result['success']) {
                            print_r($result);
                        }
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/create-a-payment-link-v-3/', 'Create Payment Link API');
                break;
                
            case '9':
                echo Colors::BOLD . "Update Payment Link\n" . Colors::RESET;
                $token = getMerchantTokenInput(); // null means use SDK's cached token
                
                printInfo("Identify payment link using:\n");
                echo "1. Invoice ID\n";
                echo "2. Payment Link ID\n";
                $identifierType = getInput("Choose (1 or 2): ");
                
                $data = [];
                if ($identifierType === '1') {
                    $invoiceId = getInput("Enter Invoice ID: ");
                    if ($invoiceId === null) {
                        printError("Invoice ID is required.\n");
                        break;
                    }
                    $data['invoice_id'] = $invoiceId;
                } elseif ($identifierType === '2') {
                    $paymentLinkId = getInput("Enter Payment Link ID: ");
                    if ($paymentLinkId === null) {
                        printError("Payment Link ID is required.\n");
                        break;
                    }
                    $data['payment_link_id'] = $paymentLinkId;
                } else {
                    printError("Invalid choice. Please choose 1 or 2.\n");
                    break;
                }
                
                printInfo("\nNote: Payment links can only be updated when status is 'created'.\n");
                printInfo("Enter fields to update (at least one field is required):\n");
                
                // User information
                $firstName = getInput("User First Name (optional): ", false);
                $lastName = getInput("User Last Name (optional): ", false);
                if ($firstName || $lastName) {
                    $data['user'] = [];
                    if ($firstName) $data['user']['first_name'] = $firstName;
                    if ($lastName) $data['user']['last_name'] = $lastName;
                }
                
                // Total amount
                $totalAmount = getInput("Total Amount (optional): ", false);
                if ($totalAmount) {
                    $data['total_amount'] = floatval($totalAmount);
                }
                
                // Expires at
                $expiresAt = getInput("Expires At (YYYY-MM-DD HH:MM:SS, UTC, optional): ", false);
                if ($expiresAt) {
                    $data['expires_at'] = $expiresAt;
                }
                
                // Currency
                $currency = getInput("Currency (ISO-4217, optional): ", false);
                if ($currency) {
                    $data['currency'] = $currency;
                }
                
                // Order line items
                $updateOrderLineItems = getInput("Update Order Line Items? (y/N): ", false);
                if (strtolower($updateOrderLineItems) === 'y') {
                    $orderLineItems = [];
                    $addMoreItems = true;
                    $itemNum = 1;
                    while ($addMoreItems) {
                        echo "Item {$itemNum}:\n";
                        $skuId = getInput("  SKU ID: ");
                        $title = getInput("  Title: ");
                        $description = getInput("  Description (optional): ", false);
                        $rate = getInput("  Rate: ");
                        $quantity = getInput("  Quantity: ");
                        $totalAmountItem = getInput("  Total Amount: ");
                        $tax = getInput("  Tax (optional): ", false) ?: '0';
                        $amountBeforeTax = getInput("  Amount Before Tax (optional): ", false);
                        $imageUrl = getInput("  Image URL (optional): ", false);
                        
                        $item = [
                            'sku_id' => $skuId,
                            'title' => $title,
                            'rate' => floatval($rate),
                            'quantity' => intval($quantity),
                            'total_amount' => $totalAmountItem, // string as per API
                            'tax' => $tax ?: '0', // string as per API
                        ];
                        if ($description) $item['description'] = $description;
                        if ($imageUrl) $item['image_url'] = $imageUrl;
                        if ($amountBeforeTax) {
                            $item['amount_before_tax'] = $amountBeforeTax; // string as per API
                        }
                        
                        $addSerialNumbers = getInput("  Add Serial Numbers? (y/N): ", false);
                        if (strtolower($addSerialNumbers) === 'y') {
                            $serialNumbers = [];
                            $addMoreSerials = true;
                            while ($addMoreSerials) {
                                $serial = getInput("    Serial Number (or press Enter to finish): ", false);
                                if ($serial) {
                                    $serialNumbers[] = $serial;
                                } else {
                                    $addMoreSerials = false;
                                }
                            }
                            if (!empty($serialNumbers)) {
                                $item['serial_numbers'] = $serialNumbers;
                            }
                        }
                        
                        $orderLineItems[] = $item;
                        $itemNum++;
                        $addMore = getInput("Add another item? (y/N): ", false);
                        $addMoreItems = (strtolower($addMore) === 'y');
                    }
                    if (!empty($orderLineItems)) {
                        $data['order_line_items'] = $orderLineItems;
                    }
                }
                
                // Bank account
                $updateBankAccount = getInput("Update Bank Account? (y/N): ", false);
                if (strtolower($updateBankAccount) === 'y') {
                    $accountNumber = getInput("  Account Number: ");
                    $accountName = getInput("  Account Name: ");
                    $ifsc = getInput("  IFSC: ");
                    if ($accountNumber && $accountName && $ifsc) {
                        $data['bank_account'] = [
                            'account_number' => $accountNumber,
                            'name' => $accountName,
                            'ifsc' => $ifsc
                        ];
                    }
                }
                
                // Custom attributes
                $updateCustomAttributes = getInput("Update Custom Attributes? (y/N): ", false);
                if (strtolower($updateCustomAttributes) === 'y') {
                    $customAttributes = [];
                    $addMoreAttrs = true;
                    while ($addMoreAttrs) {
                        $key = getInput("  Attribute Key (or press Enter to finish): ", false);
                        if ($key) {
                            $value = getInput("  Attribute Value: ");
                            if ($value !== null) {
                                $customAttributes[$key] = $value;
                            }
                        } else {
                            $addMoreAttrs = false;
                        }
                    }
                    if (!empty($customAttributes)) {
                        $data['custom_attributes'] = $customAttributes;
                    }
                }
                
                // Validate that at least one field (besides identifier) is provided
                $updateFields = $data;
                unset($updateFields['invoice_id']);
                unset($updateFields['payment_link_id']);
                
                if (empty($updateFields)) {
                    printError("Error: At least one field must be provided for update.\n");
                    printInfo("Allowed fields: user.first_name, user.last_name, total_amount, expires_at, currency, order_line_items, bank_account, custom_attributes\n");
                    break;
                }
                
                try {
                    $result = $api->paymentLinks()->updatePaymentLink($data, $token);
                    if (isset($result['error'])) {
                        $error = $result['error'];
                        $errorCode = isset($error['nimbbl_error_code']) ? $error['nimbbl_error_code'] : 'UNKNOWN_ERROR';
                        $merchantMsg = isset($error['nimbbl_merchant_message']) ? $error['nimbbl_merchant_message'] : (isset($error['nimbbl_consumer_message']) ? $error['nimbbl_consumer_message'] : 'Unknown error');
                        
                        printError("Error ({$errorCode}): {$merchantMsg}\n");
                        
                        if ($errorCode === 'LINK_CANNOT_BE_UPDATED') {
                            printInfo("Note: Payment links can only be updated when status is 'created'.\n");
                            printInfo("Use Payment Link Enquiry to check the current status of your payment link.\n");
                        }
                    } else {
                        printSuccess("Payment link updated successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/update-a-payment-link-v-3/', 'Update Payment Link API');
                break;
                
            case '10':
                echo Colors::BOLD . "Payment Link Enquiry\n" . Colors::RESET;
                $token = getMerchantTokenInput(); // null means use SDK's cached token
                
                printInfo("Identify payment link using:\n");
                echo "1. Invoice ID\n";
                echo "2. Payment Link ID\n";
                $identifierType = getInput("Choose (1 or 2): ");
                
                $data = [];
                if ($identifierType === '1') {
                    $invoiceId = getInput("Enter Invoice ID: ");
                    if ($invoiceId === null) {
                        printError("Invoice ID is required.\n");
                        break;
                    }
                    $data['invoice_id'] = $invoiceId;
                } elseif ($identifierType === '2') {
                    $paymentLinkId = getInput("Enter Payment Link ID: ");
                    if ($paymentLinkId === null) {
                        printError("Payment Link ID is required.\n");
                        break;
                    }
                    $data['payment_link_id'] = $paymentLinkId;
                } else {
                    printError("Invalid choice. Please choose 1 or 2.\n");
                    break;
                }
                
                try {
                    $result = $api->paymentLinks()->enquiryPaymentLink($data, $token);
                    if (isset($result['error'])) {
                        $error = $result['error'];
                        $errorCode = isset($error['nimbbl_error_code']) ? $error['nimbbl_error_code'] : 'UNKNOWN_ERROR';
                        $merchantMsg = isset($error['nimbbl_merchant_message']) ? $error['nimbbl_merchant_message'] : (isset($error['nimbbl_consumer_message']) ? $error['nimbbl_consumer_message'] : 'Unknown error');
                        
                        printError("Error ({$errorCode}): {$merchantMsg}\n");
                    } else {
                        printSuccess("Payment link enquiry successful!\n");
                        if (isset($result['payment_link_id'])) {
                            echo "  Payment Link ID: " . $result['payment_link_id'] . "\n";
                        }
                        if (isset($result['status'])) {
                            echo "  Status: " . $result['status'] . "\n";
                        }
                        if (isset($result['total_amount'])) {
                            echo "  Total Amount: " . $result['total_amount'] . " " . ($result['currency'] ?? '') . "\n";
                        }
                        if (isset($result['payment_link_amount_paid'])) {
                            echo "  Amount Paid: " . $result['payment_link_amount_paid'] . "\n";
                        }
                        if (isset($result['orders_with_transactions']) && is_array($result['orders_with_transactions'])) {
                            echo "  Orders with Transactions: " . count($result['orders_with_transactions']) . "\n";
                        }
                        echo "\nFull Response:\n";
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/payment-link-enquiry-v-3/', 'Payment Link Enquiry API');
                break;
                
            case '11':
                echo Colors::BOLD . "Payment Link Actions\n" . Colors::RESET;
                $token = getMerchantTokenInput(); // null means use SDK's cached token
                
                printInfo("Identify payment link using:\n");
                echo "1. Invoice ID\n";
                echo "2. Payment Link ID\n";
                $identifierType = getInput("Choose (1 or 2): ");
                
                $data = [];
                if ($identifierType === '1') {
                    $invoiceId = getInput("Enter Invoice ID: ");
                    if ($invoiceId === null) {
                        printError("Invoice ID is required.\n");
                        break;
                    }
                    $data['invoice_id'] = $invoiceId;
                } elseif ($identifierType === '2') {
                    $paymentLinkId = getInput("Enter Payment Link ID: ");
                    if ($paymentLinkId === null) {
                        printError("Payment Link ID is required.\n");
                        break;
                    }
                    $data['payment_link_id'] = $paymentLinkId;
                } else {
                    printError("Invalid choice. Please choose 1 or 2.\n");
                    break;
                }
                
                printInfo("\nAvailable actions:\n");
                echo "1. send - Send the payment link\n";
                echo "2. cancel - Cancel the payment link\n";
                $actionChoice = getInput("Choose action (1 or 2): ");
                
                if ($actionChoice === '1') {
                    $data['action'] = 'send';
                } elseif ($actionChoice === '2') {
                    $data['action'] = 'cancel';
                } else {
                    printError("Invalid choice. Please choose 1 or 2.\n");
                    break;
                }
                
                try {
                    $result = $api->paymentLinks()->performPaymentLinkActions($data, $token);
                    if (isset($result['error'])) {
                        $error = $result['error'];
                        $errorCode = isset($error['nimbbl_error_code']) ? $error['nimbbl_error_code'] : 'UNKNOWN_ERROR';
                        $merchantMsg = isset($error['nimbbl_merchant_message']) ? $error['nimbbl_merchant_message'] : (isset($error['nimbbl_consumer_message']) ? $error['nimbbl_consumer_message'] : 'Unknown error');
                        
                        printError("Error ({$errorCode}): {$merchantMsg}\n");
                    } else {
                        printSuccess("Payment link action executed successfully!\n");
                        if (isset($result['status'])) {
                            echo "  Status: " . $result['status'] . "\n";
                        }
                        if (isset($result['payment_link_url'])) {
                            echo "  Payment Link URL: " . $result['payment_link_url'] . "\n";
                        }
                        if (isset($result['payment_link_id'])) {
                            echo "  Payment Link ID: " . $result['payment_link_id'] . "\n";
                        }
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/payment-link-actions-v-3/', 'Payment Link Actions API');
                break;
                
            // Addresses API - cases 12-19
            case '12':
                echo Colors::BOLD . "List Addresses\n" . Colors::RESET;
                echo Colors::CYAN . "Query Parameters: user_id, amount, currency\n" . Colors::RESET;
                $token = getOrderTokenInput();
                
                $data = [];
                
                // user_id - required for listing addresses
                $userId = getInput("Enter User ID: ", false);
                if ($userId) {
                    $data['user_id'] = $userId;
                }
                
                // amount - order amount to calculate shipping charges
                $amount = getInput("Enter Order Amount (for shipping calculation, optional): ", false);
                if ($amount) {
                    $data['amount'] = floatval($amount);
                }
                
                // currency - currency code in ISO-4217 format
                $currency = getInput("Enter Currency (ISO-4217 format, e.g., INR, optional): ", false);
                if ($currency) {
                    $data['currency'] = strtoupper($currency);
                }
                
                if (empty($data)) {
                    printWarning("No query parameters provided. At least one parameter (user_id, amount, currency) is recommended.\n");
                    $continue = getInput("Continue anyway? (y/n): ", false);
                    if (strtolower($continue) !== 'y') {
                        break;
                    }
                }
                
                try {
                    $result = $api->addresses()->listAddresses($data, $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Addresses retrieved successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/list-addresses-v-3/', 'List Addresses API');
                break;
                
            case '13':
                echo Colors::BOLD . "Create Address\n" . Colors::RESET;
                $token = getOrderTokenInput();
                $userId = getInput("Enter User ID (optional): ", false);
                printInfo("Enter address details:\n");
                
                // Required fields
                $firstName = getInput("First Name: ");
                $lastName = getInput("Last Name: ");
                $address1 = getInput("Address Line 1: ");
                $area = getInput("Area/Locality: ");
                $city = getInput("City: ");
                $state = getInput("State: ");
                $pincode = getInput("Pincode: ");
                $addressType = getInput("Address Type (home/office/etc): ");
                
                if ($firstName === null || $lastName === null || $address1 === null || $area === null || 
                    $city === null || $state === null || $pincode === null || $addressType === null) {
                    printError("First Name, Last Name, Address Line 1, Area, City, State, Pincode, and Address Type are required.\n");
                    break;
                }
                
                // Build address object
                $addressData = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'address_1' => $address1,
                    'area' => $area,
                    'city' => $city,
                    'state' => $state,
                    'pincode' => $pincode,
                    'address_type' => $addressType
                ];
                
                // Optional fields
                $street = getInput("Street (optional): ", false);
                if ($street) $addressData['street'] = $street;
                
                $landmark = getInput("Landmark (optional): ", false);
                if ($landmark) $addressData['landmark'] = $landmark;
                
                $label = getInput("Label (optional): ", false);
                if ($label) $addressData['label'] = $label;
                
                $country = getInput("Country (optional, default: India): ", false);
                // Default to India if not provided (common for Indian addresses)
                $addressData['country'] = $country ?: 'India';
                
                $linkAs = getInput("Link As (shipping/billing, optional): ", false);
                if ($linkAs && in_array(strtolower($linkAs), ['shipping', 'billing'])) {
                    $addressData['link_as'] = strtolower($linkAs);
                }
                
                // Build request body with addresses array
                $data = [
                    'addresses' => [$addressData]
                ];
                
                if ($userId) {
                    $data['user_id'] = $userId;
                }
                
                // Amount and currency for shipping calculation
                // Note: These may be required when using order token for address creation
                $amount = getInput("Order Amount (for shipping calculation, default: 5000): ", false);
                $currency = getInput("Currency (default: INR): ", false);
                
                // Include both - use defaults if not provided (matching working curl example)
                $data['amount'] = $amount ? floatval($amount) : 5000;
                $data['currency'] = $currency ?: 'INR';
                
                try {
                    $result = $api->addresses()->createAddress($data, $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Address created successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/create-an-address-v-3/', 'Create Address API');
                break;
                
            case '14':
                echo Colors::BOLD . "Update Address\n" . Colors::RESET;
                $token = getOrderTokenInput();
                $addressId = getInput("Enter Address ID: ");
                if ($addressId === null) {
                    printError("Address ID is required.\n");
                    break;
                }
                printInfo("Enter address fields to update (press Enter to skip)\n");
                $data = [];
                $line1 = getInput("Address Line 1: ", false);
                if ($line1) $data['address_1'] = $line1;
                $city = getInput("City: ", false);
                if ($city) $data['city'] = $city;
                try {
                    $result = $api->addresses()->updateAddress($addressId, $data, $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Address updated successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/update-an-address-v-3/', 'Update Address API');
                break;
                
            case '15':
                echo Colors::BOLD . "Delete Address\n" . Colors::RESET;
                $token = getOrderTokenInput();
                $addressId = getInput("Enter Address ID: ");
                if ($addressId === null) {
                    printError("Address ID is required.\n");
                    break;
                }
                try {
                    $result = $api->addresses()->deleteAddress($addressId, $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Address deleted successfully!\n");
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/delete-an-address-v-3/', 'Delete Address API');
                break;
                
            case '16':
                echo Colors::BOLD . "Get Address by ID\n" . Colors::RESET;
                $token = getOrderTokenInput();
                $addressId = getInput("Enter Address ID: ");
                if ($addressId === null) {
                    printError("Address ID is required.\n");
                    break;
                }
                try {
                    $result = $api->addresses()->getAddressById($addressId, $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Address retrieved successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/get-an-address-v-3/', 'Get Address API');
                break;
                
            case '17':
                echo Colors::BOLD . "Import Addresses\n" . Colors::RESET;
                $token = getOrderTokenInput();
                
                printInfo("Import addresses from a provider (e.g., shiprocket)\n");
                printInfo("This is a two-step process:\n");
                printInfo("1. First call 'auth' command to initiate import\n");
                printInfo("2. Then call 'verify' command with OTP to complete import\n\n");
                
                echo "Select command:\n";
                echo "1. auth (Initiate address import)\n";
                echo "2. verify (Verify OTP and import addresses)\n";
                $commandChoice = getInput("Enter choice (1 or 2): ");
                
                if ($commandChoice === '1') {
                    $command = 'auth';
                    $provider = getInput("Enter Provider Code (e.g., shiprocket): ");
                    if ($provider === null) {
                        printError("Provider code is required.\n");
                        break;
                    }
                    $importData = [
                        'command' => $command,
                        'provider' => $provider
                    ];
                } elseif ($commandChoice === '2') {
                    $command = 'verify';
                    $provider = getInput("Enter Provider Code (e.g., shiprocket): ");
                    $otp = getInput("Enter OTP (required): ");
                    if ($provider === null || $otp === null) {
                        printError("Provider code and OTP are required.\n");
                        break;
                    }
                    $importData = [
                        'command' => $command,
                        'provider' => $provider,
                        'otp' => $otp
                    ];
                } else {
                    printError("Invalid choice. Must be 1 or 2.\n");
                    break;
                }
                
                try {
                    $result = $api->addresses()->importAddresses($importData, $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Import request processed successfully!\n");
                        if (isset($result['next']) && is_array($result['next'])) {
                            echo "\nNext steps:\n";
                            foreach ($result['next'] as $nextAction) {
                                if (isset($nextAction['action'])) {
                                    echo "  - Action: " . $nextAction['action'] . "\n";
                                    if (isset($nextAction['url'])) {
                                        echo "    URL: " . $nextAction['url'] . "\n";
                                    }
                                    if (isset($nextAction['required_parameters'])) {
                                        echo "    Required Parameters: " . implode(', ', $nextAction['required_parameters']) . "\n";
                                    }
                                }
                            }
                        }
                        if (!isset($result['success']) || !$result['success']) {
                            print_r($result);
                        }
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/import-addresses-v-3/', 'Import Addresses API');
                break;
                
            case '18':
                echo Colors::BOLD . "Check Address Eligibility\n" . Colors::RESET;
                $token = getOrderTokenInput();
                
                printInfo("Enter eligibility check details:\n");
                $pincode = getInput("Pincode (required): ");
                if ($pincode === null) {
                    printError("Pincode is required.\n");
                    break;
                }
                
                $data = ['pincode' => $pincode];
                
                $countryCode = getInput("Country Code (optional, default: IND): ", false);
                if ($countryCode) {
                    $data['country_code'] = $countryCode;
                }
                
                $amount = getInput("Order Amount (optional, for shipping calculation): ", false);
                if ($amount) {
                    $data['amount'] = floatval($amount);
                }
                
                $currency = getInput("Currency (optional, e.g., INR): ", false);
                if ($currency) {
                    $data['currency'] = $currency;
                }
                
                try {
                    $result = $api->addresses()->checkAddressEligibility($data, $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Address eligibility checked!\n");
                        echo "  Eligible for Shipping: " . (isset($result['is_eligible_for_shipping']) ? ($result['is_eligible_for_shipping'] ? 'Yes' : 'No') : 'N/A') . "\n";
                        if (isset($result['max_shipping_charges'])) {
                            echo "  Max Shipping Charges: " . $result['max_shipping_charges'] . "\n";
                        }
                        if (isset($result['pincode_details'])) {
                            $details = $result['pincode_details'];
                            echo "  City: " . ($details['city'] ?? 'N/A') . "\n";
                            echo "  State: " . ($details['state'] ?? 'N/A') . "\n";
                            echo "  Country Code: " . ($details['country_code'] ?? 'N/A') . "\n";
                        }
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/check-address-eligibility-v-3/', 'Check Address Eligibility API');
                break;
                
            case '19':
                echo Colors::BOLD . "Link Order to Address\n" . Colors::RESET;
                $token = getOrderTokenInput();
                $addressId = getInput("Enter Address ID (required): ");
                if ($addressId === null) {
                    printError("Address ID is required.\n");
                    break;
                }
                $orderId = getInput("Enter Order ID (optional, not required if using order token): ", false);
                echo "Link as:\n";
                echo "1. shipping\n";
                echo "2. billing\n";
                $linkAsChoice = getInput("Enter choice (1 or 2): ");
                $linkAs = ($linkAsChoice === '1') ? 'shipping' : (($linkAsChoice === '2') ? 'billing' : null);
                if ($linkAs === null) {
                    printError("Invalid choice. Must be 'shipping' or 'billing'.\n");
                    break;
                }
                $linkData = [
                    'address' => [
                        'address_id' => $addressId
                    ],
                    'link_as' => $linkAs
                ];
                if ($orderId) {
                    $linkData['order_id'] = $orderId;
                }
                try {
                    $result = $api->addresses()->linkAddressWithOrder($linkData, $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Order linked to address successfully!\n");
                        if (isset($result['success']) && $result['success']) {
                            echo "  " . ($result['message'] ?? 'Address linked successfully') . "\n";
                        } else {
                            // Only print result if it has meaningful data
                            if (!isset($result['response']) || !empty($result['response'])) {
                                print_r($result);
                            }
                        }
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/link-address-with-order-v-3/', 'Link Address with Order API');
                break;
                
            // Refunds API
            case '20':
                echo Colors::BOLD . "Initiate Refund\n" . Colors::RESET;
                $token = getMerchantTokenInput();
                printInfo("Enter either transaction_id OR invoice_id (at least one required)\n");
                $transactionId = getInput("Enter Transaction ID (or press Enter to skip): ", false);
                $invoiceId = getInput("Enter Invoice ID (or press Enter to skip): ", false);
                if (!$transactionId && !$invoiceId) {
                    printError("Either Transaction ID or Invoice ID is required.\n");
                    break;
                }
                $refundAmount = getInput("Enter Refund Amount (or press Enter for full refund): ", false);
                $comment = getInput("Enter Refund Comment (optional): ", false);
                $refundRequestId = getInput("Enter Refund Request ID (optional, for idempotency): ", false);
                $data = [];
                if ($transactionId) {
                    $data['transaction_id'] = $transactionId;
                }
                if ($invoiceId) {
                    $data['invoice_id'] = $invoiceId;
                }
                if ($refundAmount) {
                    $data['refund_amount'] = floatval($refundAmount);
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
                        if (!$skuId) break;
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
                    $result = $api->refunds()->initiateRefund($data, $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Refund initiated successfully!\n");
                        // Display refund information
                        if (is_array($result)) {
                            echo "  Refund ID: " . ($result['refund_id'] ?? $result['nimbbl_refund_id'] ?? 'N/A') . "\n";
                            echo "  Transaction ID: " . ($result['transaction_id'] ?? $result['nimbbl_transaction_id'] ?? 'N/A') . "\n";
                            echo "  Refund Amount: " . ($result['refund_amount'] ?? 0) . " " . ($result['currency'] ?? 'INR') . "\n";
                            echo "  Status: " . ($result['status'] ?? 'N/A') . "\n";
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
                printDocLink('https://nimbbl.biz/docs/api-reference/refund-a-payment-v-3/', 'Initiate Refund API');
                break;
                
            // Transactions API
            case '21':
                echo Colors::BOLD . "Transaction Enquiry\n" . Colors::RESET;
                $token = getMerchantTokenInput();
                printInfo("Enter one of: transaction_id, order_id, or invoice_id (at least one required)\n");
                $transactionId = getInput("Enter Transaction ID (or press Enter to skip): ", false);
                $orderId = getInput("Enter Order ID (or press Enter to skip): ", false);
                $invoiceId = getInput("Enter Invoice ID (or press Enter to skip): ", false);
                $transactionData = [];
                if ($transactionId) {
                    $transactionData['transaction_id'] = $transactionId;
                }
                if ($orderId) {
                    $transactionData['order_id'] = $orderId;
                }
                if ($invoiceId) {
                    $transactionData['invoice_id'] = $invoiceId;
                }
                if (empty($transactionData)) {
                    printError("At least one of Transaction ID, Order ID, or Invoice ID is required.\n");
                    break;
                }
                try {
                    $result = $api->transactions()->transactionEnquiry($transactionData, $token);
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
                                    echo "    Transaction ID: " . ($transaction['transaction_id'] ?? $transaction['nimbbl_transaction_id'] ?? 'N/A') . "\n";
                                    echo "    Status: " . ($transaction['status'] ?? 'N/A') . "\n";
                                    echo "    Amount: " . ($transaction['amount'] ?? 0) . " " . ($transaction['currency'] ?? 'INR') . "\n";
                                    if (isset($transaction['payment_mode_code'])) {
                                        echo "    Payment Mode: " . $transaction['payment_mode_code'] . "\n";
                                    }
                                }
                            } else {
                                echo "\nNo transactions found for this order.\n";
                            }
                            
                            // Display order information
                            if (isset($result['order']) && is_array($result['order'])) {
                                echo "\nOrder:\n";
                                $order = $result['order'];
                                echo "  Order ID: " . ($order['nimbbl_order_id'] ?? 'N/A') . "\n";
                                echo "  Invoice ID: " . ($order['invoice_id'] ?? 'N/A') . "\n";
                                echo "  Status: " . ($order['status'] ?? 'N/A') . "\n";
                                echo "  Amount: " . ($order['total_amount'] ?? 0) . " " . ($order['currency'] ?? 'INR') . "\n";
                                if (isset($order['offer_discount']) && $order['offer_discount'] > 0) {
                                    echo "  Offer Discount: " . ($order['offer_discount']) . " " . ($order['currency'] ?? 'INR') . "\n";
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
                printDocLink('https://nimbbl.biz/docs/api-reference/transaction-enquiry-v-3/', 'Transaction Enquiry API');
                break;
                
            // Checkout Utilities API - cases 22-29
            case '22':
                echo Colors::BOLD . "List Payment Modes\n" . Colors::RESET;
                $token = getOrderTokenInput();
                $orderId = getInput("Enter Order ID: ");
                if ($orderId === null) {
                    printError("Order ID is required.\n");
                    break;
                }
                try {
                    $result = $api->checkoutUtilities()->listPaymentModes(['order_id' => $orderId], $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Payment modes retrieved successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/list-of-payment-modes-v-3/', 'List Payment Modes API');
                break;
                
            case '23':
                echo Colors::BOLD . "List Banks\n" . Colors::RESET;
                $token = getOrderTokenInput();
                $orderId = getInput("Enter Order ID: ");
                if ($orderId === null) {
                    printError("Order ID is required.\n");
                    break;
                }
                try {
                    $result = $api->checkoutUtilities()->listBanks(['order_id' => $orderId], $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Banks retrieved successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/list-of-banks-v-3/', 'List Banks API');
                break;
                
            case '24':
                echo Colors::BOLD . "List Wallets\n" . Colors::RESET;
                $token = getOrderTokenInput();
                $orderId = getInput("Enter Order ID: ");
                if ($orderId === null) {
                    printError("Order ID is required.\n");
                    break;
                }
                try {
                    $result = $api->checkoutUtilities()->listWallets(['order_id' => $orderId], $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Wallets retrieved successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/list-of-wallets-v-3/', 'List Wallets API');
                break;
                
            case '25':
                echo Colors::BOLD . "List EMIs\n" . Colors::RESET;
                $token = getOrderTokenInput();
                $orderId = getInput("Enter Order ID: ");
                if ($orderId === null) {
                    printError("Order ID is required.\n");
                    break;
                }
                try {
                    $result = $api->checkoutUtilities()->listEMIs(['order_id' => $orderId], $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("EMIs retrieved successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/list-of-em-is-v-3/', 'List EMIs API');
                break;
                
            case '26':
                echo Colors::BOLD . "Get Offers\n" . Colors::RESET;
                $token = getOrderTokenInput();
                $orderId = getInput("Enter Order ID: ");
                if ($orderId === null) {
                    printError("Order ID is required.\n");
                    break;
                }
                $paymentModeCode = getInput("Enter Payment Mode Code (card/net_banking/wallet/upi/pay_later/all): ");
                if ($paymentModeCode === null) {
                    printError("Payment Mode Code is required.\n");
                    break;
                }
                $data = [
                    'order_id' => $orderId,
                    'payment_mode_code' => $paymentModeCode
                ];
                // Optional fields
                $currency = getInput("Enter Currency (optional, press Enter to skip): ", false);
                if ($currency) {
                    $data['currency'] = $currency;
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
                } elseif ($paymentModeCode === 'net_banking') {
                    echo "   ℹ Bank Code is required for net_banking payment mode.\n";
                    echo "   Common bank codes: HDFC, ICICI, SBI, AXIS, KOTAK, etc.\n";
                    $bankCode = getInput("Enter Bank Code (default: HDFC): ", false) ?: 'HDFC';
                    $data['bank_code'] = $bankCode;
                } elseif ($paymentModeCode === 'wallet') {
                    $walletCode = getInput("Enter Wallet Code (optional, press Enter to skip): ", false);
                    if ($walletCode) {
                        $data['wallet_code'] = $walletCode;
                    }
                } elseif ($paymentModeCode === 'upi') {
                    $upiId = getInput("Enter UPI ID (optional, press Enter to skip): ", false);
                    if ($upiId) {
                        $data['upi_id'] = $upiId;
                    }
                }
                try {
                    $result = $api->checkoutUtilities()->getOffers($data, $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Offers retrieved successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/offers-v-3/', 'Get Offers API');
                break;
                
            case '27':
                echo Colors::BOLD . "Get Card BIN Data\n" . Colors::RESET;
                $token = getOrderTokenInput();
                $cardBin = getInput("Enter Card BIN (first 6 digits): ");
                if ($cardBin === null) {
                    printError("Card BIN is required.\n");
                    break;
                }
                $orderId = getInput("Enter Order ID (optional, press Enter to skip): ", false);
                $data = ['card_bin' => $cardBin];
                if ($orderId) {
                    $data['order_id'] = $orderId;
                }
                try {
                    $result = $api->checkoutUtilities()->getCardBinData($data, $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("Card BIN data retrieved successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/get-card-bin-data-v-3/', 'Get Card BIN Data API');
                break;
                
            case '28':
                echo Colors::BOLD . "Validate UPI VPA\n" . Colors::RESET;
                $token = getOrderTokenInput();
                $upiId = getInput("Enter UPI ID (e.g., user@paytm): ");
                if ($upiId === null) {
                    printError("UPI ID is required.\n");
                    break;
                }
                try {
                    $result = $api->checkoutUtilities()->validateUpiVpa(['upi_id' => $upiId], $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("UPI VPA validated!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                printDocLink('https://nimbbl.biz/docs/api-reference/validate-upi-vpa-v-3/', 'Validate UPI VPA API');
                break;
                
            case '29':
                echo Colors::BOLD . "Get UPI App Details\n" . Colors::RESET;
                $token = getOrderTokenInput();
                $platform = getInput("Enter Platform (ios/android): ");
                if ($platform === null) {
                    printError("Platform is required.\n");
                    break;
                }
                // Validate platform value
                if (!in_array(strtolower($platform), ['ios', 'android'])) {
                    printError("Platform must be 'ios' or 'android'.\n");
                    break;
                }
                try {
                    $result = $api->checkoutUtilities()->getUpiAppDetails(['platform' => strtolower($platform)], $token);
                    if (isset($result['error'])) {
                        printError("Error: " . print_r($result['error'], true) . "\n");
                    } else {
                        printSuccess("UPI app details retrieved successfully!\n");
                        print_r($result);
                    }
                } catch (Exception $e) {
                    printException($e);
                }
                break;
                
            // Webhooks
            case '30':
                echo Colors::BOLD . "Webhook Handling\n" . Colors::RESET;
                printInfo("Webhook handling is designed for HTTP requests, not CLI.\n");
                printInfo("To set up webhooks:\n");
                printInfo("1. Deploy webhook-handler.php to your web server (HTTPS required)\n");
                printInfo("2. Ensure the URL accepts POST requests and returns 200 within 15 seconds\n");
                printInfo("3. Configure webhook URL in Nimbbl Dashboard or contact support@nimbbl.tech\n");
                printInfo("4. Webhooks will be sent to your configured URL\n");
                printInfo("\nImportant:\n");
                printInfo("- URL must be HTTPS and publicly accessible\n");
                printInfo("- Must return 200 response within 15 seconds\n");
                printInfo("- Handle idempotency (same webhook may be received multiple times)\n");
                printInfo("- Webhook order is not guaranteed\n");
                printInfo("\nSupported Events:\n");
                printInfo("- payment_success, payment_failed, payment_reversing\n");
                printInfo("- payment_reversal_failed, payment_reversed\n");
                printInfo("- refund_success, refund_failed, refund_pending\n");
                printInfo("\nFor implementation details, check example/webhook-handler.php\n");
                printDocLink('https://nimbbl.biz/docs/standard-checkout/completing-integration/keeping-system-updated/', 'Webhooks Documentation');
                break;
                
            // Examples
            case '31':
                echo Colors::BOLD . "Encryption Examples
" . Colors::RESET . "
";
                $_SERVER['SCRIPT_NAME'] = __FILE__;
                require __DIR__ . '/encryption-examples.php';
                break;

            case '32':
                echo Colors::BOLD . "Exception Handling Examples
" . Colors::RESET . "
";
                $_SERVER['SCRIPT_NAME'] = __FILE__;
                require __DIR__ . '/exception-handling-examples.php';
                break;

            case '33':
                echo Colors::BOLD . "Run All Examples
" . Colors::RESET . "
";
                $_SERVER['SCRIPT_NAME'] = __FILE__;
                require __DIR__ . '/index.php';
                break;

            default:
                printError("Invalid choice. Please select a number from 0-33.\n");
                break;
        }
    } catch (Exception $e) {
        printException($e);
        if (isset($config['enable_logging']) && $config['enable_logging']) {
            echo "Check logs/nimbbl_debug.log for details.\n";
        }
    }
    
    printSeparator();
    echo "\n";
    echo "Press Enter to continue...";
    fgets(STDIN);
    echo "\n";
}
