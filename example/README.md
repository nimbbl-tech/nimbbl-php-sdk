# Nimbbl PHP SDK - Sample Application

This directory contains comprehensive examples demonstrating how to use the Nimbbl PHP SDK.

**Requires PHP 7.4+**

##  Framework-Agnostic Design

**Important:** These plain PHP examples work in **ALL PHP frameworks**!

[OK] **Works directly in:**
- Laravel
- CodeIgniter
- Symfony
- Yii
- Slim
- Plain PHP
- **Any PHP framework**

The SDK is 100% framework-agnostic. The examples here demonstrate core SDK usage patterns that work everywhere. You can use these examples as-is in any framework, or adapt them to follow framework-specific best practices (Service Providers, Dependency Injection, etc.).

##  Structure

```
example/
├── README.md                 # This file
├── HOW_TO_RUN.md            # Detailed running instructions
├── USING_SDK.md             # SDK usage guide
├── config.php.example        # Configuration template
├── cli.php                   # Interactive CLI menu (main entry point)
├── index.php                 # Examples overview
├── generate-token.php        # Token generation example
├── order-examples.php         # Order management examples
├── payments-examples.php      # Payment processing examples
├── payment-links-examples.php # Payment link examples
├── addresses-examples.php    # Address management examples
├── refund-examples.php       # Refund processing examples
├── transaction-status.php   # Transaction status enquiry
├── checkout-utilities-examples.php # Checkout utilities examples
├── encryption-examples.php   # Encryption/decryption examples
├── webhook-handler.php       # Webhook handler example
├── exception-handling-examples.php # Exception handling examples
└── utils/
    ├── cli_output.php        # CLI output utilities
    └── helpers.php           # Helper functions
```

##  Quick Start

### 1. Setup Configuration

```bash
# Copy the example config file
cp config.php.example config.php

# Edit config.php with your credentials
nano config.php
```

### 2. Install Dependencies

```bash
# From the SDK root directory
composer install
```

### 3. Run Examples

```bash
# Run main examples
php example/index.php

# Run interactive CLI menu (recommended)
php example/cli.php

# Or run specific examples
php example/generate-token.php
php example/order-examples.php
php example/payments-examples.php
php example/refund-examples.php
php example/transaction-status.php
php example/addresses-examples.php
php example/payment-links-examples.php
php example/checkout-utilities-examples.php
php example/exception-handling-examples.php

# Run PHPUnit unit/offline-safe suite (recommended)
vendor/bin/phpunit -c phpunit.xml.dist

# Optional: run integration scripts (require valid credentials + API/network access)
php tests/AddressTest.php
php tests/PaymentTest.php
php tests/PaymentLinkTest.php
php tests/CheckoutUtilitiesTest.php
php tests/TransactionStatusTest.php

# Optional integration batch script
php tests/run-all-tests.php
```

##  Configuration

Edit `config.php` with your Nimbbl credentials:

```php
<?php
return [
    'access_key' => getenv('NIMBBL_ACCESS_KEY') ?: 'your_access_key_here',
    'access_secret' => getenv('NIMBBL_ACCESS_SECRET') ?: 'your_access_secret_here',
    'api_host' => 'https://api.nimbbl.tech',  // Production
    // 'api_host' => 'https://apipp.nimbbl.tech',  // UAT/Sandbox
    // Note: Webhook verification uses access_secret automatically
];
```

##  Complete Example Coverage

This sample application includes comprehensive examples for:

### Core APIs
- [OK] **Orders API** - Create, retrieve, update orders
- [OK] **Payments API** - Initiate, complete payments, resend OTP
- [OK] **Payment Links API** - Create, update, manage payment links
- [OK] **Addresses API** - Manage customer addresses
- [OK] **Refunds API** - Process refunds
- [OK] **Transactions API** - Transaction enquiry (by order_id, invoice_id, or transaction_id)
- [OK] **Checkout Utilities API** - Payment modes, banks, wallets, EMIs, offers

### Advanced Features
- [OK] **Webhook Handling** - Signature verification and event processing
- [OK] **Event Logging** - Automatic and custom event logging
- [OK] **Error Handling** - Comprehensive exception handling examples

##  Examples

### Quick Example: Order Creation

**Note:** Orders API uses **Order Token** (obtained from order creation response). Initial order creation uses **Merchant Token**.

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';

use Nimbbl\Api\RestClient\NimbblClient;

// Initialize API
$config = loadConfig();
$api = initApi($config);

// Generate merchant token
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

// Create order
$order = $api->orders()->createOrder([
    'invoice_id' => 'INV-' . time(),
    'total_amount' => 100.00,
    'amount_before_tax' => 90.00,
    'tax' => 10.00,
    'currency' => 'INR',
    'user' => [
        'email' => 'customer@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'country_code' => '+91',
        'mobile_number' => '9876543210',
    ],
], $merchantToken);

// Extract order token for subsequent operations
if (!isset($order['error'])) {
    $orderToken = $order['token'];
    $orderId = $order['order_id'] ?? $order['nimbbl_order_id'];
    echo "Order created! Order ID: {$orderId}\n";
    echo "Order Token: {$orderToken}\n";
}
```

For complete examples, see:
- `order-examples.php` - Order creation and retrieval
- `payments-examples.php` - Payment processing
- `payment-links-examples.php` - Payment link management
- `addresses-examples.php` - Address management
- `refund-examples.php` - Refund processing
- `webhook-handler.php` - Webhook handling
- `encryption-examples.php` - Encryption/decryption

All examples are self-contained and can be run standalone or called from `cli.php`.

##  Security Notes

1. **Never commit `config.php`** - It contains sensitive credentials
2. **Use environment variables** in production
3. **Verify webhook signatures** before processing
4. **Validate all user input** before sending to API
5. **Use HTTPS** for all API communications

##  Documentation

- [Nimbbl API Documentation](https://nimbbl.biz/docs/api-reference/introduction/)
- [SDK Implementation Plan](../../docs/implementation-plans/php-sdk-implementation-plan.md)

##  Troubleshooting

### Common Issues

1. **Autoload errors**: Run `composer install` from SDK root
2. **API errors**: Check your credentials in `config.php`
3. **Webhook verification fails**: Ensure access_secret is correct (used for webhook verification)

##  Using the SDK in Your Application

For detailed instructions on using the SDK in your own application, see:
- **[USING_SDK.md](./USING_SDK.md)** - Complete guide for integrating the SDK in Laravel, CodeIgniter, Symfony, and plain PHP

##  Publishing the SDK

For instructions on publishing the SDK to Packagist, see:
- **[../PUBLISHING.md](../PUBLISHING.md)** - Complete guide for publishing to Packagist

##  Support

For issues or questions:
- Check the [API Documentation](https://nimbbl.biz/docs/api-reference/introduction/)
- Review the [SDK Documentation](../README.md)
- Review the [Using SDK Guide](./USING_SDK.md)
- Contact support: support@nimbbl.biz

