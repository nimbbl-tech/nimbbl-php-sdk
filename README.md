# Nimbbl PHP SDK

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Official PHP SDK for integrating with the Nimbbl Payment Gateway API. This SDK provides a simple and intuitive interface to interact with all Nimbbl API endpoints.

## 📦 Installation

### Using Composer (Recommended)

```bash
composer require nimbbl/nimbbl-sdk
```

### Manual Installation

1. Download the SDK
2. Include the autoloader:

```php
require_once 'path/to/nimbbl-php-sdk/vendor/autoload.php';
```

## 🚀 Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use Nimbbl\Api\Api;

// Initialize the SDK
$api = new Api(
    'your_access_key',
    'your_access_secret',
    'https://api.nimbbl.tech/api/', // API base URL
    'v3' // API version
);

// Step 1: Generate merchant token (for Transaction Enquiry/Refunds)
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

// Step 2: Create an order (uses merchant token initially)
$order = $api->orders()->createOrder([
    'invoice_id' => 'INV-12345',
    'amount_before_tax' => 3.60,
    'tax' => 0.40,
    'total_amount' => 4.00,
    'currency' => 'INR',
    'user' => [
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_number' => '9876543210',
        'country_code' => '+91'
    ]
], $merchantToken);

// Step 3: Extract order token from response
if (!isset($order['error'])) {
    $orderToken = $order['token']; // Use this for subsequent order-related operations
    echo "Order created: " . ($order['order_id'] ?? $order['nimbbl_order_id']);
}
```

## 📚 Features

### ✅ Complete API Coverage

- **Orders API** - Create, retrieve orders (uses Order Token)
- **Payments API** - Initiate, complete payments, resend OTP (uses Order Token)
- **Payment Links API** - Create, update, manage payment links (uses Order Token)
- **Addresses API** - Manage customer addresses (uses Order Token)
- **Refunds API** - Process refunds (full and partial) (uses Merchant Token)
- **Transactions API** - Transaction enquiry (by order_id, invoice_id, or transaction_id) (uses Merchant Token)
- **Checkout Utilities API** - Payment modes, banks, wallets, EMIs, offers, card BIN, UPI validation (uses Order Token)

### 🔑 Token Management

The SDK uses two types of tokens:

1. **Merchant Token** (from `generateToken()`)
   - Used for: Transaction Enquiry, Refund operations
   - Generated from: `access_key` and `access_secret`

2. **Order Token** (from order creation response)
   - Used for: All other operations (Orders, Payments, Payment Links, Addresses, Checkout Utilities)
   - Obtained from: Order creation response (`$order['token']`)

**Token Usage Pattern:**
```php
// 1. Generate merchant token
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

// 2. Create order (uses merchant token initially)
$order = $api->orders()->createOrder($orderData, $merchantToken);
$orderToken = $order['token']; // Extract order token

// 3. Use order token for order-related operations
$payment = $api->payments()->initiatePayment($paymentData, $orderToken);

// 4. Use merchant token for admin operations
$enquiry = $api->transactions()->transactionEnquiry($data, $merchantToken);
$refund = $api->refunds()->initiateRefund($refundData, $merchantToken);
```

### 🔐 Security Features

- **Webhook Signature Verification** - Verify webhook authenticity
- **Payment Signature Verification** - Verify payment responses
- **Comprehensive Error Handling** - Detailed exception hierarchy

### 📊 Logging

- **Comprehensive Logging** - Detailed logging of all API requests and responses
- **Multiple Output Channels** - Logs to file, PHP error log, and console
- **Masked Sensitive Data** - Automatically masks API keys and secrets in logs

### 🎯 Framework Agnostic

Works seamlessly with:
- Plain PHP
- Laravel
- CodeIgniter
- Symfony
- Any PHP framework

## 📖 Documentation

### Comprehensive Documentation

Complete documentation for all SDK functionalities is available in the [`docs/`](./docs/) directory:

- **[Orders API](./docs/ORDERS.md)** - Create and retrieve orders
- **[Payments API](./docs/PAYMENTS.md)** - Process payments
- **[Payment Links API](./docs/PAYMENT_LINKS.md)** - Manage payment links
- **[Addresses API](./docs/ADDRESSES.md)** - Manage customer addresses
- **[Refunds API](./docs/REFUNDS.md)** - Process refunds
- **[Transactions API](./docs/TRANSACTIONS.md)** - Transaction enquiry
- **[Checkout Utilities API](./docs/CHECKOUT_UTILITIES.md)** - Checkout helpers
- **[Authentication API](./docs/AUTHENTICATION.md)** - Token management
- **[Webhooks](./docs/WEBHOOKS.md)** - Webhook handling
- **[Encryption](./docs/ENCRYPTION.md)** - Data encryption/decryption
- **[Exception Handling](./docs/EXCEPTIONS.md)** - Error handling patterns
- **[Logging](./docs/LOGGING.md)** - Logging configuration

See [Documentation Index](./docs/README.md) for a complete overview.

### API Clients

#### Orders API

**Note:** Orders API uses **Order Token** (obtained from order creation response).

```php
// Generate merchant token first
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

// Create order (uses merchant token initially)
$order = $api->orders()->createOrder([
    'invoice_id' => 'INV-123',
    'amount_before_tax' => 3.60,
    'tax' => 0.40,
    'total_amount' => 4.00,
    'currency' => 'INR',
    'user' => [
        'email' => 'customer@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_number' => '9876543210',
        'country_code' => '+91'
    ]
], $merchantToken);

// Extract order token from response
$orderToken = $order['token'];

// Retrieve order (uses order token)
$order = $api->orders()->getOrderById('order_id', $orderToken);
```

#### Payments API

**Note:** Payments API uses **Order Token** (obtained from order creation response).

```php
// Initiate payment (uses order token)
$payment = $api->payments()->initiatePayment([
    'order_id' => 'order_id',
    'payment_mode_code' => 'net_banking',
    'bank_code' => 'axis',
    'callback_url' => 'https://your-callback-url.com'
], $orderToken);

// Complete payment (for Pay Later with OTP)
$payment = $api->payments()->completePayment([
    'transaction_id' => 'transaction_id',
    'payment_flow' => 'otp',
    'otp' => '123456'
], $orderToken);

// Resend OTP
$api->payments()->resendPaymentOtp([
    'transaction_id' => 'transaction_id'
], $orderToken);
```

#### Payment Links API

```php
// Create payment link
$link = $api->paymentLinks()->createPaymentLink([
    'invoice_id' => 'INV-123',
    'total_amount' => 1000,
    'currency' => 'INR',
    'user' => [
        'email' => 'customer@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_number' => '9876543210',
        'country_code' => '+91'
    ],
    'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
]);

// Enquiry payment link
$link = $api->paymentLinks()->enquiryPaymentLink([
    'payment_link_id' => 'link_id'
]);

// Update payment link
$link = $api->paymentLinks()->updatePaymentLink([
    'payment_link_id' => 'link_id',
    'total_amount' => 1500
]);

// Payment link actions
$api->paymentLinks()->performPaymentLinkActions([
    'payment_link_id' => 'link_id',
    'action' => 'cancel'
]);
```

#### Addresses API

```php
// List addresses
$addresses = $api->addresses()->listAddresses(['user_id' => 'user_id']);

// Create address
$address = $api->addresses()->createAddress([
    'addresses' => [
        [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_1' => '123 Main St',
            'area' => 'Andheri',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'address_type' => 'home'
        ]
    ],
    'user_id' => 'user_id' // Optional
]);

// Update address
$address = $api->addresses()->updateAddress('address_id', [
    'line1' => '456 New St'
]);

// Delete address
$api->addresses()->deleteAddress('address_id');
```

#### Refunds API

**Note:** Refunds API uses **Merchant Token** (from `generateToken()`).

```php
// Generate merchant token
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

// Full refund by transaction_id
$refund = $api->refunds()->initiateRefund([
    'transaction_id' => 'transaction_id'
], $merchantToken);

// Partial refund with comment
$refund = $api->refunds()->initiateRefund([
    'transaction_id' => 'transaction_id',
    'refund_amount' => 500, // Optional for partial refund
    'comment' => 'Refund reason'
], $merchantToken);

// Refund by invoice_id
$refund = $api->refunds()->initiateRefund([
    'invoice_id' => 'invoice_id',
    'refund_amount' => 250,
    'comment' => 'Refund reason'
], $merchantToken);

// Refund with idempotency (refund_request_id)
$refund = $api->refunds()->initiateRefund([
    'transaction_id' => 'transaction_id',
    'refund_request_id' => 'UNIQUE_REFUND_ID_' . time(),
    'refund_amount' => 100
], $merchantToken);

// Refund with order line items
$refund = $api->refunds()->initiateRefund([
    'transaction_id' => 'transaction_id',
    'order_line_items' => [
        [
            'sku_id' => 'SKU-001',
            'serial_numbers' => ['SN-001', 'SN-002'] // Optional
        ]
    ],
    'comment' => 'Refund specific items'
], $merchantToken);
```

#### Transactions API (Transaction Enquiry)

**Note:** Transaction Enquiry uses **Merchant Token** (from `generateToken()`).

```php
// Generate merchant token
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

// Enquiry by order ID
$status = $api->transactions()->transactionEnquiry([
    'order_id' => 'order_id'
], $merchantToken);

// Enquiry by invoice ID
$status = $api->transactions()->transactionEnquiry([
    'invoice_id' => 'invoice_id'
], $merchantToken);

// Enquiry by transaction ID
$status = $api->transactions()->transactionEnquiry([
    'transaction_id' => 'transaction_id'
], $merchantToken);
```

#### Checkout Utilities API

**Note:** Checkout Utilities API uses **Order Token** (obtained from order creation response).

```php
// List payment modes
$modes = $api->checkoutUtilities()->listPaymentModes(['order_id' => 'order_id'], $orderToken);

// List banks
$banks = $api->checkoutUtilities()->listBanks(['order_id' => 'order_id'], $orderToken);

// List wallets
$wallets = $api->checkoutUtilities()->listWallets(['order_id' => 'order_id'], $orderToken);

// List EMIs
$emis = $api->checkoutUtilities()->listEMIs(['order_id' => 'order_id'], $orderToken);

// Get offers
$offers = $api->checkoutUtilities()->getOffers([
    'order_id' => 'order_id',
    'payment_mode_code' => 'card'
], $orderToken);

// Get card BIN data
$binData = $api->checkoutUtilities()->getCardBinData([
    'card_bin' => '411111',  // Required: first 6 digits of card number
    'order_id' => 'order_id' // Optional: order for which to validate bin details
], $orderToken);

// Validate UPI VPA
$validation = $api->checkoutUtilities()->validateUpiVpa(['upi_id' => 'user@paytm'], $orderToken);
```

### Encryption/Decryption

**Note:** Encrypted payloads are not enabled by default. Please reach out to [support@nimbbl.tech](mailto:support@nimbbl.tech) if you want this functionality.

The encryption utility is used to decrypt responses from Standard Checkout integration. When your client forwards the checkout response to your server, it may include an `encrypted_response` field that needs to be decrypted.

**References:**
- [Standard Checkout Integration Guide](https://nimbbl.biz/docs/standard-checkout/completing-integration/) - Understanding encrypted responses
- [Encryption/Decryption Guide](https://nimbbl.biz/docs/guides/encrypt-decrypt-payload/) - Detailed encryption implementation

```php
use Nimbbl\Api\Encryption;

// Initialize encryption with access secret
$encryption = new Encryption($accessSecret);

// Example 1: Decrypt Standard Checkout response
// When your client forwards the checkout response, it may contain encrypted_response
$checkoutResponse = [
    'event_type' => 'globalHandleCheckoutResponse',
    'payload' => [
        'encrypted_response' => '3164351ca6195e9871cca9de3117cb8f...' // Encrypted response from client
    ]
];

if (isset($checkoutResponse['payload']['encrypted_response'])) {
    // Decrypt the encrypted response
    $decrypted = $encryption->decrypt($checkoutResponse['payload']['encrypted_response'], true);
    
    // Now you can access the decrypted response
    $status = $decrypted['status']; // 'success', 'failed', or 'pending'
    $orderId = $decrypted['nimbbl_order_id'];
    $transactionId = $decrypted['nimbbl_transaction_id'];
    $signature = $decrypted['nimbbl_signature'];
    
    // Validate the signature before processing
    // ... validation logic
}

// Example 2: Encrypt data for API requests (if needed)
$data = [
    'user_id' => 'user_123',
    'email' => 'user@example.com'
];
$encrypted = $encryption->encrypt($data);

// Send encrypted payload to API in 'encrypted_payload' field
$order = $api->orders()->createOrder([
    'encrypted_payload' => $encrypted,
    // ... other fields
], $token);
```

### Webhook Handling

```php
use Nimbbl\Api\Api;
use Nimbbl\Api\Webhook;
use Nimbbl\Api\Model\WebhookEvent;

// Initialize API and get webhook handler
$api = new Api($accessKey, $accessSecret);
$webhook = $api->webhook();

// Get webhook payload
$payload = $webhook->getPayloadFromInput();

// Get signature from headers
$headers = getallheaders();
$signature = $webhook->getSignatureFromHeaders($headers);

// Verify webhook
$eventData = $webhook->verifyAndParse($payload, $signature, $secret);

if ($eventData) {
    $event = new WebhookEvent($eventData);
    
    if ($event->isPaymentCaptured()) {
        // Handle payment success
    } elseif ($event->isPaymentFailed()) {
        // Handle payment failure
    }
}
```

### Error Handling

```php
use Nimbbl\Api\Exception\NimbblException;
use Nimbbl\Api\Exception\AuthenticationException;
use Nimbbl\Api\Exception\BadRequestException;
use Nimbbl\Api\Exception\NotFoundException;

try {
    $order = $api->orders()->createOrder([...]);
} catch (AuthenticationException $e) {
    // Handle 401 errors
    echo "Authentication failed: " . $e->getMessage();
} catch (BadRequestException $e) {
    // Handle 400/422 errors
    echo "Invalid request: " . $e->getMessage();
} catch (NotFoundException $e) {
    // Handle 404 errors
    echo "Resource not found: " . $e->getMessage();
} catch (NimbblException $e) {
    // Handle other Nimbbl exceptions
    echo "Error: " . $e->getMessage();
    echo "Error Code: " . $e->getErrorCode();
    echo "Request ID: " . $e->getRequestId();
}
```

## 🧪 Testing

### Run Tests

```bash
# Run all tests
php tests/run-all-tests.php

# Run specific test
php tests/OrderTest.php
php tests/PaymentTest.php
```

See [tests/README.md](tests/README.md) for more details.

## 📝 Examples

Comprehensive examples are available in the `example/` directory:

- `index.php` - Main examples (all-in-one)
- `order-examples.php` - Create and retrieve orders
- `payments-examples.php` - Payment processing examples
- `payment-links-examples.php` - Payment links examples
- `addresses-examples.php` - Addresses API examples
- `checkout-utilities-examples.php` - Checkout utilities examples
- `refund-examples.php` - Refund examples
- `transaction-status.php` - Transaction enquiry examples
- `webhook-handler.php` - Webhook handling
- `exception-handling-examples.php` - Error handling examples
- `encryption-examples.php` - Encryption/Decryption examples

See [example/README.md](example/README.md) for more details.

## 🔄 Migration from v2 to v3

The SDK now exclusively uses v3 API endpoints. All v2 endpoints and backward compatibility have been removed.

### Key Changes

1. **API Version**: All endpoints now use `v3` by default
2. **No Version Parameter**: `apiVersion` parameter removed from all methods
3. **New API Clients**: Added Addresses, Payments, Payment Links, Checkout Utilities, Transaction Status
4. **Exception Hierarchy**: New exception classes for better error handling
5. **Webhook Handling**: New Webhook class and WebhookEvent model

### Breaking Changes

- `apiVersion` parameter removed from all API client methods
- `NimbblSegment` class removed (was deprecated, no longer needed)
- `retrieveMany()` method removed from base entity class
- Users API removed (not an official public API)
- Deprecated methods removed:
  - `Order::create()` → use `createOrder()`
  - `Order::retrieveOne()` → use `getOrderById()`
  - `Order::retrieveByInvoiceId()` → use `getOrderByInvoiceId()`
  - `Order::edit()` → use `updateOrder()`
  - `Address::retrieveOne()` → use `getAddressById()`
- Error handling now uses new exception hierarchy (backward compatible with `NimbblError`)

## 📋 Requirements

- PHP >= 7.4
- JSON extension
- cURL extension (for HTTP requests)

## 🔗 Resources

- [API Documentation](https://nimbbl.biz/docs/api-reference/introduction/)
- [Nimbbl Dashboard](https://dashboard.nimbbl.tech/)
- [Support](https://nimbbl.biz/support/)

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📞 Support

For support, email support@nimbbl.tech or visit [https://nimbbl.biz/support/](https://nimbbl.biz/support/)

## 🗺️ Roadmap

- [x] API Standardization (v3 only)
- [x] Event Logging System
- [x] Webhook Handling
- [x] Error Handling & Structure
- [x] Testing & Documentation
- [ ] Sample Applications (Phase 6)

---

**Version**: 3.6.9  
**Last Updated**: 2024
