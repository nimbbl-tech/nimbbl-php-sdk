# Payments API Documentation

The Payments API allows you to initiate payments, complete payments, and resend OTP for payment verification.

## Overview

- **Token Type**: Order Token (obtained from order creation)
- **Base URL**: `/api/v3/initiate-payment`, `/api/v3/payment`, `/api/v3/resend-otp`
- **API Documentation**: 
  - https://nimbbl.biz/docs/api-reference/initiate-a-payment-v-3/
  - https://nimbbl.biz/docs/api-reference/complete-payment-v-3/

## Methods

### 1. initiatePayment

Initiate a payment for an order.

**Method Signature:**
```php
public function initiatePayment($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Payment initiation attributes:
  - `order_id` (string, required): Order ID from order creation
  - `payment_mode_code` (string, required): Payment mode (e.g., 'net_banking', 'credit_card', 'debit_card', 'upi', 'wallet', 'pay_later')
  - `callback_url` (string, required): URL to redirect after payment
  - `bank_code` (string, conditional): Required for `net_banking` payment mode (e.g., 'HDFC', 'ICICI', 'SBI', 'AXIS')
  - `wallet_code` (string, conditional): Required for `wallet` payment mode
  - `upi_id` (string, conditional): Required for `upi` payment mode
  - `card` (array, conditional): Required for card payments:
    - `card_no` (string): Card number
    - `card_input_type` (string): 'card_pan', 'merchant_network_token', or 'nimbbl_token_id'
    - `network_token` (string): Network token (if card_input_type is 'merchant_network_token')
    - `nimbbl_token_id` (string): Nimbbl token ID (if card_input_type is 'nimbbl_token_id')
  - `emi` (array, optional): EMI details for EMI payments
  - `offer_id` (string, optional): Offer ID if using an offer
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Payment initiation response containing:
  - `transaction_id`: Transaction identifier
  - `status`: Payment status
  - `payment_mode_code`: Payment mode used
  - `next`: Array of next steps (e.g., redirect URL, OTP requirement)
  - Other payment details

**Example - Net Banking:**
```php
use Nimbbl\Api\Api;

$api = new Api($accessKey, $accessSecret, $baseUrl, $apiVersion);

$paymentData = [
    'order_id' => 'o_4KQ3NzX4oO3PwYw2',
    'payment_mode_code' => 'net_banking',
    'bank_code' => 'HDFC', // Required for net_banking
    'callback_url' => 'https://yourwebsite.com/payment/callback'
];

$payment = $api->payments()->initiatePayment($paymentData, $orderToken);

if (!isset($payment['error'])) {
    $transactionId = $payment['transaction_id'];
    echo "Payment initiated. Transaction ID: {$transactionId}\n";
    
    // Check if redirect is required
    if (isset($payment['next']) && is_array($payment['next'])) {
        foreach ($payment['next'] as $next) {
            if (isset($next['type']) && $next['type'] === 'redirect') {
                $redirectUrl = $next['url'];
                echo "Redirect to: {$redirectUrl}\n";
            }
        }
    }
} else {
    echo "Error: " . $payment['error']['nimbbl_merchant_message'] . "\n";
}
```

**Example - Credit Card:**
```php
$paymentData = [
    'order_id' => 'o_4KQ3NzX4oO3PwYw2',
    'payment_mode_code' => 'credit_card',
    'card' => [
        'card_input_type' => 'card_pan',
        'card_no' => '4111111111111111'
    ],
    'callback_url' => 'https://yourwebsite.com/payment/callback'
];

$payment = $api->payments()->initiatePayment($paymentData, $orderToken);
```

**Example - UPI:**
```php
$paymentData = [
    'order_id' => 'o_4KQ3NzX4oO3PwYw2',
    'payment_mode_code' => 'upi',
    'upi_id' => 'customer@paytm',
    'callback_url' => 'https://yourwebsite.com/payment/callback'
];

$payment = $api->payments()->initiatePayment($paymentData, $orderToken);
```

**Example - Wallet:**
```php
$paymentData = [
    'order_id' => 'o_4KQ3NzX4oO3PwYw2',
    'payment_mode_code' => 'wallet',
    'wallet_code' => 'paytm',
    'callback_url' => 'https://yourwebsite.com/payment/callback'
];

$payment = $api->payments()->initiatePayment($paymentData, $orderToken);
```

---

### 2. completePayment

Complete a payment that requires additional steps (e.g., OTP verification for Pay Later providers).

**Method Signature:**
```php
public function completePayment($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Payment completion attributes:
  - `transaction_id` (string, required): Transaction ID from payment initiation
  - `payment_flow` (string, required): Payment flow type ('auto_debit' or 'otp')
  - `otp` (string, conditional): OTP required if `payment_flow` is 'otp'
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Payment completion response containing:
  - `transaction_id`: Transaction identifier
  - `status`: Payment status ('success', 'failed', 'pending')
  - `payment_mode_code`: Payment mode used
  - Other payment details

**Example - Auto Debit:**
```php
$completeData = [
    'transaction_id' => 't_abc123xyz',
    'payment_flow' => 'auto_debit'
];

$result = $api->payments()->completePayment($completeData, $orderToken);

if (!isset($result['error'])) {
    echo "Payment Status: " . $result['status'] . "\n";
    if ($result['status'] === 'success') {
        echo "Payment completed successfully!\n";
    }
} else {
    echo "Error: " . $result['error']['nimbbl_merchant_message'] . "\n";
}
```

**Example - OTP Flow:**
```php
$completeData = [
    'transaction_id' => 't_abc123xyz',
    'payment_flow' => 'otp',
    'otp' => '123456' // OTP received by customer
];

$result = $api->payments()->completePayment($completeData, $orderToken);
```

---

### 3. resendPaymentOtp

Resend OTP for payment verification.

**Method Signature:**
```php
public function resendPaymentOtp($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Resend OTP attributes:
  - `transaction_id` (string, required): Transaction ID
  - `order_id` (string, optional): Order ID (alternative to transaction_id)
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Resend OTP response containing:
  - `message`: Success message
  - `transaction_id`: Transaction identifier
  - Other details

**Example:**
```php
$resendData = [
    'transaction_id' => 't_abc123xyz'
];

$result = $api->payments()->resendPaymentOtp($resendData, $orderToken);

if (!isset($result['error'])) {
    echo "OTP resent successfully!\n";
    echo "Message: " . $result['message'] . "\n";
} else {
    echo "Error: " . $result['error']['nimbbl_merchant_message'] . "\n";
}
```

---

## Payment Flow Examples

### Complete Payment Flow - Net Banking

```php
// Step 1: Create order
$order = $api->orders()->createOrder($orderData, $merchantToken);
$orderToken = $order['token'];
$orderId = $order['order_id'];

// Step 2: Initiate payment
$paymentData = [
    'order_id' => $orderId,
    'payment_mode_code' => 'net_banking',
    'bank_code' => 'HDFC',
    'callback_url' => 'https://yourwebsite.com/payment/callback'
];

$payment = $api->payments()->initiatePayment($paymentData, $orderToken);
$transactionId = $payment['transaction_id'];

// Step 3: Redirect customer to bank page (if redirect URL provided)
if (isset($payment['next'])) {
    foreach ($payment['next'] as $next) {
        if (isset($next['type']) && $next['type'] === 'redirect') {
            // Redirect customer to $next['url']
            header('Location: ' . $next['url']);
            exit;
        }
    }
}

// Step 4: Handle callback (in callback URL handler)
// Payment status will be updated via webhook or callback
```

### Complete Payment Flow - Card with OTP

```php
// Step 1: Initiate payment
$paymentData = [
    'order_id' => $orderId,
    'payment_mode_code' => 'credit_card',
    'card' => [
        'card_input_type' => 'card_pan',
        'card_no' => '4111111111111111'
    ],
    'callback_url' => 'https://yourwebsite.com/payment/callback'
];

$payment = $api->payments()->initiatePayment($paymentData, $orderToken);

// Step 2: Check if OTP is required
if (isset($payment['next'])) {
    foreach ($payment['next'] as $next) {
        if (isset($next['type']) && $next['type'] === 'otp') {
            // Prompt user for OTP
            $otp = getUserInput(); // Your function to get OTP from user
            
            // Step 3: Complete payment with OTP
            $completeData = [
                'transaction_id' => $payment['transaction_id'],
                'payment_flow' => 'otp',
                'otp' => $otp
            ];
            
            $result = $api->payments()->completePayment($completeData, $orderToken);
            
            if ($result['status'] === 'success') {
                echo "Payment successful!\n";
            }
        }
    }
}
```

---

## Common Bank Codes

For `net_banking` payment mode, use these common bank codes:

- `HDFC` - HDFC Bank
- `ICICI` - ICICI Bank
- `SBI` - State Bank of India
- `AXIS` - Axis Bank
- `KOTAK` - Kotak Mahindra Bank
- `PNB` - Punjab National Bank
- `BOB` - Bank of Baroda
- `UBI` - Union Bank of India

To get the complete list of available banks, use the [Checkout Utilities API](./CHECKOUT_UTILITIES.md#listbanks).

---

## Error Codes

Common error codes you may encounter:

- `BANK_MISSING`: Bank code is required for net_banking payment mode
- `INVALID_PAYMENT_MODE`: Invalid payment mode code
- `INVALID_CARD`: Invalid card details
- `OTP_REQUIRED`: OTP is required to complete payment
- `OTP_INVALID`: Invalid OTP provided
- `PAYMENT_FAILED`: Payment processing failed
- `TRANSACTION_NOT_FOUND`: Transaction ID not found

---

## Best Practices

1. **Always provide bank_code for net_banking**: The `bank_code` parameter is mandatory for net banking payments
2. **Handle redirects properly**: Check the `next` array for redirect URLs and handle them appropriately
3. **Store transaction_id**: Save the transaction ID for tracking and status checks
4. **Implement webhook handlers**: Use webhooks to get real-time payment status updates
5. **Handle OTP flow**: For payment modes requiring OTP, implement proper OTP collection and verification
6. **Use callback URLs**: Always provide a valid callback URL for payment completion
7. **Error handling**: Always check for `error` key in response and handle errors gracefully

---

## Related Documentation

- [Orders API](./ORDERS.md) - Create orders
- [Transaction Enquiry](./TRANSACTIONS.md) - Check payment status
- [Webhooks](./WEBHOOKS.md) - Receive payment status updates
- [Checkout Utilities API](./CHECKOUT_UTILITIES.md) - Get available payment modes and banks



