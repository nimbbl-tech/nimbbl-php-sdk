# Orders API Documentation

The Orders API allows you to create and retrieve orders in the Nimbbl payment system.

## Overview

- **Token Type**: Order Token (obtained from order creation response)
- **Base URL**: `/api/v3/order`
- **API Documentation**: https://nimbbl.biz/docs/api-reference/create-an-order-v-3/

## Methods

### 1. createOrder

Create a new order in the Nimbbl system.

**Method Signature:**
```php
public function createOrder($attributes, $token)
```

**Parameters:**
- `$attributes` (array): Order attributes including:
  - `invoice_id` (string, optional): Unique invoice reference
  - `amount_before_tax` (number, required): Amount before tax
  - `tax` (number, required): Tax amount
  - `total_amount` (number, required): Total amount (amount_before_tax + tax)
  - `currency` (string, optional): Currency code (default: 'INR')
  - `user` (array, required): User information:
    - `email` (string, required): User email
    - `first_name` (string, required): First name
    - `last_name` (string, optional): Last name
    - `mobile_number` (string, required): Mobile number
    - `country_code` (string, required): Country code (e.g., '+91')
  - `shipping_address` (array, optional): Shipping address
  - `billing_address` (array, optional): Billing address
  - `order_line_items` (array, optional): Array of order line items
  - `metadata` (array, optional): Custom metadata
- `$token` (string): Authentication token (Merchant Token for initial order creation, required)

**Returns:**
- `array`: Order response containing:
  - `order_id` or `nimbbl_order_id`: Order identifier
  - `token`: Order token (use this for subsequent operations)
  - `status`: Order status
  - `invoice_id`: Invoice ID
  - Other order details

**Example:**
```php
use Nimbbl\Api\Api;

$api = new Api($accessKey, $accessSecret, $baseUrl, $apiVersion);

// Step 1: Generate merchant token
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

// Step 2: Create order
$orderData = [
    'invoice_id' => 'INV-12345',
    'amount_before_tax' => 100.00,
    'tax' => 18.00,
    'total_amount' => 118.00,
    'currency' => 'INR',
    'user' => [
        'email' => 'customer@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_number' => '9876543210',
        'country_code' => '+91'
    ],
    'shipping_address' => [
        'address_1' => '123 Main Street',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
        'country' => 'India'
    ]
];

$order = $api->orders()->createOrder($orderData, $merchantToken);

if (!isset($order['error'])) {
    $orderToken = $order['token']; // Save this for subsequent operations
    $orderId = $order['order_id'] ?? $order['nimbbl_order_id'];
    echo "Order created: {$orderId}\n";
    echo "Order Token: {$orderToken}\n";
} else {
    echo "Error: " . $order['error']['nimbbl_merchant_message'] . "\n";
}
```

**Error Handling:**
```php
try {
    $order = $api->orders()->createOrder($orderData, $merchantToken);
} catch (\Nimbbl\Api\Exception\BadRequestException $e) {
    echo "Bad Request: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getErrorCode() . "\n";
} catch (\Nimbbl\Api\Exception\ApiException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
}
```

---

### 2. getOrderById

Retrieve an order by its order ID.

**Method Signature:**
```php
public function getOrderById($orderId, $token)
```

**Parameters:**
- `$orderId` (string): Order ID to retrieve
- `$token` (string): Order token (required)

**Returns:**
- `array`: Order details including:
  - `order_id`: Order identifier
  - `invoice_id`: Invoice ID
  - `status`: Order status
  - `total_amount`: Total amount
  - `currency`: Currency code
  - `user`: User information
  - `transactions`: Array of associated transactions
  - Other order details

**Example:**
```php
$orderId = 'o_4KQ3NzX4oO3PwYw2';
$order = $api->orders()->getOrderById($orderId, $orderToken);

if (!isset($order['error'])) {
    echo "Order ID: " . $order['order_id'] . "\n";
    echo "Status: " . $order['status'] . "\n";
    echo "Amount: " . $order['total_amount'] . " " . $order['currency'] . "\n";
} else {
    echo "Error: " . $order['error']['nimbbl_merchant_message'] . "\n";
}
```

---

### 3. getOrderByInvoiceId

Retrieve an order by its invoice ID.

**Method Signature:**
```php
public function getOrderByInvoiceId($invoiceId, $token)
```

**Parameters:**
- `$invoiceId` (string): Invoice ID to retrieve
- `$token` (string): Order token (required)

**Returns:**
- `array`: Order details (same structure as `getOrderById`)

**Example:**
```php
$invoiceId = 'INV-12345';
$order = $api->orders()->getOrderByInvoiceId($invoiceId, $orderToken);

if (!isset($order['error'])) {
    echo "Invoice ID: " . $order['invoice_id'] . "\n";
    echo "Order ID: " . $order['order_id'] . "\n";
    echo "Status: " . $order['status'] . "\n";
} else {
    echo "Error: " . $order['error']['nimbbl_merchant_message'] . "\n";
}
```

---

## Token Management

### Order Token Flow

1. **Initial Order Creation**: Use Merchant Token (from `generateToken()`)
2. **Extract Order Token**: From order creation response (`$order['token']`)
3. **Subsequent Operations**: Use Order Token for:
   - Getting order details
   - Payment operations
   - Payment link operations
   - Address operations
   - Checkout utilities

```php
// Complete flow
$merchantToken = $api->auth()->generateToken()['token'];
$order = $api->orders()->createOrder($orderData, $merchantToken);
$orderToken = $order['token']; // Save this!

// Use order token for subsequent operations
$orderDetails = $api->orders()->getOrderById($order['order_id'], $orderToken);
```

---

## Common Use Cases

### 1. Create Order with Shipping Address
```php
$orderData = [
    'invoice_id' => 'INV-001',
    'amount_before_tax' => 500.00,
    'tax' => 90.00,
    'total_amount' => 590.00,
    'currency' => 'INR',
    'user' => [
        'email' => 'customer@example.com',
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'mobile_number' => '9876543210',
        'country_code' => '+91'
    ],
    'shipping_address' => [
        'address_1' => '456 Oak Avenue',
        'street' => 'MG Road',
        'landmark' => 'Near Park',
        'area' => 'Downtown',
        'city' => 'Bangalore',
        'state' => 'Karnataka',
        'pincode' => '560001',
        'country' => 'India'
    ]
];
```

### 2. Create Order with Line Items
```php
$orderData = [
    'invoice_id' => 'INV-002',
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
    'order_line_items' => [
        [
            'sku_id' => 'SKU-001',
            'name' => 'Product 1',
            'quantity' => 2,
            'unit_price' => 500.00
        ],
        [
            'sku_id' => 'SKU-002',
            'name' => 'Product 2',
            'quantity' => 1,
            'unit_price' => 180.00
        ]
    ]
];
```

---

## Error Codes

Common error codes you may encounter:

- `INVALID_REQUEST`: Invalid request parameters
- `AUTHENTICATION_FAILED`: Token authentication failed
- `ORDER_NOT_FOUND`: Order with given ID not found
- `INVOICE_ID_ALREADY_EXISTS`: Invoice ID already used

---

## Best Practices

1. **Always store the Order Token**: The order token is required for most subsequent operations
2. **Use unique Invoice IDs**: Ensure invoice IDs are unique to avoid conflicts
3. **Validate amounts**: Ensure `total_amount = amount_before_tax + tax`
4. **Handle errors gracefully**: Always check for `error` key in response
5. **Use try-catch blocks**: Wrap API calls in try-catch for proper error handling

---

## Related Documentation

- [Payments API](./PAYMENTS.md) - Initiate and complete payments
- [Payment Links API](./PAYMENT_LINKS.md) - Create payment links
- [Addresses API](./ADDRESSES.md) - Manage customer addresses
- [Authentication API](./AUTHENTICATION.md) - Token generation



