# Nimbbl PHP SDK

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Official PHP SDK for integrating with the Nimbbl Payment Gateway API. This SDK provides a simple and intuitive interface to interact with all Nimbbl API endpoints.

##  Installation

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

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use Nimbbl\Api\RestClient\NimbblClient;

$config = [
    'access_key' => 'your_access_key',
    'access_secret' => 'your_access_secret',
    'api_host' => 'https://api.nimbbl.tech',
];

// Initialize the SDK
$api = new NimbblClient(
    $config['access_key'],
    $config['access_secret'],
    rtrim($config['api_host'], '/') . '/api/v3' // API endpoint
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

##  Features

### [OK] Complete API Coverage

- **Orders API** - Create, retrieve orders (uses Order Token)
- **Payments API** - Initiate, complete payments, resend OTP (Order Token); pre-auth **capture / void** (Merchant Token)
- **Payment Links API** - Create, update, manage payment links (uses Order Token)
- **Addresses API** - Manage customer addresses (uses Order Token)
- **Refunds API** - Process refunds (full and partial) (uses Merchant Token)
- **Transactions API** - Transaction enquiry (by order_id, invoice_id, or transaction_id) (uses Merchant Token)
- **Checkout Utilities API** - Payment modes, banks, wallets, EMIs, offers, card BIN, UPI validation (uses Order Token)

###  Token Management

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

###  Security Features

- **Webhook Signature Verification** - Verify webhook authenticity
- **Payment Signature Verification** - Verify payment responses
- **Comprehensive Error Handling** - Detailed exception hierarchy

###  Logging

- **Comprehensive Logging** - Detailed logging of all API requests and responses
- **Multiple Output Channels** - Logs to file, PHP error log, and console
- **Masked Sensitive Data** - Automatically masks API keys and secrets in logs

###  Framework Agnostic

Works seamlessly with:
- Plain PHP
- Laravel
- CodeIgniter
- Symfony
- Any PHP framework

##  Documentation

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

##### Pre-Authorization: Capture / Void

Available when the sub-merchant is configured with `capture_mode=manual` (contact Nimbbl to enable). A pre-authorized checkout yields a transaction in the `authorized` status; you then either **capture** the held funds or **void** the hold. Both use the **merchant token** and are asynchronous — a `pending` status is normal; confirm the outcome via the `capture_success` / `void_success` webhook or Transaction Enquiry.

```php
// Capture an authorized payment (collects the held funds — full amount only)
$capture = $api->payments()->capture([
    'transaction_id' => 'o_xxxx-yyyy',
    'comment' => 'Goods dispatched'   // optional
], $merchantToken);
// => ['capture_status' => 'succeeded'|'pending'|'failed', 'transaction_id' => ..., ...]

// Void an authorized payment (releases the hold without charging — cannot be undone)
$void = $api->payments()->void([
    'transaction_id' => 'o_aaaa-bbbb',
    'comment' => 'Customer cancelled' // optional
], $merchantToken);
// => ['void_status' => 'succeeded'|'pending'|'failed', 'transaction_id' => ..., ...]
```

> Capture is terminal — a transaction can be captured **or** voided, not both. Never fulfil an order on `authorized` alone; capture first. See [example/capture-void-examples.php](example/capture-void-examples.php).

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

### Payment Callback Handling

**Note:** Encrypted payloads are not enabled by default. Please reach out to [support@nimbbl.tech](mailto:support@nimbbl.tech) if you want this functionality.

Always **verify the signature first** with `SignatureVerifier::verifyCallback()` on the **raw** body. It reads the payload's `version` field (source of truth) and picks the handling automatically — v4 signed envelope (signed with `nimbbl_signature`), encrypted v4 (AES-GCM decryption authenticates), or legacy v3 per-field — and unwraps the checkout wrappers (`globalHandleCheckoutResponse` / `globalCloseCheckoutModal`) for you.

**References:**
- [Standard Checkout Integration Guide](https://nimbbl.biz/docs/standard-checkout/completing-integration/) - Understanding callbacks
- [Encryption/Decryption Guide](https://nimbbl.biz/docs/guides/encrypt-decrypt-payload/) - Detailed encryption implementation

```php
use Nimbbl\Api\Common\SignatureVerifier;
use Nimbbl\Api\Common\JsonKeys;

$accessSecret = 'your_access_secret';
$verifier = new SignatureVerifier();

// Standard Checkout callback — pass the RAW body:
//   - redirect/GET mode:  the base64 `response` query param
//   - popup/POST mode:    the raw request body
$rawBody = $_GET['response'] ?? file_get_contents('php://input');

$result = $verifier->verifyCallback($rawBody, $accessSecret);
// => ['success' => bool, 'version' => 'v4'|'legacy', 'event_type' => ..., 'payload' => [...]]

if (!$result['success']) {
    http_response_code(400);   // signature/decryption failed — do NOT trust the callback
    exit;
}
$payload = $result['payload'];
```

**⚠️ The callback is never the source of truth for the result screen — always confirm via Transaction Enquiry (or the webhook).** This matters most for v4, whose callback is *minimal*.

**v4 callback (minimal)** — carries only: `checkout_status`, `reason`, `nimbbl_order_id`, `nimbbl_transaction_id` (may be `null`), `invoice_id`, `retry`, `message`. There is **no** transaction/amount/status block — take `nimbbl_transaction_id` and enquire:

```php
$transactionId = $payload[JsonKeys::NIMBBL_TRANSACTION_ID] ?? null;   // v4: top level
if ($transactionId) {
    $enq  = $api->transactions()->transactionEnquiry([JsonKeys::TRANSACTION_ID => $transactionId]);
    $txn  = $enq['transaction'][0] ?? [];
    $status = $txn['payment_status'] ?? null;   // 'succeeded' | 'pending' | 'failed' — authoritative
    // 'succeeded' -> fulfil ; 'pending' -> still processing ; 'failed' -> failed
}
// Pre-auth: reason === 'payment_authorized' means funds are HELD, not captured — do NOT fulfil; capture first.
```

**Legacy v3 callback (full payload)** — carries the transaction inline; you can read it directly, but still reconcile before fulfilment:

```php
$status        = $payload['transaction']['status'] ?? $payload['status'] ?? null;
$orderId       = $payload['nimbbl_order_id'] ?? $payload['order']['order_id'] ?? null;
$transactionId = $payload['transaction']['transaction_id'] ?? null;
// Treat webhook / Transaction Enquiry as the source of truth before fulfilling the order.
```

> The legacy `verifyCallbackSignature(array $payload, $secret)` (operates on a pre-parsed array) is retained for backward compatibility, but new integrations should use `verifyCallback()` on the raw body so v4 signed envelopes are verified correctly.

### Encryption/Decryption

For direct encryption/decryption operations, use the `Encryption` class:

```php
use Nimbbl\Api\Common\Encryption;

// Initialize encryption with access secret
$encryption = new Encryption($accessSecret);

// Encrypt data for API requests (if needed)
$data = [
    'user_id' => 'user_123',
    'email' => 'user@example.com'
];
$encrypted = $encryption->encrypt($data);

// Decrypt encrypted response
$decrypted = $encryption->decrypt($encrypted, true); // true = return as array
```

### Webhook Handling

Use `SignatureVerifier::verifyWebhook()` on the **raw** request body. Like `verifyCallback()`, it selects v4 signed-envelope vs legacy per-field handling off the `version` field and returns the parsed payload.

```php
use Nimbbl\Api\Common\SignatureVerifier;

$rawBody = file_get_contents('php://input');
$accessSecret = 'your_access_secret';

$verifier = new SignatureVerifier();
$result = $verifier->verifyWebhook($rawBody, $accessSecret);
// => ['success' => bool, 'version' => 'v4'|'legacy', 'event_type' => ..., 'payload' => [...]]

if ($result['success']) {
    $payload       = $result['payload'];
    $eventType     = $result['event_type'] ?? $payload['event_type'] ?? null;
    $orderId       = $payload['nimbbl_order_id'] ?? $payload['order']['order_id'] ?? null;
    $transactionId = $payload['transaction']['transaction_id'] ?? null;

    // Branch on the event type. Pre-auth lifecycle: payment_authorized ->
    // capture_success / void_success (or *_pending / *_failed).
    switch ($eventType) {
        case 'capture_success': /* funds captured — safe to fulfil */ break;
        case 'void_success':    /* hold released — cancel the order */ break;
        // 'payment_success', 'refund_success', ... handle as needed
    }

    // Respond 200 within 15s to acknowledge; reconcile via Transaction Enquiry.
    http_response_code(200);
}
```

> The legacy `verifySignature(array $eventData, $secret)` is retained for backward compatibility; new integrations should use `verifyWebhook()` on the raw body.

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
# Offline unit suite (no credentials, no network) — signatures, webhook/callback, pre-auth E2E
vendor/bin/phpunit

# Live/integration against the configured environment (needs example/config.php)
php tests/Integration/test-all-apis.php
```

See [tests/README.md](tests/README.md) for the full testing guide (offline vs live, pre-auth capture/void, webhook/callback verification).

##  Examples

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

## Project Structure

```text
nimbbl-php-sdk/
├── docs/                 # API and integration documentation
├── example/              # Example integrations and guides
├── logs/                 # SDK log output directory
├── src/
│   ├── Common/           # Shared constants, helpers, encryption, masking
│   ├── Exception/        # SDK exception hierarchy
│   ├── Log/              # Logger and formatting helpers
│   ├── Models/           # Data models
│   ├── RestClient/       # HTTP client, request orchestration, SDK client
│   └── Services/         # API service clients (Orders, Payments, etc.)
├── tests/                # Unit/integration test suites and guides
├── composer.json         # Package metadata and dependencies
└── README.md             # SDK overview and usage
```

## 🔄 Migration from v2 to v3

The SDK now exclusively uses v3 API endpoints. All v2 endpoints and backward compatibility have been removed.

### Key Changes

1. **API Version**: All endpoints now use `v3` by default
2. **No Version Parameter**: `apiVersion` parameter removed from all methods
3. **New API Clients**: Added Addresses, Payments, Payment Links, Checkout Utilities, Transaction Status
4. **Exception Hierarchy**: New exception classes for better error handling
5. **Webhook Handling**: New Webhook class and WebhookEvent model

##  Requirements

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

##  Support

For support, email support@nimbbl.biz

---

**Version**: 4.1.0  
**Last Updated**: July 27, 2026
