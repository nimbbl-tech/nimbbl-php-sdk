#!/usr/bin/env php
<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Payment Links API Examples
 * 
 * This example demonstrates how to use the Payment Links API
 * 
 * This file can be:
 * 1. Executed standalone: php payment-links-examples.php
 * 2. Included from cli.php to use the functions: createPaymentLinkExample(), updatePaymentLinkExample(), enquiryPaymentLinkExample(), performPaymentLinkActionsExample()
 * 
 * API Documentation: https://nimbbl.biz/docs/category/api-reference/payment-link/
 */

// All helper functions are available from cli_output.php
use Nimbbl\Api\Common\JsonKeys;

/**
 * Create Payment Link - Function to be called from cli.php or standalone
 */
function createPaymentLinkExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);



    printInfo("Enter payment link details:\n");
    $invoiceId = getInput("Enter Invoice ID: ");
    if ($invoiceId === null) {
        printError("Invoice ID is required.\n");
        return;
    }
    $totalAmount = getInput("Enter Total Amount: ");
    if ($totalAmount === null) {
        printError("Total Amount is required.\n");
        return;
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
            'rate' => floatval($rate),
            'quantity' => intval($quantity),
            'amount_before_tax' => floatval($amountBeforeTax),
            'tax' => floatval($tax),
            'total_amount' => floatval($itemTotalAmount)
        ];
        if ($description)
            $item['description'] = $description;
        if ($imageUrl)
            $item['image_url'] = $imageUrl;

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

    // Required field: expires_at (default: 24 hours from now)
    printInfo("\nRequired Field:\n");
    $defaultExpiresAt = (new DateTime('+24 hours', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    $expiresAt = getInput("Expires At (YYYY-MM-DD HH:MM:SS, UTC, default: {$defaultExpiresAt}): ", false) ?: $defaultExpiresAt;
    if (empty($expiresAt)) {
        printError("Expires At is required.\n");
        return;
    }

    // Ask if user wants to use defaults for optional fields
    $useDefaults = getInput("\nUse default values for optional fields? (y/N): ", false);
    $useDefaultsFlag = (strtolower($useDefaults) === 'y');

    if ($useDefaultsFlag) {
        $description = "xxx";
        $termsAndConditions = "xxx";
        $callbackUrl = "https://www.google.com";
        $sendSms = 'y';
        $sendEmail = 'y';
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
        $notificationScheduledAt = null;
        $emailTemplateId = null;
        $smsTemplateId = null;
        printInfo("Using defaults from curl example:\n");
        printInfo("  description='xxx', terms_and_conditions='xxx', callback_url='https://www.google.com'\n");
        printInfo("  send_sms=true, send_email=true\n");
        printInfo("  bank_account: account_number='037801513988', name='Vasudha Maini', ifsc='ICIC0000378'\n");
        printInfo("  custom_attributes: Name='Vasudha', Place='Delhi', Animal='Tiger', Thing='Pen'\n");
        printInfo("  top-level serial_numbers: ['359043372654548', '359043371395481']\n");
    } else {
        printInfo("\nOptional Fields (press Enter to skip any):\n");
        $description = getInput("Description (optional): ", false);
        $termsAndConditions = getInput("Terms and Conditions (optional): ", false);
        $callbackUrl = getInput("Callback URL (optional): ", false);

        $sendSms = getInput("Send SMS? (Y/n, default: Y): ", false);
        $sendEmail = getInput("Send Email? (y/N, default: N): ", false);

        $notificationScheduledAt = getInput("Notification Scheduled At (YYYY-MM-DD HH:MM:SS, optional): ", false);
        $emailTemplateId = getInput("Email Template ID (optional): ", false);
        $smsTemplateId = getInput("SMS Template ID (optional): ", false);

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
    if ($useDefaultsFlag) {
        $sendSmsValue = true;
        $sendEmailValue = true;
    } else {
        $sendSmsValue = ($sendSms === '' || strtolower($sendSms) === 'y' || strtolower($sendSms) === 'yes') ? true : false;
        $sendEmailValue = (strtolower($sendEmail) === 'y' || strtolower($sendEmail) === 'yes') ? true : false;
    }

    $data = [
        JsonKeys::INVOICE_ID => $invoiceId,
        JsonKeys::TOTAL_AMOUNT => floatval($totalAmount),
        JsonKeys::CURRENCY => $currency,
        'expires_at' => $expiresAt,
        JsonKeys::ORDER_LINE_ITEMS => $orderLineItems,
        JsonKeys::USER => [
            JsonKeys::EMAIL => $email,
            JsonKeys::FIRST_NAME => $firstName,
            JsonKeys::COUNTRY_CODE => $countryCode,
            JsonKeys::MOBILE_NUMBER => $mobileNumber
        ],
        'send_sms' => $sendSmsValue,
        'send_email' => $sendEmailValue
    ];

    if ($lastName)
        $data[JsonKeys::USER][JsonKeys::LAST_NAME] = $lastName;
    if ($description)
        $data[JsonKeys::DESCRIPTION] = $description;
    if ($termsAndConditions)
        $data['terms_and_conditions'] = $termsAndConditions;
    if ($callbackUrl)
        $data['callback_url'] = $callbackUrl;
    if ($notificationScheduledAt)
        $data['notification_scheduled_at'] = $notificationScheduledAt;
    if ($emailTemplateId)
        $data['email_template_id'] = $emailTemplateId;
    if ($smsTemplateId)
        $data['sms_template_id'] = $smsTemplateId;
    if ($bankAccount)
        $data['bank_account'] = $bankAccount;
    if ($customAttributes)
        $data['custom_attributes'] = $customAttributes;
    if ($topLevelSerialNumbers)
        $data['serial_numbers'] = $topLevelSerialNumbers;

    try {
        $result = $api->paymentLinks()->createPaymentLink($data);
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
}

/**
 * Update Payment Link - Function to be called from cli.php or standalone
 */
function updatePaymentLinkExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);



    printInfo("Identify payment link using:\n");
    echo "1. Invoice ID\n";
    echo "2. Payment Link ID\n";
    $identifierType = getInput("Choose (1 or 2): ");

    $data = [];
    if ($identifierType === '1') {
        $invoiceId = getInput("Enter Invoice ID: ");
        if ($invoiceId === null) {
            printError("Invoice ID is required.\n");
            return;
        }
        $data[JsonKeys::INVOICE_ID] = $invoiceId;
    } elseif ($identifierType === '2') {
        $paymentLinkId = getInput("Enter Payment Link ID: ");
        if ($paymentLinkId === null) {
            printError("Payment Link ID is required.\n");
            return;
        }
        $data[JsonKeys::PAYMENT_LINK_ID] = $paymentLinkId;
    } else {
        printError("Invalid choice. Please choose 1 or 2.\n");
        return;
    }

    printInfo("\nNote: Payment links can only be updated when status is 'created'.\n");
    printInfo("Enter fields to update (at least one field is required):\n");

    // User information
    $firstName = getInput("User First Name (optional): ", false);
    $lastName = getInput("User Last Name (optional): ", false);
    if ($firstName || $lastName) {
        $data[JsonKeys::USER] = [];
        if ($firstName)
            $data[JsonKeys::USER][JsonKeys::FIRST_NAME] = $firstName;
        if ($lastName)
            $data[JsonKeys::USER][JsonKeys::LAST_NAME] = $lastName;
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
                'total_amount' => $totalAmountItem,
                'tax' => $tax ?: '0',
            ];
            if ($description)
                $item['description'] = $description;
            if ($imageUrl)
                $item['image_url'] = $imageUrl;
            if ($amountBeforeTax) {
                $item['amount_before_tax'] = $amountBeforeTax;
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
            $data[JsonKeys::ORDER_LINE_ITEMS] = $orderLineItems;
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
        return;
    }

    try {
        $result = $api->paymentLinks()->updatePaymentLink($data);
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
}

/**
 * Payment Link Enquiry - Function to be called from cli.php or standalone
 */
function enquiryPaymentLinkExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);



    printInfo("Identify payment link using:\n");
    echo "1. Invoice ID\n";
    echo "2. Payment Link ID\n";
    $identifierType = getInput("Choose (1 or 2): ");

    $data = [];
    if ($identifierType === '1') {
        $invoiceId = getInput("Enter Invoice ID: ");
        if ($invoiceId === null) {
            printError("Invoice ID is required.\n");
            return;
        }
        $data[JsonKeys::INVOICE_ID] = $invoiceId;
    } elseif ($identifierType === '2') {
        $paymentLinkId = getInput("Enter Payment Link ID: ");
        if ($paymentLinkId === null) {
            printError("Payment Link ID is required.\n");
            return;
        }
        $data[JsonKeys::PAYMENT_LINK_ID] = $paymentLinkId;
    } else {
        printError("Invalid choice. Please choose 1 or 2.\n");
        return;
    }

    try {
        $result = $api->paymentLinks()->enquiryPaymentLink($data);
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
}

/**
 * Payment Link Actions - Function to be called from cli.php or standalone
 */
function performPaymentLinkActionsExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);



    printInfo("Identify payment link using:\n");
    echo "1. Invoice ID\n";
    echo "2. Payment Link ID\n";
    $identifierType = getInput("Choose (1 or 2): ");

    $data = [];
    if ($identifierType === '1') {
        $invoiceId = getInput("Enter Invoice ID: ");
        if ($invoiceId === null) {
            printError("Invoice ID is required.\n");
            return;
        }
        $data[JsonKeys::INVOICE_ID] = $invoiceId;
    } elseif ($identifierType === '2') {
        $paymentLinkId = getInput("Enter Payment Link ID: ");
        if ($paymentLinkId === null) {
            printError("Payment Link ID is required.\n");
            return;
        }
        $data[JsonKeys::PAYMENT_LINK_ID] = $paymentLinkId;
    } else {
        printError("Invalid choice. Please choose 1 or 2.\n");
        return;
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
        return;
    }

    try {
        $result = $api->paymentLinks()->performPaymentLinkActions($data);
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
}

// Only run the full example if this file is executed directly (not included)
if (basename($_SERVER['PHP_SELF']) === 'payment-links-examples.php') {
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

    echo Colors::CYAN . Colors::BOLD . "=== Payment Links API Examples ===" . Colors::RESET . "\n\n";

    // Run Create Payment Link example
    echo Colors::BLUE . Colors::BOLD . "Step 1: Create Payment Link" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    createPaymentLinkExample();

    echo "\n";

    // Run Update Payment Link example
    echo Colors::BLUE . Colors::BOLD . "Step 2: Update Payment Link" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    updatePaymentLinkExample();

    echo "\n";

    // Run Payment Link Enquiry example
    echo Colors::BLUE . Colors::BOLD . "Step 3: Payment Link Enquiry" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    enquiryPaymentLinkExample();

    echo "\n";

    // Run Payment Link Actions example
    echo Colors::BLUE . Colors::BOLD . "Step 4: Payment Link Actions" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    performPaymentLinkActionsExample();

    echo "\n" . Colors::CYAN . Colors::BOLD . "=== Payment Links API Examples Complete ===" . Colors::RESET . "\n";
    echo "\nFor more information, see: https://nimbbl.biz/docs/category/api-reference/payment-link/\n";
}
