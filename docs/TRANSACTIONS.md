# Transactions API Documentation

The Transactions API allows you to enquire about transaction status by order ID, invoice ID, or transaction ID.

## Overview

- **Token Type**: Merchant Token (obtained from `generateToken()`)
- **Base URL**: `/api/v3/transaction-enquiry`
- **API Documentation**: https://nimbbl.biz/docs/api-reference/transaction-enquiry-v-3/

## Methods

### transactionEnquiry

Get the latest status of an order and associated transactions.

**Method Signature:**
```php
public function transactionEnquiry($attributes = array(), $token = null)
```

**Parameters:**
- `$attributes` (array): Query attributes (provide one of the following):
  - `order_id` (string, optional): Order ID to query
  - `invoice_id` (string, optional): Invoice ID to query
  - `transaction_id` (string, optional): Transaction ID to query
- `$token` (string|null): Merchant token (optional, will use cached or basic auth if not provided)

**Returns:**
- `array`: Transaction enquiry response containing:
  - `order_id`: Order identifier
  - `invoice_id`: Invoice ID
  - `status`: Order status
  - `total_amount`: Total order amount
  - `currency`: Currency code
  - `transactions`: Array of associated transactions, each containing:
    - `transaction_id`: Transaction identifier
    - `status`: Transaction status ('success', 'failed', 'pending')
    - `payment_mode_code`: Payment mode used
    - `amount`: Transaction amount
    - `created_at`: Transaction creation timestamp
    - `refunds`: Array of refunds (if any)
  - Other order and transaction details

**Example - Query by Transaction ID:**
```php
use Nimbbl\Api\Api;

$api = new Api($accessKey, $accessSecret, $baseUrl, $apiVersion);

// Generate merchant token
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

// Query by transaction ID
$enquiryData = [
    'transaction_id' => 't_abc123xyz'
];

$result = $api->transactions()->transactionEnquiry($enquiryData, $merchantToken);

if (!isset($result['error'])) {
    echo "Order ID: " . $result['order_id'] . "\n";
    echo "Invoice ID: " . $result['invoice_id'] . "\n";
    echo "Order Status: " . $result['status'] . "\n";
    
    if (isset($result['transactions']) && count($result['transactions']) > 0) {
        foreach ($result['transactions'] as $transaction) {
            echo "\nTransaction Details:\n";
            echo "  Transaction ID: " . $transaction['transaction_id'] . "\n";
            echo "  Status: " . $transaction['status'] . "\n";
            echo "  Payment Mode: " . $transaction['payment_mode_code'] . "\n";
            echo "  Amount: " . $transaction['amount'] . " " . $result['currency'] . "\n";
            
            // Check for refunds
            if (isset($transaction['refunds']) && count($transaction['refunds']) > 0) {
                echo "  Refunds:\n";
                foreach ($transaction['refunds'] as $refund) {
                    echo "    - Refund ID: " . $refund['refund_id'] . "\n";
                    echo "      Amount: " . $refund['refund_amount'] . "\n";
                    echo "      Status: " . $refund['status'] . "\n";
                }
            }
        }
    }
} else {
    echo "Error: " . $result['error']['nimbbl_merchant_message'] . "\n";
}
```

**Example - Query by Order ID:**
```php
$enquiryData = [
    'order_id' => 'o_4KQ3NzX4oO3PwYw2'
];

$result = $api->transactions()->transactionEnquiry($enquiryData, $merchantToken);

if (!isset($result['error'])) {
    echo "Order Status: " . $result['status'] . "\n";
    echo "Total Amount: " . $result['total_amount'] . " " . $result['currency'] . "\n";
    
    // Check transaction status
    if (isset($result['transactions'])) {
        $successfulTransactions = array_filter($result['transactions'], function($txn) {
            return $txn['status'] === 'success';
        });
        
        if (count($successfulTransactions) > 0) {
            echo "Payment successful!\n";
        } else {
            echo "No successful transactions found.\n";
        }
    }
}
```

**Example - Query by Invoice ID:**
```php
$enquiryData = [
    'invoice_id' => 'INV-12345'
];

$result = $api->transactions()->transactionEnquiry($enquiryData, $merchantToken);

if (!isset($result['error'])) {
    echo "Invoice Status: " . $result['status'] . "\n";
    
    // Display all transactions
    if (isset($result['transactions'])) {
        echo "Transactions:\n";
        foreach ($result['transactions'] as $transaction) {
            echo "  - " . $transaction['transaction_id'] . " (" . $transaction['status'] . ")\n";
        }
    }
}
```

---

## Transaction Status Values

Transaction status can be one of the following:

- `success`: Payment completed successfully
- `failed`: Payment failed
- `pending`: Payment is pending
- `initiated`: Payment initiated but not completed
- `cancelled`: Payment was cancelled

---

## Complete Transaction Status Check Flow

```php
// Step 1: Generate merchant token
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

// Step 2: Query transaction status
$enquiryData = [
    'transaction_id' => 't_abc123xyz'
];

$result = $api->transactions()->transactionEnquiry($enquiryData, $merchantToken);

// Step 3: Process based on status
if (!isset($result['error'])) {
    $orderStatus = $result['status'];
    $transactions = $result['transactions'] ?? [];
    
    if ($orderStatus === 'paid' || $orderStatus === 'success') {
        // Order is paid - fulfill order
        echo "Order is paid. Proceed with fulfillment.\n";
    } elseif (count($transactions) > 0) {
        $latestTransaction = $transactions[0];
        
        if ($latestTransaction['status'] === 'success') {
            echo "Payment successful!\n";
        } elseif ($latestTransaction['status'] === 'failed') {
            echo "Payment failed. Reason: " . ($latestTransaction['failure_reason'] ?? 'Unknown') . "\n";
        } elseif ($latestTransaction['status'] === 'pending') {
            echo "Payment is pending. Waiting for confirmation.\n";
        }
    } else {
        echo "No transactions found for this order.\n";
    }
}
```

---

## Use Cases

### 1. Check Payment Status After Redirect
```php
// After customer returns from payment gateway
$transactionId = $_GET['transaction_id'] ?? null;

if ($transactionId) {
    $enquiryData = ['transaction_id' => $transactionId];
    $result = $api->transactions()->transactionEnquiry($enquiryData, $merchantToken);
    
    if (isset($result['transactions'][0])) {
        $transaction = $result['transactions'][0];
        
        if ($transaction['status'] === 'success') {
            // Payment successful - show success page
            echo "Payment successful!";
        } else {
            // Payment failed - show error page
            echo "Payment failed. Please try again.";
        }
    }
}
```

### 2. Verify Order Payment Status
```php
$orderId = 'o_4KQ3NzX4oO3PwYw2';
$enquiryData = ['order_id' => $orderId];
$result = $api->transactions()->transactionEnquiry($enquiryData, $merchantToken);

$isPaid = false;
if (isset($result['transactions'])) {
    foreach ($result['transactions'] as $transaction) {
        if ($transaction['status'] === 'success') {
            $isPaid = true;
            break;
        }
    }
}

if ($isPaid) {
    // Proceed with order fulfillment
    fulfillOrder($orderId);
}
```

### 3. Check Refund Status
```php
$transactionId = 't_abc123xyz';
$enquiryData = ['transaction_id' => $transactionId];
$result = $api->transactions()->transactionEnquiry($enquiryData, $merchantToken);

if (isset($result['transactions'][0]['refunds'])) {
    $refunds = $result['transactions'][0]['refunds'];
    
    foreach ($refunds as $refund) {
        echo "Refund ID: " . $refund['refund_id'] . "\n";
        echo "Refund Amount: " . $refund['refund_amount'] . "\n";
        echo "Refund Status: " . $refund['status'] . "\n";
    }
}
```

---

## Error Codes

Common error codes you may encounter:

- `TRANSACTION_NOT_FOUND`: Transaction ID not found
- `ORDER_NOT_FOUND`: Order ID not found
- `INVOICE_NOT_FOUND`: Invoice ID not found
- `INVALID_QUERY`: Invalid query parameters (must provide one of order_id, invoice_id, or transaction_id)

---

## Best Practices

1. **Use Merchant Token**: Always use merchant token (not order token) for transaction enquiry
2. **Query by appropriate ID**: Use the ID you have available (transaction_id, order_id, or invoice_id)
3. **Handle multiple transactions**: An order can have multiple transactions (retries, different payment modes)
4. **Check latest transaction**: If multiple transactions exist, check the latest one for current status
5. **Monitor refunds**: Check refunds array to see if any refunds have been processed
6. **Use webhooks**: Prefer webhooks for real-time status updates instead of polling
7. **Cache results**: Cache transaction status to avoid excessive API calls
8. **Handle errors gracefully**: Always check for `error` key in response

---

## Webhook Integration

Instead of polling for transaction status, use webhooks to receive real-time updates:

```php
// In your webhook handler
$webhook = new \Nimbbl\Api\Webhook();
$payload = $webhook->getPayloadFromInput();
$signature = $webhook->getSignatureFromHeaders($_SERVER);

$event = $webhook->verifyAndParse($payload, $signature, $accessSecret);

if ($event && $event['event_type'] === 'payment.success') {
    $transactionId = $event['payload']['transaction_id'];
    // Payment successful - update your database
}
```

See [Webhooks Documentation](./WEBHOOKS.md) for more details.

---

## Related Documentation

- [Refunds API](./REFUNDS.md) - Process refunds
- [Authentication API](./AUTHENTICATION.md) - Generate merchant token
- [Webhooks](./WEBHOOKS.md) - Receive real-time transaction updates
- [Orders API](./ORDERS.md) - Create and manage orders



