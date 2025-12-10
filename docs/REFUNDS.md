# Refunds API Documentation

The Refunds API allows you to process refunds for successful payment transactions.

## Overview

- **Token Type**: Merchant Token (obtained from `generateToken()`)
- **Base URL**: `/api/v3/refund`
- **API Documentation**: https://nimbbl.biz/docs/api-reference/refund-a-payment-v-3/

## Methods

### initiateRefund

Initiate a refund for a successful payment transaction.

**Method Signature:**
```php
public function initiateRefund($attributes = array(), $token = null)
```

**Parameters:**
- `$attributes` (array): Refund attributes:
  - `transaction_id` (string, nullable): Nimbbl transaction ID (required if `invoice_id` not provided)
  - `invoice_id` (string, nullable): Unique invoice reference (required if `transaction_id` not provided)
  - `refund_amount` (number, nullable): Amount to refund (optional, for partial refunds)
    - If not provided, full refund is processed
  - `comment` (string, nullable): Refund reason/comment
  - `refund_request_id` (string, nullable): Unique identifier to avoid duplicate refunds (idempotency)
  - `order_line_items` (array, nullable): Array of items to refund (for item-level refunds):
    - `sku_id` (string, required): SKU to be refunded
    - `serial_numbers` (array, nullable): Serial numbers to refund (optional)
- `$token` (string|null): Merchant token (optional, will use cached or basic auth if not provided)

**Returns:**
- `array`: Refund response containing:
  - `refund_id`: Refund identifier
  - `transaction_id`: Original transaction ID
  - `refund_amount`: Refunded amount
  - `status`: Refund status
  - `created_at`: Refund creation timestamp
  - Other refund details

**Example - Full Refund:**
```php
use Nimbbl\Api\Api;

$api = new Api($accessKey, $accessSecret, $baseUrl, $apiVersion);

// Generate merchant token
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

// Full refund using transaction_id
$refundData = [
    'transaction_id' => 't_abc123xyz',
    'comment' => 'Customer requested refund'
];

$refund = $api->refunds()->initiateRefund($refundData, $merchantToken);

if (!isset($refund['error'])) {
    echo "Refund initiated successfully!\n";
    echo "Refund ID: " . $refund['refund_id'] . "\n";
    echo "Refund Amount: " . $refund['refund_amount'] . "\n";
    echo "Status: " . $refund['status'] . "\n";
} else {
    echo "Error: " . $refund['error']['nimbbl_merchant_message'] . "\n";
}
```

**Example - Partial Refund:**
```php
// Partial refund - refund only part of the transaction amount
$refundData = [
    'transaction_id' => 't_abc123xyz',
    'refund_amount' => 500.00, // Refund only 500 out of total amount
    'comment' => 'Partial refund for damaged item'
];

$refund = $api->refunds()->initiateRefund($refundData, $merchantToken);
```

**Example - Refund by Invoice ID:**
```php
$refundData = [
    'invoice_id' => 'INV-12345',
    'refund_amount' => 1000.00,
    'comment' => 'Refund for cancelled order'
];

$refund = $api->refunds()->initiateRefund($refundData, $merchantToken);
```

**Example - Item-Level Refund:**
```php
// Refund specific items from an order
$refundData = [
    'transaction_id' => 't_abc123xyz',
    'order_line_items' => [
        [
            'sku_id' => 'SKU-001',
            'serial_numbers' => ['SN-001', 'SN-002'] // Optional
        ],
        [
            'sku_id' => 'SKU-002'
        ]
    ],
    'comment' => 'Refund for specific items'
];

$refund = $api->refunds()->initiateRefund($refundData, $merchantToken);
```

**Example - Idempotent Refund:**
```php
// Use refund_request_id to prevent duplicate refunds
$refundData = [
    'transaction_id' => 't_abc123xyz',
    'refund_request_id' => 'REF-' . time() . '-' . uniqid(), // Unique ID
    'comment' => 'Refund request'
];

$refund = $api->refunds()->initiateRefund($refundData, $merchantToken);

// If you call this again with the same refund_request_id, it will return the same refund
// instead of creating a duplicate
```

---

## Complete Refund Flow

```php
// Step 1: Generate merchant token
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

// Step 2: Check transaction status (optional)
$transactionData = [
    'transaction_id' => 't_abc123xyz'
];
$transaction = $api->transactions()->transactionEnquiry($transactionData, $merchantToken);

if ($transaction['status'] === 'success') {
    // Step 3: Initiate refund
    $refundData = [
        'transaction_id' => 't_abc123xyz',
        'refund_amount' => $transaction['amount'], // Full refund
        'comment' => 'Customer requested refund'
    ];
    
    $refund = $api->refunds()->initiateRefund($refundData, $merchantToken);
    
    if (!isset($refund['error'])) {
        echo "Refund processed successfully!\n";
        echo "Refund ID: " . $refund['refund_id'] . "\n";
    }
}
```

---

## Use Cases

### 1. Full Refund
```php
$refundData = [
    'transaction_id' => 't_abc123xyz',
    'comment' => 'Full refund - order cancelled'
];

$refund = $api->refunds()->initiateRefund($refundData, $merchantToken);
```

### 2. Partial Refund
```php
$refundData = [
    'transaction_id' => 't_abc123xyz',
    'refund_amount' => 500.00, // Refund only 500
    'comment' => 'Partial refund - item damaged'
];

$refund = $api->refunds()->initiateRefund($refundData, $merchantToken);
```

### 3. Multiple Partial Refunds
```php
// First partial refund
$refundData1 = [
    'transaction_id' => 't_abc123xyz',
    'refund_amount' => 500.00,
    'refund_request_id' => 'REF-001',
    'comment' => 'First partial refund'
];
$refund1 = $api->refunds()->initiateRefund($refundData1, $merchantToken);

// Second partial refund
$refundData2 = [
    'transaction_id' => 't_abc123xyz',
    'refund_amount' => 300.00,
    'refund_request_id' => 'REF-002',
    'comment' => 'Second partial refund'
];
$refund2 = $api->refunds()->initiateRefund($refundData2, $merchantToken);
```

### 4. Refund by Invoice ID
```php
$refundData = [
    'invoice_id' => 'INV-12345',
    'comment' => 'Refund for invoice'
];

$refund = $api->refunds()->initiateRefund($refundData, $merchantToken);
```

---

## Error Codes

Common error codes you may encounter:

- `TRANSACTION_NOT_FOUND`: Transaction ID not found
- `INVOICE_NOT_FOUND`: Invoice ID not found
- `REFUND_ALREADY_PROCESSED`: Refund already processed for this transaction
- `INVALID_REFUND_AMOUNT`: Refund amount exceeds transaction amount
- `REFUND_NOT_ALLOWED`: Refund not allowed for this transaction status
- `DUPLICATE_REFUND_REQUEST`: Duplicate refund request (use `refund_request_id` to prevent)

---

## Best Practices

1. **Use Merchant Token**: Always use merchant token (not order token) for refund operations
2. **Check transaction status**: Verify transaction is successful before initiating refund
3. **Use refund_request_id**: Always provide a unique `refund_request_id` to prevent duplicate refunds
4. **Store refund IDs**: Save refund IDs for tracking and reconciliation
5. **Handle partial refunds carefully**: Track remaining refundable amount for multiple partial refunds
6. **Add comments**: Always provide meaningful comments for audit purposes
7. **Monitor refund status**: Use transaction enquiry to check refund status
8. **Handle errors gracefully**: Always check for `error` key in response

---

## Refund Status

Refund status can be checked using the [Transaction Enquiry API](./TRANSACTIONS.md):

```php
$transactionData = [
    'transaction_id' => 't_abc123xyz'
];

$transaction = $api->transactions()->transactionEnquiry($transactionData, $merchantToken);

if (isset($transaction['refunds'])) {
    foreach ($transaction['refunds'] as $refund) {
        echo "Refund ID: " . $refund['refund_id'] . "\n";
        echo "Refund Amount: " . $refund['refund_amount'] . "\n";
        echo "Refund Status: " . $refund['status'] . "\n";
    }
}
```

---

## Related Documentation

- [Transactions API](./TRANSACTIONS.md) - Check transaction and refund status
- [Authentication API](./AUTHENTICATION.md) - Generate merchant token
- [Webhooks](./WEBHOOKS.md) - Receive refund status updates



