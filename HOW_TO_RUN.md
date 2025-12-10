# How to Run Nimbbl PHP SDK

## 📋 Prerequisites

✅ **PHP**: Version 7.4 or higher (You have PHP 8.4.14 - Perfect!)
✅ **Composer**: Dependency manager (You have Composer 2.8.12 - Perfect!)

## 🚀 Quick Start

### Step 1: Install Dependencies

```bash
cd /Users/sandeepyadav/Downloads/sdks/nimbbl-php-sdk
composer install
```

This will install all required dependencies including:
- `rmccue/requests` - HTTP client library
- `phpunit/phpunit` - Testing framework (dev dependency)

### Step 2: Configure Your Credentials

```bash
# Copy the example config file
cp example/config.php.example example/config.php

# Edit the config file with your credentials
# You can use any text editor: nano, vim, VS Code, etc.
nano example/config.php
```

**Update `example/config.php` with your credentials:**

```php
<?php
return [
    // Your Nimbbl API credentials
    'access_key' => 'your_access_key_here',
    'access_secret' => 'your_access_secret_here',
    
    // API Base URL
    // Production: https://api.nimbbl.tech/api/
    // UAT/Sandbox: https://apipp.nimbbl.tech/api/
    'api_url' => 'https://apipp.nimbbl.tech/api/',
    
    // API Version
    'api_version' => 'v3',
    
    // Logging (optional)
    'enable_logging' => true,
    'log_file' => __DIR__ . '/../logs/nimbbl_debug.log',
];
```

### Step 3: Run Examples

#### Option 1: Interactive CLI Menu (Recommended - Similar to .NET SDK)

```bash
php example/cli.php
```

This provides an interactive menu similar to the .NET SDK:
- ✅ Easy navigation with numbered options
- ✅ Color-coded output
- ✅ Runs individual examples or all at once
- ✅ User-friendly interface

#### Option 2: Run All Examples (Non-Interactive)

```bash
php example/index.php
```

This runs a comprehensive set of examples:
- ✅ Create Order
- ✅ Get Order by Order ID
- ✅ Initiate Refund
- ✅ Payment Signature Verification
- ✅ Addresses API
- ✅ Checkout Utilities
- ✅ Transaction Status

#### Option 2: Run Individual Examples

**Order Management:**
```bash
# Create an order
php example/create-order.php

# Get order details
php example/get-order.php
```

**Payment Processing:**
```bash
# Payments API examples (Initiate, Complete, Resend OTP)
php example/payments-examples.php

# Payment Links examples (Create, Update, Enquiry, Actions)
php example/payment-links-examples.php

# Checkout Utilities (payment modes, banks, wallets, EMIs, offers, etc.)
php example/checkout-utilities-examples.php
```

**Addresses:**
```bash
# Addresses API examples (List, Create, Get, Update, Delete, Import, Check Eligibility, Link Order)
php example/addresses-examples.php
```

**Refunds & Status:**
```bash
# Refund examples (Full and Partial)
php example/refund-examples.php

# Transaction status enquiry
php example/transaction-status.php
```

**Error Handling:**
```bash
# Exception handling examples
php example/exception-handling-examples.php
```

**Webhooks:**
```bash
# Webhook signature verification
php example/webhook-handler.php
```

### Step 4: Run Tests

#### Run All API Tests

```bash
php tests/test-all-apis.php
```

This comprehensive test suite will:
- Test all API endpoints
- Verify request/response formats
- Check error handling
- Validate authentication

#### Run Individual Test Files

```bash
# Test specific APIs
php tests/OrderTest.php
php tests/AddressTest.php
php tests/PaymentTest.php
php tests/PaymentLinkTest.php
php tests/CheckoutUtilitiesTest.php
php tests/TransactionStatusTest.php
php tests/TransactionTest.php
php tests/RefundTest.php
```

## 📝 Example Output

When you run an example, you'll see output like:

```
=== Nimbbl PHP SDK Examples ===

1. Creating an Order...
   Order created successfully!
   Order ID: o_XXXXXXXXXX
   Invoice ID: INV-1234567890
   Token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

2. Getting Order by Order ID...
   Order retrieved successfully!
   Order ID: o_XXXXXXXXXX
   Status: created
```

## 🔧 Troubleshooting

### Error: "Configuration file not found"
```bash
# Make sure config.php exists
cp example/config.php.example example/config.php
# Then edit config.php with your credentials
```

### Error: "Class not found" or "Autoload error"
```bash
# Install Composer dependencies
composer install
```

### Error: "Invalid credentials"
- ✅ Check your `access_key` and `access_secret` in `example/config.php`
- ✅ Make sure you're using the correct environment (UAT vs Production)
- ✅ Verify your API keys are active in the Nimbbl dashboard

### Error: "API request failed"
- ✅ Check your internet connection
- ✅ Verify the API URL is correct (`https://apipp.nimbbl.tech/api/` for UAT)
- ✅ Check if you're hitting rate limits
- ✅ Review the error message for specific API errors

### Error: "Permission denied" when writing logs
```bash
# Make sure logs directory is writable
chmod 755 logs/
touch logs/nimbbl_debug.log
chmod 644 logs/nimbbl_debug.log
```

## 📊 Viewing Logs

The SDK logs all API requests and responses. View logs:

```bash
# View logs in real-time
tail -f logs/nimbbl_debug.log

# View last 100 lines
tail -n 100 logs/nimbbl_debug.log

# Clear logs
./clear-logs.sh
```

## 🎯 Quick Reference

### Basic Usage

```php
<?php
require_once 'vendor/autoload.php';

use Nimbbl\Api\NimbblApi;
use Nimbbl\Api\NimbblRequest;

// Initialize the SDK
$api = new NimbblApi(
    'your_access_key',
    'your_access_secret',
    'https://apipp.nimbbl.tech/api/', // UAT
    'v3'
);

// Step 1: Generate merchant token
$request = new NimbblRequest();
$merchantToken = $request->generateToken()['token'];

// Step 2: Create an order (uses merchant token initially)
$order = $api->orders()->createOrder([
    'invoice_id' => 'INV-12345',
    'amount_before_tax' => 90.00,
    'tax' => 10.00,
    'total_amount' => 100.00,
    'currency' => 'INR',
    'user' => [
        'email' => 'customer@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_number' => '9876543210',
        'country_code' => '+91'
    ]
], $merchantToken);

// Step 3: Extract order token for subsequent operations
if (!isset($order['error'])) {
    $orderToken = $order['token'];
    echo "Order created: " . ($order['order_id'] ?? $order['nimbbl_order_id']);
    
    // Use order token for order-related operations
    $payment = $api->payments()->initiatePayment(['order_id' => $order['nimbbl_order_id']], $orderToken);
}
```

### Available API Clients

**Token Types:**
- **Merchant Token** (from `generateToken()`): Used for Transaction Enquiry and Refunds
- **Order Token** (from order creation): Used for all other operations

```php
// Generate merchant token
$request = new NimbblRequest();
$merchantToken = $request->generateToken()['token'];

// Create order and get order token
$order = $api->orders()->createOrder($data, $merchantToken);
$orderToken = $order['token'];

// API Clients (all require token parameter)
$api->orders()              // Orders API (uses Order Token)
$api->payments()            // Payments API (uses Order Token)
$api->paymentLinks()        // Payment Links API (uses Order Token)
$api->addresses()           // Addresses API (uses Order Token)
$api->refunds()             // Refunds API (uses Merchant Token)
$api->transactions()         // Transactions API - Transaction Enquiry (uses Merchant Token)
$api->checkoutUtilities()    // Checkout Utilities API (uses Order Token)
$api->webhook()             // Webhook utilities
```

## 📚 Next Steps

1. **Review the examples** - Each example file contains detailed comments
2. **Check the README** - See `README.md` for API documentation
3. **Integrate into your app** - Copy patterns from examples into your application
4. **Test with real data** - Replace test IDs with actual order/transaction IDs

## 🎯 Framework Integration

These examples work in **any PHP framework**:
- **Laravel**: Use in Controllers, Services, or Commands
- **CodeIgniter**: Use in Controllers or Libraries
- **Symfony**: Use in Controllers or Services
- **Plain PHP**: Use directly as shown

See `example/FRAMEWORK_INTEGRATION.md` for framework-specific integration examples.

## 🔗 Useful Files

- `example/index.php` - Main examples runner
- `example/config.php.example` - Configuration template
- `tests/test-all-apis.php` - Comprehensive test suite
- `README.md` - Full SDK documentation
- `example/HOW_TO_RUN.md` - Detailed examples guide
- `tests/TEST_GUIDE.md` - Testing guide

## ✅ Verification Checklist

Before running examples, make sure:

- [ ] PHP is installed (you have 8.4.14 ✅)
- [ ] Composer is installed (you have 2.8.12 ✅)
- [ ] Dependencies are installed (`composer install`)
- [ ] `example/config.php` exists and has correct credentials
- [ ] `logs/` directory exists and is writable
- [ ] You have valid Nimbbl API credentials

## 🚀 Ready to Go!

You're all set! Start with:

```bash
cd /Users/sandeepyadav/Downloads/sdks/nimbbl-php-sdk
composer install
cp example/config.php.example example/config.php
# Edit config.php with your credentials
php example/index.php
```

Happy coding! 🎉

