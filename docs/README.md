# Nimbbl PHP SDK Documentation

Welcome to the Nimbbl PHP SDK documentation. This directory contains comprehensive documentation for all SDK functionalities.

## Documentation Index

### Core APIs

1. **[Orders API](./ORDERS.md)** - Create and retrieve orders
   - `createOrder()` - Create a new order
   - `getOrderById()` - Get order by ID
   - `getOrderByInvoiceId()` - Get order by invoice ID

2. **[Payments API](./PAYMENTS.md)** - Process payments
   - `initiatePayment()` - Initiate a payment
   - `completePayment()` - Complete a payment
   - `resendPaymentOtp()` - Resend OTP for payment

3. **[Payment Links API](./PAYMENT_LINKS.md)** - Manage payment links
   - `createPaymentLink()` - Create a payment link
   - `updatePaymentLink()` - Update a payment link
   - `enquiryPaymentLink()` - Get payment link details
   - `performPaymentLinkActions()` - Perform actions on payment link

4. **[Addresses API](./ADDRESSES.md)** - Manage customer addresses
   - `listAddresses()` - List addresses
   - `createAddress()` - Create an address
   - `updateAddress()` - Update an address
   - `deleteAddress()` - Delete an address
   - `importAddresses()` - Import addresses
   - `checkAddressEligibility()` - Check address eligibility
   - `linkAddressWithOrder()` - Link address with order
   - `getAddressById()` - Get address by ID

5. **[Refunds API](./REFUNDS.md)** - Process refunds
   - `initiateRefund()` - Initiate a refund (full or partial)

6. **[Transactions API](./TRANSACTIONS.md)** - Transaction enquiry
   - `transactionEnquiry()` - Get transaction status

7. **[Checkout Utilities API](./CHECKOUT_UTILITIES.md)** - Checkout helpers
   - `listPaymentModes()` - Get available payment modes
   - `listBanks()` - Get available banks
   - `listWallets()` - Get available wallets
   - `listEMIs()` - Get available EMI options
   - `getOffers()` - Get available offers
   - `getCardBinData()` - Get card BIN data
   - `validateUpiVpa()` - Validate UPI ID
   - `getUpiAppDetails()` - Get UPI app details

### Authentication & Security

8. **[Authentication API](./AUTHENTICATION.md)** - Token management
   - `generateToken()` - Generate authentication token
   - `refreshToken()` - Refresh authentication token

9. **[Webhooks](./WEBHOOKS.md)** - Webhook handling
   - `verifyWebhook()` - Verify webhook signature
   - `parseWebhookEvent()` - Parse webhook payload
   - `verifyAndParse()` - Verify and parse in one call
   - `getSignatureFromHeaders()` - Get signature from headers
   - `getPayloadFromInput()` - Get payload from input stream

10. **[Encryption](./ENCRYPTION.md)** - Data encryption/decryption
    - `encrypt()` - Encrypt data
    - `decrypt()` - Decrypt data

### Utilities

11. **[Exception Handling](./EXCEPTIONS.md)** - Error handling patterns
    - Exception hierarchy
    - Error codes and handling
    - Retry logic examples

12. **[Logging](./LOGGING.md)** - Logging configuration
    - Log levels and configuration
    - Log file management
    - Debugging with logs

## Quick Start

### Installation

```bash
composer require nimbbl/nimbbl-sdk
```

### Basic Usage

```php
<?php
require_once 'vendor/autoload.php';

use Nimbbl\Api\RestClient\NimbblClient;

// Initialize SDK
$api = new NimbblClient(
    'your_access_key',
    'your_access_secret',
    'https://api.nimbbl.tech/api/',
    'v3'
);
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

// Create order
$order = $api->orders()->createOrder([
    'invoice_id' => 'INV-123',
    'amount_before_tax' => 100.00,
    'tax' => 18.00,
    'total_amount' => 118.00,
    'currency' => 'INR',
    'user' => [
        'email' => 'customer@example.com',
        'first_name' => 'John',
        'mobile_number' => '9876543210',
        'country_code' => '+91'
    ]
], $merchantToken);

// Extract order token
$orderToken = $order['token'];

// Initiate payment
$payment = $api->payments()->initiatePayment([
    'order_id' => $order['order_id'],
    'payment_mode_code' => 'net_banking',
    'bank_code' => 'HDFC',
    'callback_url' => 'https://yourwebsite.com/callback'
], $orderToken);
```

## Token Management

The SDK uses two types of tokens:

1. **Merchant Token** (from `generateToken()`)
   - Used for: Transaction Enquiry, Refunds
   - Generated from: `access_key` and `access_secret`

2. **Order Token** (from order creation response)
   - Used for: Orders, Payments, Payment Links, Addresses, Checkout Utilities
   - Obtained from: `$order['token']`

## Common Patterns

### Complete Payment Flow

```php
// 1. Generate merchant token
$merchantToken = $api->auth()->generateToken()['token'];

// 2. Create order
$order = $api->orders()->createOrder($orderData, $merchantToken);
$orderToken = $order['token'];

// 3. Initiate payment
$payment = $api->payments()->initiatePayment($paymentData, $orderToken);

// 4. Handle payment status (via webhook or transaction enquiry)
```

### Webhook Handling

```php
```

## Examples

See the `example/` directory for complete working examples:

- `create-order.php` - Order creation examples
- `payments-examples.php` - Payment processing examples
- `webhook-handler.php` - Webhook handling example
- `cli.php` - Interactive CLI for testing

## API Reference

For detailed API reference, visit:
- https://nimbbl.biz/docs/api-reference/

## Support

- **Documentation**: https://nimbbl.biz/docs/
- **Support Email**: support@nimbbl.tech
- **GitHub Issues**: (if applicable)

## License

See LICENSE file for details.



