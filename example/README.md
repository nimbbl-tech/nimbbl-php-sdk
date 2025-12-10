# Nimbbl PHP SDK - Sample Application

This directory contains comprehensive examples demonstrating how to use the Nimbbl PHP SDK.

**Requires PHP 7.4+**

## 🎯 Framework-Agnostic Design

**Important:** These plain PHP examples work in **ALL PHP frameworks**!

✅ **Works directly in:**
- Laravel
- CodeIgniter
- Symfony
- Yii
- Slim
- Plain PHP
- **Any PHP framework**

The SDK is 100% framework-agnostic. The examples here demonstrate core SDK usage patterns that work everywhere. You can use these examples as-is in any framework, or adapt them to follow framework-specific best practices (Service Providers, Dependency Injection, etc.).

## 📁 Structure

```
example/
├── README.md                 # This file
├── config.php.example        # Configuration template
├── index.php                 # Main entry point with examples
├── create-order.php          # Order creation example
├── get-order.php             # Order retrieval examples
├── refund-examples.php       # Refund processing examples
├── transaction-status.php    # Transaction status enquiry
├── webhook-handler.php       # Webhook signature verification example
├── addresses-examples.php    # Addresses API examples (NEW)
├── payments-examples.php     # Payments API examples (NEW)
├── payment-links-examples.php # Payment Links API examples (NEW)
├── checkout-utilities-examples.php # Checkout Utilities API examples (NEW)
├── exception-handling-examples.php # Exception handling examples (NEW)
└── utils/
    └── helpers.php           # Helper functions
```

## 🚀 Quick Start

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

# Run specific examples
php example/create-order.php
php example/get-order.php
php example/refund-examples.php
php example/transaction-status.php
php example/addresses-examples.php
php example/payments-examples.php
php example/payment-links-examples.php
php example/checkout-utilities-examples.php
php example/exception-handling-examples.php

# Run API client tests (from tests directory)
php tests/AddressTest.php
php tests/PaymentTest.php
php tests/PaymentLinkTest.php
php tests/CheckoutUtilitiesTest.php
php tests/TransactionStatusTest.php

# Run all tests at once
php tests/run-all-tests.php
```

## 📝 Configuration

Edit `config.php` with your Nimbbl credentials:

```php
<?php
return [
    'access_key' => 'your_access_key_here',
    'access_secret' => 'your_access_secret_here',
    'api_endpoint' => 'https://api.nimbbl.tech/api/v3',  // Production
    // 'api_endpoint' => 'https://apipp.nimbbl.tech/api/v3',  // UAT/Sandbox
    // Note: Webhook verification uses access_secret automatically
];
```

## 📋 Complete Example Coverage

This sample application includes comprehensive examples for:

### Core APIs
- ✅ **Orders API** - Create, retrieve, update orders
- ✅ **Payments API** - Initiate, complete payments, resend OTP
- ✅ **Payment Links API** - Create, update, manage payment links
- ✅ **Addresses API** - Manage customer addresses
- ✅ **Refunds API** - Process refunds
- ✅ **Transactions API** - Transaction enquiry (by order_id, invoice_id, or transaction_id)
- ✅ **Checkout Utilities API** - Payment modes, banks, wallets, EMIs, offers

### Advanced Features
- ✅ **Webhook Handling** - Signature verification and event processing
- ✅ **Event Logging** - Automatic and custom event logging
- ✅ **Error Handling** - Comprehensive exception handling examples

## 📚 Examples

### Order Creation

**Note:** Orders API uses **Order Token** (obtained from order creation response). Initial order creation uses **Merchant Token**.

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

use Nimbbl\Api\NimbblApi;
use Nimbbl\Api\NimbblRequest;

$api = new NimbblApi($config['access_key'], $config['access_secret'], $config['api_endpoint']);

// Generate merchant token
$request = new NimbblRequest();
$merchantToken = $request->generateToken()['token'];

$orderData = [
    'total_amount' => 100.00,
    'currency' => 'INR',
    'invoice_id' => 'INV-' . time(),
    'user' => [
        'email' => 'customer@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'country_code' => '+91',
        'mobile_number' => '9876543210',
    ],
    'shipping_address' => [
        'address_1' => '123 Main Street',
        'area' => 'Downtown',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
        'address_type' => 'home',
    ],
];

// Create order with merchant token
$order = $api->orders()->createOrder($orderData, $merchantToken);

// Extract order token for subsequent operations
if (!isset($order['error'])) {
    $orderToken = $order['token'];
    print_r($order);
}
```

### Order Retrieval

**Note:** Uses **Order Token** (obtained from order creation response).

```php
// Get order by order_id (uses order token)
$order = $api->orders()->getOrderById('o_XXXXXXXXXX', $orderToken);

// Get order by invoice_id (uses order token)
$order = $api->orders()->getOrderByInvoiceId('INV-123456', $orderToken);
```

### Payment Processing

**Note:** Payments API uses **Order Token** (obtained from order creation response).

```php
// Initiate payment (uses order token)
$payment = $api->payments()->initiatePayment([
    'order_id' => 'o_XXXXXXXXXX',
    'payment_mode_code' => 'net_banking',
    'bank_code' => 'axis',
    'callback_url' => 'https://your-callback-url.com'
], $orderToken);

// Complete payment (for OTP flow) - uses order token
$payment = $api->payments()->completePayment([
    'transaction_id' => 'transaction_id',
    'payment_flow' => 'otp',
    'otp' => '123456'
], $orderToken);

// Resend OTP - uses order token
$api->payments()->resendPaymentOtp([
    'transaction_id' => 'transaction_id'
], $orderToken);
```

### Refund Processing

**Note:** Refunds API uses **Merchant Token** (from `generateToken()`).

```php
// Generate merchant token
$request = new NimbblRequest();
$merchantToken = $request->generateToken()['token'];

// Full refund (uses merchant token)
$refund = $api->refunds()->initiateRefund([
    'transaction_id' => 't_XXXXXXXXXX',
], $merchantToken);

// Partial refund (uses merchant token)
$refund = $api->refunds()->initiateRefund([
    'transaction_id' => 't_XXXXXXXXXX',
    'refund_amount' => 50.00,
    'comment' => 'Partial refund'
], $merchantToken);
```

### Webhook Handling

```php
// See webhook-handler.php for complete example
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_NIMBBL_SIGNATURE'] ?? '';

$webhook = new \Nimbbl\Api\Webhook();
$event = $webhook->verifyAndParse($payload, $signature, $config['access_secret']);

if ($isValid) {
    $event = json_decode($payload, true);
    // Process webhook event
}
```

## 🔐 Security Notes

1. **Never commit `config.php`** - It contains sensitive credentials
2. **Use environment variables** in production
3. **Verify webhook signatures** before processing
4. **Validate all user input** before sending to API
5. **Use HTTPS** for all API communications

## 📖 Documentation

- [Nimbbl API Documentation](https://nimbbl.biz/docs/api-reference/introduction/)
- [SDK Implementation Plan](../../docs/implementation-plans/php-sdk-implementation-plan.md)

## 🐛 Troubleshooting

### Common Issues

1. **Autoload errors**: Run `composer install` from SDK root
2. **API errors**: Check your credentials in `config.php`
3. **Webhook verification fails**: Ensure access_secret is correct (used for webhook verification)

## 📖 Using the SDK in Your Application

For detailed instructions on using the SDK in your own application, see:
- **[USING_SDK.md](./USING_SDK.md)** - Complete guide for integrating the SDK in Laravel, CodeIgniter, Symfony, and plain PHP

## 📦 Publishing the SDK

For instructions on publishing the SDK to Packagist, see:
- **[../PUBLISHING.md](../PUBLISHING.md)** - Complete guide for publishing to Packagist

## 📞 Support

For issues or questions:
- Check the [API Documentation](https://nimbbl.biz/docs/api-reference/introduction/)
- Review the [SDK Documentation](../README.md)
- Review the [Using SDK Guide](./USING_SDK.md)
- Contact support: support@nimbbl.biz

