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

## Signature Header (Optional but Recommended)

**The `X-Nimbbl-Signature` header is optional per Nimbbl documentation, but recommended for security.**

- If the header is present, the webhook handler will verify the signature before processing.
- If the header is missing, the webhook will be processed without signature verification (not recommended for production).
- The header should contain the HMAC-SHA256 signature of the webhook payload.
- Ensure your proxy/CDN (e.g., Cloudflare) preserves the `X-Nimbbl-Signature` header if you want to use signature verification.

**Note:** The official Nimbbl documentation (https://nimbbl.biz/docs/standard-checkout/completing-integration/keeping-system-updated/) does not explicitly require the `X-Nimbbl-Signature` header. The signature can also be found in the payload as `nimbbl_signature`. This SDK implementation supports both approaches - if the header is present, it will be verified; if missing, the webhook will be processed without verification.

## Setup

1. **Deploy webhook handler**: Create a publicly accessible HTTPS endpoint
2. **Configure webhook URL**: Set webhook URL in Nimbbl Dashboard or contact support@nimbbl.tech
3. **Verify signature**: Always verify webhook signature before processing

## Methods

### 1. PayloadHelperUtils::parse()

Parse and unwrap webhook payload, handling encryption, unwrapping, and `globalHandleCheckoutResponse` events automatically.

**Method Signature:**
```php
public static function parse(string $payload, string $secret): array
```

**Parameters:**
- `$payload` (string): Raw webhook payload (JSON string)
- `$secret` (string): Access secret (from Nimbbl dashboard)

**Returns:**
- `array`: Parsed webhook event array

**Features:**
- Automatically detects and decrypts `encrypted_response` at various levels
- Handles `callback` object unwrapping
- Unwraps `globalHandleCheckoutResponse` events
- Throws exception on parsing errors

**Example:**
```php
use Nimbbl\Api\Common\PayloadHelperUtils;

$payload = file_get_contents('php://input');
$accessSecret = 'your_access_secret';

try {
    $eventData = PayloadHelperUtils::parse($payload, $accessSecret);
    // Process event
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Parse error: ' . $e->getMessage()]);
    exit;
}
```

---

### 2. PayloadHelperUtils::parseResponse()

Parse payment callback response, which can be either base64-encoded or a regular JSON string. Automatically detects the format and handles both cases.

**Method Signature:**
```php
public static function parseResponse(string $response, string $secret): array
```

**Parameters:**
- `$response` (string): Base64-encoded JSON response or regular JSON string
- `$secret` (string): Access secret (from Nimbbl dashboard)

**Returns:**
- `array`: Parsed response array

**Features:**
- Automatically detects base64 vs JSON format
- Handles encryption, unwrapping, and `globalHandleCheckoutResponse` events
- Used for payment callbacks from popup/redirect checkout

**Example:**
```php
use Nimbbl\Api\Common\PayloadHelperUtils;

// Handle GET callback with base64-encoded response
$responseParam = $_GET['response'] ?? '';
$parsed = PayloadHelperUtils::parseResponse($responseParam, $accessSecret);

// Handle POST callback with JSON
$raw = file_get_contents('php://input');
$parsed = PayloadHelperUtils::parseResponse($raw, $accessSecret);
```

---

### 3. SignatureVerifier::verifySignature()

Verify webhook signature. Routes to the appropriate verification method based on webhook event type.

**Method Signature:**
```php
public function verifySignature(array $attributes, string $secretKey = null): array
```

**Parameters:**
- `$attributes` (array): Parsed webhook event data
- `$secretKey` (string|null): Access secret (optional, uses configured secret if not provided)

**Returns:**
- `array`: Result array with `success` (bool) and `message` (string)

**Example:**
```php
use Nimbbl\Api\Common\SignatureVerifier;

$verifier = new SignatureVerifier();
$result = $verifier->verifySignature($eventData, $accessSecret);

if (!$result['success']) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}
```

---

### 4. SignatureVerifier::verifyCallbackSignature()

Verify signature for payment callbacks from popup/redirect checkout.

**Method Signature:**
```php
public function verifyCallbackSignature(array $payload, string $secretKey = null): array
```

**Parameters:**
- `$payload` (array): Parsed callback payload
- `$secretKey` (string|null): Access secret (optional)

**Returns:**
- `array`: Result array with `success` (bool) and `message` (string)

**Example:**
```php
use Nimbbl\Api\Common\PayloadHelperUtils;
use Nimbbl\Api\Common\SignatureVerifier;

$parsed = PayloadHelperUtils::parseResponse($responseParam, $accessSecret);
$verifier = new SignatureVerifier();
$result = $verifier->verifyCallbackSignature($parsed, $accessSecret);

if ($result['success']) {
    // Process payment
}
```

**Note:** Signature verification reads `signature`, `signature_version`, and `transaction_id` **only from the transaction object** (no fallbacks). This ensures data integrity and matches the authoritative source.

---

## Complete Webhook Handler Example

```php
<?php
require_once 'vendor/autoload.php';

use Nimbbl\Api\Common\PayloadHelperUtils;
use Nimbbl\Api\Common\SignatureVerifier;
use Nimbbl\Api\Common\JsonKeys;

// Get webhook payload
$payload = file_get_contents('php://input');
$accessSecret = 'your_access_secret'; // From config

if (empty($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Webhook payload is empty']);
    exit;
}

// Parse and unwrap the payload using PayloadHelperUtils
// This handles encryption, unwrapping, and globalHandleCheckoutResponse automatically
$eventData = PayloadHelperUtils::parse($payload, $accessSecret);

// Verify webhook signature
$verifier = new SignatureVerifier();
$result = $verifier->verifySignature($eventData, $accessSecret);

if (!$result['success']) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature: ' . ($result['message'] ?? 'Unknown error')]);
    exit;
}

// Extract event details
$eventType = $eventData[JsonKeys::EVENT_TYPE] ?? null;
$orderId = $eventData[JsonKeys::NIMBBL_ORDER_ID] ?? $eventData[JsonKeys::ORDER_ID] ?? null;
// Extract transaction_id only from transaction object
$transactionId = $eventData[JsonKeys::TRANSACTION][JsonKeys::TRANSACTION_ID] ?? null;

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
2. **Require signature header**: The `X-Nimbbl-Signature` header is mandatory - reject requests without it
3. **Return 200 quickly**: Return 200 OK within 15 seconds or webhook will be retried
4. **Handle idempotency**: Same webhook may be received multiple times - handle duplicates
5. **Process asynchronously**: Do heavy processing asynchronously, return 200 quickly
6. **Log everything**: Log all webhook events for debugging
7. **Handle errors gracefully**: Don't fail on unknown events, log and continue
8. **Use HTTPS**: Webhook URL must be HTTPS
9. **Validate event data**: Always validate required fields before processing
10. **Preserve headers**: Ensure your proxy/CDN preserves the `X-Nimbbl-Signature` header

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



