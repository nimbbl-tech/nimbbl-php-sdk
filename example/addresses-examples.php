#!/usr/bin/env php
<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Addresses API Examples
 * 
 * This example demonstrates how to use the Addresses API
 * 
 * This file can be:
 * 1. Executed standalone: php addresses-examples.php
 * 2. Included from cli.php to use the functions: listAddressesExample(), createAddressExample(), updateAddressExample(), deleteAddressExample(), importAddressesExample(), checkAddressEligibilityExample(), linkAddressWithOrderExample()
 * 
 * API Documentation: https://nimbbl.biz/docs/category/api-reference/addresses/
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';
require_once __DIR__ . '/utils/cli_output.php';

// All helper functions are available from cli_output.php
use Nimbbl\Api\Common\JsonKeys;

/**
 * List Addresses - Function to be called from cli.php or standalone
 */
function listAddressesExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);

    $data = [];

    // user_id - required for listing addresses
    $userId = getInput("Enter User ID: ", false);
    if ($userId) {
        $data[JsonKeys::USER_ID] = $userId;
    }

    // amount - order amount to calculate shipping charges
    $amount = getInput("Enter Order Amount (for shipping calculation, optional): ", false);
    if ($amount) {
        $data[JsonKeys::AMOUNT] = floatval($amount);
    }

    // currency - currency code in ISO-4217 format
    $currency = getInput("Enter Currency (ISO-4217 format, e.g., INR, optional): ", false);
    if ($currency) {
        $data[JsonKeys::CURRENCY] = strtoupper($currency);
    }

    if (empty($data)) {
        printWarning("No query parameters provided. At least one parameter (user_id, amount, currency) is recommended.\n");
        $continue = getInput("Continue anyway? (y/n): ", false);
        if (strtolower($continue) !== 'y') {
            return;
        }
    }

    try {
        $result = $api->addresses()->listAddresses($data);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Addresses retrieved successfully!\n");
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * Create Address - Function to be called from cli.php or standalone
 */
function createAddressExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);

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

    if (
        $firstName === null || $lastName === null || $address1 === null || $area === null ||
        $city === null || $state === null || $pincode === null || $addressType === null
    ) {
        printError("First Name, Last Name, Address Line 1, Area, City, State, Pincode, and Address Type are required.\n");
        return;
    }

    // Build address object
    $addressData = [
        JsonKeys::FIRST_NAME => $firstName,
        JsonKeys::LAST_NAME => $lastName,
        JsonKeys::ADDRESS_1 => $address1,
        JsonKeys::AREA => $area,
        JsonKeys::CITY => $city,
        JsonKeys::STATE => $state,
        JsonKeys::PINCODE => $pincode,
        JsonKeys::ADDRESS_TYPE => $addressType
    ];

    // Optional fields
    $street = getInput("Street (optional): ", false);
    if ($street)
        $addressData[JsonKeys::STREET] = $street;

    $landmark = getInput("Landmark (optional): ", false);
    if ($landmark)
        $addressData[JsonKeys::LANDMARK] = $landmark;

    $label = getInput("Label (optional): ", false);
    if ($label)
        $addressData['label'] = $label;

    $country = getInput("Country (optional, default: India): ", false);
    $addressData[JsonKeys::COUNTRY] = $country ?: 'India';

    $linkAs = getInput("Link As (shipping/billing, optional): ", false);
    if ($linkAs && in_array(strtolower($linkAs), ['shipping', 'billing'])) {
        $addressData[JsonKeys::LINK_AS] = strtolower($linkAs);
    }

    // Build request body with addresses array
    $data = [
        JsonKeys::ADDRESSES => [$addressData]
    ];

    if ($userId) {
        $data['user_id'] = $userId;
    }

    // Amount and currency for shipping calculation
    $amount = getInput("Order Amount (for shipping calculation, default: 5000): ", false);
    $currency = getInput("Currency (default: INR): ", false);

    $data[JsonKeys::AMOUNT] = $amount ? floatval($amount) : 5000;
    $data[JsonKeys::CURRENCY] = $currency ?: 'INR';

    try {
        $result = $api->addresses()->createAddress($data);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Address created successfully!\n");
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * Update Address - Function to be called from cli.php or standalone
 */
function updateAddressExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);

    $addressId = getInput("Enter Address ID: ");
    if ($addressId === null) {
        printError("Address ID is required.\n");
        return;
    }
    printInfo("Enter address fields to update (press Enter to skip)\n");
    $data = [];
    $line1 = getInput("Address Line 1: ", false);
    if ($line1)
        $data[JsonKeys::ADDRESS_1] = $line1;
    $city = getInput("City: ", false);
    if ($city)
        $data[JsonKeys::CITY] = $city;
    try {
        $result = $api->addresses()->updateAddress($addressId, $data);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Address updated successfully!\n");
            print_r($result);
        }
    } catch (Exception $e) {
        printException($e);
    }
}

/**
 * Delete Address - Function to be called from cli.php or standalone
 */
function deleteAddressExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);

    $addressId = getInput("Enter Address ID: ");
    if ($addressId === null) {
        printError("Address ID is required.\n");
        return;
    }
    try {
        $result = $api->addresses()->deleteAddress($addressId);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Address deleted successfully!\n");
        }
    } catch (Exception $e) {
        printException($e);
    }
}


/**
 * Import Addresses - Function to be called from cli.php or standalone
 */
function importAddressesExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);

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
            return;
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
            return;
        }
        $importData = [
            'command' => $command,
            'provider' => $provider,
            'otp' => $otp
        ];
    } else {
        printError("Invalid choice. Must be 1 or 2.\n");
        return;
    }

    try {
        $result = $api->addresses()->importAddresses($importData);
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
}

/**
 * Check Address Eligibility - Function to be called from cli.php or standalone
 */
function checkAddressEligibilityExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);

    printInfo("Enter eligibility check details:\n");
    $pincode = getInput("Pincode (required): ");
    if ($pincode === null) {
        printError("Pincode is required.\n");
        return;
    }

    $data = [JsonKeys::PINCODE => $pincode];

    $countryCode = getInput("Country Code (optional, default: IND): ", false);
    if ($countryCode) {
        $data[JsonKeys::COUNTRY_CODE] = $countryCode;
    }

    $amount = getInput("Order Amount (optional, for shipping calculation): ", false);
    if ($amount) {
        $data['amount'] = floatval($amount);
    }

    $currency = getInput("Currency (optional, e.g., INR): ", false);
    if ($currency) {
        $data[JsonKeys::CURRENCY] = $currency;
    }

    try {
        $result = $api->addresses()->checkAddressEligibility($data);
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
}

/**
 * Link Address with Order - Function to be called from cli.php or standalone
 */
function linkAddressWithOrderExample()
{
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);

    $addressId = getInput("Enter Address ID (required): ");
    if ($addressId === null) {
        printError("Address ID is required.\n");
        return;
    }
    $orderId = getInput("Enter Order ID (optional): ", false);
    echo "Link as:\n";
    echo "1. shipping\n";
    echo "2. billing\n";
    $linkAsChoice = getInput("Enter choice (1 or 2): ");
    $linkAs = ($linkAsChoice === '1') ? 'shipping' : (($linkAsChoice === '2') ? 'billing' : null);
    if ($linkAs === null) {
        printError("Invalid choice. Must be 'shipping' or 'billing'.\n");
        return;
    }
    $linkData = [
        JsonKeys::ADDRESS => [
            JsonKeys::ADDRESS_ID => $addressId
        ],
        'link_as' => $linkAs
    ];
    if ($orderId) {
        $linkData[JsonKeys::ORDER_ID] = $orderId;
    }
    try {
        $result = $api->addresses()->linkAddressWithOrder($linkData);
        if (isset($result['error'])) {
            printError("Error: " . print_r($result['error'], true) . "\n");
        } else {
            printSuccess("Order linked to address successfully!\n");
            if (isset($result['success']) && $result['success']) {
                echo "  " . ($result['message'] ?? 'Address linked successfully') . "\n";
            } else {
                if (!isset($result['response']) || !empty($result['response'])) {
                    print_r($result);
                }
            }
        }
    } catch (Exception $e) {
        printException($e);
    }
}

// Only run the full example if this file is executed directly (not included)
if (basename($_SERVER['PHP_SELF']) === 'addresses-examples.php') {
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

    echo Colors::CYAN . Colors::BOLD . "=== Addresses API Examples ===" . Colors::RESET . "\n\n";

    // Run List Addresses example
    echo Colors::BLUE . Colors::BOLD . "Step 1: List Addresses" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    listAddressesExample();

    echo "\n";

    // Run Create Address example
    echo Colors::BLUE . Colors::BOLD . "Step 2: Create Address" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    createAddressExample();

    echo "\n";

    // Run Update Address example
    echo Colors::BLUE . Colors::BOLD . "Step 3: Update Address" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    updateAddressExample();

    echo "\n";

    // Run Delete Address example
    echo Colors::BLUE . Colors::BOLD . "Step 4: Delete Address" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    deleteAddressExample();

    echo "\n";

    echo "\n";

    // Run Import Addresses example
    echo Colors::BLUE . Colors::BOLD . "Step 5: Import Addresses" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    importAddressesExample();

    echo "\n";

    // Run Check Address Eligibility example
    echo Colors::BLUE . Colors::BOLD . "Step 6: Check Address Eligibility" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    checkAddressEligibilityExample();

    echo "\n";

    // Run Link Address with Order example
    echo Colors::BLUE . Colors::BOLD . "Step 7: Link Address with Order" . Colors::RESET . "\n";
    echo str_repeat('-', 60) . "\n";
    linkAddressWithOrderExample();

    echo "\n" . Colors::CYAN . Colors::BOLD . "=== Addresses API Examples Complete ===" . Colors::RESET . "\n";
    echo "\nFor more information, see: https://nimbbl.biz/docs/category/api-reference/addresses/\n";
}
