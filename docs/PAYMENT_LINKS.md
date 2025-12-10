# Payment Links API Documentation

The Payment Links API allows you to create, update, and manage payment links for orders.

## Overview

- **Token Type**: Order Token (obtained from order creation)
- **Base URL**: `/api/v3/payment-link`
- **API Documentation**: https://nimbbl.biz/docs/api-reference/payment-links-v-3/

## Methods

### 1. createPaymentLink

Create a payment link for an order.

**Method Signature:**
```php
public function createPaymentLink($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Payment link attributes:
  - `invoice_id` (string, required): Unique invoice reference
  - `amount_before_tax` (number, required): Amount before tax
  - `tax` (number, required): Tax amount
  - `total_amount` (number, required): Total amount
  - `currency` (string, optional): Currency code (default: 'INR')
  - `user` (array, required): User information
  - `expires_at` (string, optional): Payment link expiration timestamp (ISO 8601 format)
  - `metadata` (array, optional): Custom metadata
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Payment link response containing:
  - `payment_link_id`: Payment link identifier
  - `payment_link_url`: URL to share with customer
  - `status`: Payment link status
  - `invoice_id`: Invoice ID
  - Other payment link details

**Example:**
```php
use Nimbbl\Api\Api;

$api = new Api($accessKey, $accessSecret, $baseUrl, $apiVersion);

$paymentLinkData = [
    'invoice_id' => 'INV-PL-001',
    'amount_before_tax' => 1000.00,
    'tax' => 180.00,
    'total_amount' => 1180.00,
    'currency' => 'INR',
    'user' => [
        'email' => 'customer@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_number' => '9876543210',
        'country_code' => '+91'
    ],
    'expires_at' => date('c', strtotime('+7 days')) // Expires in 7 days
];

$paymentLink = $api->paymentLinks()->createPaymentLink($paymentLinkData, $orderToken);

if (!isset($paymentLink['error'])) {
    echo "Payment Link Created!\n";
    echo "Payment Link ID: " . $paymentLink['payment_link_id'] . "\n";
    echo "Payment Link URL: " . $paymentLink['payment_link_url'] . "\n";
    echo "Status: " . $paymentLink['status'] . "\n";
    
    // Share this URL with customer
    // $paymentLink['payment_link_url']
} else {
    echo "Error: " . $paymentLink['error']['nimbbl_merchant_message'] . "\n";
}
```

---

### 2. updatePaymentLink

Update an existing payment link.

**Method Signature:**
```php
public function updatePaymentLink($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Payment link update attributes:
  - `invoice_id` (string, required): Invoice ID of the payment link to update
  - OR `payment_link_id` (string, required): Payment link ID
  - `amount_before_tax` (number, optional): Updated amount before tax
  - `tax` (number, optional): Updated tax amount
  - `total_amount` (number, optional): Updated total amount
  - `expires_at` (string, optional): Updated expiration timestamp
  - `metadata` (array, optional): Updated metadata
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Updated payment link response

**Example:**
```php
$updateData = [
    'invoice_id' => 'INV-PL-001',
    'total_amount' => 1500.00, // Update amount
    'expires_at' => date('c', strtotime('+14 days')) // Extend expiration
];

$updatedLink = $api->paymentLinks()->updatePaymentLink($updateData, $orderToken);

if (!isset($updatedLink['error'])) {
    echo "Payment Link Updated!\n";
    echo "New URL: " . $updatedLink['payment_link_url'] . "\n";
    echo "New Status: " . $updatedLink['status'] . "\n";
} else {
    echo "Error: " . $updatedLink['error']['nimbbl_merchant_message'] . "\n";
}
```

---

### 3. enquiryPaymentLink

Get details of a payment link.

**Method Signature:**
```php
public function enquiryPaymentLink($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Enquiry attributes:
  - `invoice_id` (string, required): Invoice ID
  - OR `payment_link_id` (string, required): Payment link ID
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Payment link details including:
  - `payment_link_id`: Payment link identifier
  - `payment_link_url`: Payment link URL
  - `status`: Payment link status
  - `order_line_items`: Order items
  - `user`: User information
  - `orders_with_transactions`: Associated orders and transactions
  - Other details

**Example:**
```php
$enquiryData = [
    'invoice_id' => 'INV-PL-001'
];

$linkDetails = $api->paymentLinks()->enquiryPaymentLink($enquiryData, $orderToken);

if (!isset($linkDetails['error'])) {
    echo "Payment Link Details:\n";
    echo "  Payment Link ID: " . $linkDetails['payment_link_id'] . "\n";
    echo "  Status: " . $linkDetails['status'] . "\n";
    echo "  URL: " . $linkDetails['payment_link_url'] . "\n";
    
    if (isset($linkDetails['orders_with_transactions'])) {
        echo "  Associated Orders: " . count($linkDetails['orders_with_transactions']) . "\n";
    }
} else {
    echo "Error: " . $linkDetails['error']['nimbbl_merchant_message'] . "\n";
}
```

---

### 4. performPaymentLinkActions

Perform actions on a payment link (e.g., cancel, expire).

**Method Signature:**
```php
public function performPaymentLinkActions($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Action attributes:
  - `invoice_id` (string, required): Invoice ID
  - OR `payment_link_id` (string, required): Payment link ID
  - `action` (string, required): Action to perform (e.g., 'cancel', 'expire')
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Action response with updated status

**Example:**
```php
$actionData = [
    'invoice_id' => 'INV-PL-001',
    'action' => 'cancel'
];

$result = $api->paymentLinks()->performPaymentLinkActions($actionData, $orderToken);

if (!isset($result['error'])) {
    echo "Payment Link Action Performed!\n";
    echo "New Status: " . $result['status'] . "\n";
} else {
    echo "Error: " . $result['error']['nimbbl_merchant_message'] . "\n";
}
```

---

## Complete Payment Link Flow

```php
// Step 1: Create payment link
$paymentLinkData = [
    'invoice_id' => 'INV-PL-001',
    'amount_before_tax' => 1000.00,
    'tax' => 180.00,
    'total_amount' => 1180.00,
    'currency' => 'INR',
    'user' => [
        'email' => 'customer@example.com',
        'first_name' => 'John',
        'mobile_number' => '9876543210',
        'country_code' => '+91'
    ],
    'expires_at' => date('c', strtotime('+7 days'))
];

$paymentLink = $api->paymentLinks()->createPaymentLink($paymentLinkData, $orderToken);
$paymentLinkUrl = $paymentLink['payment_link_url'];

// Step 2: Share URL with customer (via email, SMS, etc.)
// Customer clicks link and completes payment

// Step 3: Check payment link status
$enquiryData = ['invoice_id' => 'INV-PL-001'];
$linkDetails = $api->paymentLinks()->enquiryPaymentLink($enquiryData, $orderToken);

if (isset($linkDetails['orders_with_transactions'])) {
    foreach ($linkDetails['orders_with_transactions'] as $order) {
        echo "Order ID: " . $order['order_id'] . "\n";
        echo "Status: " . $order['status'] . "\n";
    }
}
```

---

## Use Cases

### 1. Create Payment Link for Invoice
```php
$paymentLinkData = [
    'invoice_id' => 'INV-2024-001',
    'amount_before_tax' => 5000.00,
    'tax' => 900.00,
    'total_amount' => 5900.00,
    'currency' => 'INR',
    'user' => [
        'email' => 'customer@example.com',
        'first_name' => 'Jane',
        'mobile_number' => '9876543210',
        'country_code' => '+91'
    ]
];

$paymentLink = $api->paymentLinks()->createPaymentLink($paymentLinkData, $orderToken);
// Send $paymentLink['payment_link_url'] to customer
```

### 2. Update Payment Link Amount
```php
$updateData = [
    'invoice_id' => 'INV-2024-001',
    'amount_before_tax' => 6000.00,
    'tax' => 1080.00,
    'total_amount' => 7080.00
];

$updatedLink = $api->paymentLinks()->updatePaymentLink($updateData, $orderToken);
```

### 3. Cancel Payment Link
```php
$actionData = [
    'invoice_id' => 'INV-2024-001',
    'action' => 'cancel'
];

$result = $api->paymentLinks()->performPaymentLinkActions($actionData, $orderToken);
```

---

## Error Codes

Common error codes you may encounter:

- `INVALID_INVOICE_ID`: Invalid or missing invoice ID
- `PAYMENT_LINK_NOT_FOUND`: Payment link not found
- `PAYMENT_LINK_EXPIRED`: Payment link has expired
- `PAYMENT_LINK_ALREADY_PAID`: Payment link already has a successful payment
- `INVALID_ACTION`: Invalid action specified

---

## Best Practices

1. **Use unique invoice IDs**: Ensure invoice IDs are unique across all payment links
2. **Set expiration dates**: Always set appropriate expiration dates for payment links
3. **Monitor payment link status**: Regularly check payment link status to track payments
4. **Handle webhooks**: Use webhooks to get real-time updates when customers pay via payment link
5. **Store payment link URLs**: Save payment link URLs for reference and tracking
6. **Update before expiration**: Update payment links before they expire if needed
7. **Cancel unused links**: Cancel payment links that are no longer needed

---

## Related Documentation

- [Orders API](./ORDERS.md) - Create orders
- [Payments API](./PAYMENTS.md) - Process payments
- [Webhooks](./WEBHOOKS.md) - Receive payment status updates



