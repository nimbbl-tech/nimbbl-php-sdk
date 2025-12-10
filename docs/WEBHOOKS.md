# Webhooks Documentation

Webhooks allow you to receive real-time notifications about payment events from Nimbbl.

## Overview

- **Purpose**: Receive real-time updates about payment status changes
- **Method**: POST request to your webhook URL
- **Authentication**: Signature verification using `X-Nimbbl-Signature` header
- **Documentation**: https://nimbbl.biz/docs/standard-checkout/completing-integration/keeping-system-updated/

## Webhook Secret (uses access_secret)

- The SDK uses your `access_secret` to verify webhook signatures in `X-Nimbbl-Signature`; no separate webhook secret is required.
- Signature calculation: `HMAC-SHA256(payload, access_secret)`.
- Keep the secret out of logs/config, prefer env vars; always reject invalid signatures.

## Setup

1. **Deploy webhook handler**: Create a publicly accessible HTTPS endpoint
2. **Configure webhook URL**: Set webhook URL in Nimbbl Dashboard or contact support@nimbbl.tech
3. **Verify signature**: Always verify webhook signature before processing

## Methods

### 1. verifyWebhook

Verify webhook signature to ensure it's from Nimbbl.

**Method Signature:**
```php
public function verifyWebhook($payload, $signature, $secret)
```

**Parameters:**
- `$payload` (string): Raw webhook payload (JSON string)
- `$signature` (string): Signature from `X-Nimbbl-Signature` header
- `$secret` (string): Access secret (from Nimbbl dashboard)

**Returns:**
- `bool`: `true` if signature is valid, `false` otherwise

**Example:**
```php
use Nimbbl\Api\Webhook;

$webhook = new Webhook();

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_NIMBBL_SIGNATURE'] ?? '';
$secret = 'your_access_secret';

$isValid = $webhook->verifyWebhook($payload, $signature, $secret);

if (!$isValid) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Process webhook
```

---

### 2. parseWebhookEvent

Parse webhook payload into an array.

**Method Signature:**
```php
public function parseWebhookEvent($payload)
```

**Parameters:**
- `$payload` (string): Raw webhook payload (JSON string)

**Returns:**
- `array|null`: Parsed webhook event or `null` if parsing fails

**Example:**
```php
$payload = file_get_contents('php://input');
$eventData = $webhook->parseWebhookEvent($payload);

if ($eventData === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$eventType = $eventData['event_type'] ?? null;
$orderId = $eventData['nimbbl_order_id'] ?? null;
$transactionId = $eventData['nimbbl_transaction_id'] ?? null;
```

---

### 3. verifyAndParse

Verify signature and parse webhook in one call.

**Method Signature:**
```php
public function verifyAndParse($payload, $signature, $secret)
```

**Parameters:**
- `$payload` (string): Raw webhook payload
- `$signature` (string): Signature from header
- `$secret` (string): Access secret

**Returns:**
- `array|null`: Parsed event or `null` if verification fails

**Example:**
```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_NIMBBL_SIGNATURE'] ?? '';
$secret = 'your_access_secret';

$eventData = $webhook->verifyAndParse($payload, $signature, $secret);

if ($eventData === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Verification failed']);
    exit;
}

// Process event
$eventType = $eventData['event_type'];
```

---

### 4. getSignatureFromHeaders

Extract signature from HTTP headers.

**Method Signature:**
```php
public function getSignatureFromHeaders($headers)
```

**Parameters:**
- `$headers` (array): HTTP headers array (e.g., `$_SERVER`)

**Returns:**
- `string|null`: Signature or `null` if not found

**Example:**
```php
$signature = $webhook->getSignatureFromHeaders($_SERVER);

if ($signature === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Signature header missing']);
    exit;
}
```

---

### 5. getPayloadFromInput

Get webhook payload from input stream.

**Method Signature:**
```php
public function getPayloadFromInput()
```

**Returns:**
- `string`: Raw payload from `php://input`

**Example:**
```php
$payload = $webhook->getPayloadFromInput();
```

---

## Complete Webhook Handler Example

```php
<?php
require_once 'vendor/autoload.php';

use Nimbbl\Api\Webhook;

// Initialize webhook handler
$webhook = new Webhook();

// Get payload and signature
$payload = $webhook->getPayloadFromInput();
$signature = $webhook->getSignatureFromHeaders($_SERVER);
$secret = 'your_access_secret'; // From config

// Verify and parse
$eventData = $webhook->verifyAndParse($payload, $signature, $secret);

if ($eventData === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Extract event details
$eventType = $eventData['event_type'] ?? null;
$orderId = $eventData['nimbbl_order_id'] ?? null;
$transactionId = $eventData['nimbbl_transaction_id'] ?? null;

// Process based on event type
switch ($eventType) {
    case 'payment.success':
        handlePaymentSuccess($orderId, $transactionId, $eventData);
        break;
        
    case 'payment.failed':
        handlePaymentFailed($orderId, $transactionId, $eventData);
        break;
        
    case 'refund.success':
        handleRefundSuccess($orderId, $transactionId, $eventData);
        break;
        
    default:
        // Unknown event type - log but don't fail
        error_log("Unknown webhook event: {$eventType}");
}

// Always return 200 OK
http_response_code(200);
echo json_encode(['status' => 'success']);

function handlePaymentSuccess($orderId, $transactionId, $eventData) {
    // Update order status in database
    // Send confirmation email
    // Fulfill order
    error_log("Payment successful for order: {$orderId}");
}

function handlePaymentFailed($orderId, $transactionId, $eventData) {
    // Update order status
    // Notify customer
    error_log("Payment failed for order: {$orderId}");
}

function handleRefundSuccess($orderId, $transactionId, $eventData) {
    // Update refund status
    // Process refund
    error_log("Refund successful for transaction: {$transactionId}");
}
```

---

## Webhook Events

### Payment Events

- `payment.success`: Payment completed successfully
- `payment.failed`: Payment failed
- `payment.pending`: Payment is pending
- `payment.reversing`: Payment is being reversed
- `payment.reversal_failed`: Payment reversal failed
- `payment.reversed`: Payment reversed successfully

### Refund Events

- `refund.success`: Refund completed successfully
- `refund.failed`: Refund failed
- `refund.pending`: Refund is pending

---

## Webhook Payload Structure

```json
{
    "event_type": "payment.success",
    "nimbbl_order_id": "o_4KQ3NzX4oO3PwYw2",
    "nimbbl_transaction_id": "t_abc123xyz",
    "status": "success",
    "amount": 1000.00,
    "currency": "INR",
    "payment_mode_code": "net_banking",
    "timestamp": "2024-01-01T12:00:00Z",
    "metadata": {}
}
```

---

## Using WebhookEvent Model

The SDK provides a `WebhookEvent` model for easier webhook handling:

```php
use Nimbbl\Api\Model\WebhookEvent;

$eventData = $webhook->verifyAndParse($payload, $signature, $secret);
$webhookEvent = new WebhookEvent($eventData);

echo "Event Type: " . $webhookEvent->event . "\n";
echo "Order ID: " . $webhookEvent->order_id . "\n";
echo "Transaction ID: " . $webhookEvent->transaction_id . "\n";
```

---

## Best Practices

1. **Always verify signature**: Never process webhooks without signature verification
2. **Return 200 quickly**: Return 200 OK within 15 seconds or webhook will be retried
3. **Handle idempotency**: Same webhook may be received multiple times - handle duplicates
4. **Process asynchronously**: Do heavy processing asynchronously, return 200 quickly
5. **Log everything**: Log all webhook events for debugging
6. **Handle errors gracefully**: Don't fail on unknown events, log and continue
7. **Use HTTPS**: Webhook URL must be HTTPS
8. **Validate event data**: Always validate required fields before processing

---

## Error Handling

```php
try {
    $eventData = $webhook->verifyAndParse($payload, $signature, $secret);
    
    if ($eventData === null) {
        throw new Exception('Webhook verification failed');
    }
    
    // Process event
    processWebhookEvent($eventData);
    
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log("Webhook error: " . $e->getMessage());
    
    // Return 200 to prevent retries for non-retryable errors
    // Or return 4xx/5xx for retryable errors
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
```

---

## Testing Webhooks

### Using ngrok (Local Testing)

```bash
# Install ngrok
# Start local server
php -S localhost:8000 webhook-handler.php

# Expose via ngrok
ngrok http 8000

# Use ngrok URL in Nimbbl dashboard
```

### Manual Testing

```php
// Test webhook handler locally
$testPayload = json_encode([
    'event_type' => 'payment.success',
    'nimbbl_order_id' => 'o_test123',
    'nimbbl_transaction_id' => 't_test123'
]);

// Generate signature (use same method as Nimbbl)
$signature = hash_hmac('sha256', $testPayload, $secret);

// Test verification
$isValid = $webhook->verifyWebhook($testPayload, $signature, $secret);
```

---

## Security Considerations

1. **Never expose secret**: Keep access secret secure
2. **Use HTTPS**: Always use HTTPS for webhook URLs
3. **Verify signature**: Always verify signature before processing
4. **Validate payload**: Validate payload structure before processing
5. **Rate limiting**: Implement rate limiting to prevent abuse
6. **IP whitelisting**: Consider IP whitelisting if possible

---

## Related Documentation

- [Payments API](./PAYMENTS.md) - Process payments
- [Refunds API](./REFUNDS.md) - Process refunds
- [Transactions API](./TRANSACTIONS.md) - Check transaction status



