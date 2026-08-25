# Nimbbl PHP SDK - Merchant Integration (v3)

This guide describes the end-to-end server-side integration using the Nimbbl PHP SDK with API v3. It covers:

- Creating an order (v3)
- Validating payment response (signature verification)
- Webhook handling (including encrypted payloads)
- Processing refunds (v3)
- Transaction enquiry (v3)

Notes:

- Always use API version v3 (base URL includes `/api/v3`).
- Always verify webhook/callback signatures before fulfilling orders.
- Ignore deprecated or "fetch" endpoints during integration.

## Integration Flow at a Glance

1. Create order (server) → return token to frontend.
2. Customer pays via Nimbbl Standard Checkout (frontend).
3. Nimbbl redirects to your callback URL and/or POSTs to your webhook (server).
4. Parse payload (decrypt if needed), verify signature (server) → fulfill order if valid.
5. Optional: Refunds (server).
6. Optional: Transaction enquiry / status checks (server).

## Prerequisites

- PHP 7.4+
- Composer
- Nimbbl Access Key and Access Secret

## Installation

```bash
composer require nimbbl/nimbbl-sdk
```

## Initialization

Use `NimbblClient` with your credentials. The API base URL (third parameter) is **optional**. When omitted, the SDK uses the default production URL (`https://api.nimbbl.tech`). When provided, use the base URL up to the host (e.g. `https://api.nimbbl.tech`) and append `/api/v3` to form the full endpoint.

```php
require 'vendor/autoload.php';

use Nimbbl\Api\RestClient\NimbblClient;

$accessKey    = 'your_access_key';
$accessSecret = 'your_access_secret';
$apiHost      = 'https://api.nimbbl.tech';  // optional: omit (pass null) to use default
$apiEndpoint  = $apiHost . '/api/v3';

$api = new NimbblClient(
    $accessKey,
    $accessSecret,
    $apiEndpoint,  // optional: pass null to use default (https://api.nimbbl.tech)
    null,          // log file path (optional)
    false,         // encrypt_payload (optional, default false)
    false,         // debug_logging (optional)
    false          // override_log_filename (optional)
);

// Or use default production URL by omitting the endpoint:
// $api = new NimbblClient($accessKey, $accessSecret, null, null, false, false, false);
```

Optional: enable request payload encryption and debug logging:

```php
$api = new NimbblClient(
    $accessKey,
    $accessSecret,
    $apiEndpoint,
    __DIR__ . '/logs/nimbbl.log',
    true,   // encrypt_payload
    true    // debug_logging
);
```

### Token generation

The SDK auto-generates a short-lived merchant token when you call order, refund, or transaction APIs without passing a token. If you need the token explicitly (e.g. for multiple calls):

```php
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];
// Use $merchantToken when calling refunds()->initiateRefund() or transactions()->transactionEnquiry()
```

All order-creation and payment flows can omit the token; the SDK attaches `Authorization: Bearer <token>` automatically.

## Create Order (v3)

Endpoint: `POST /v3/create-order`

Example:

```php
use Nimbbl\Api\RestClient\NimbblClient;

$api = new NimbblClient($accessKey, $accessSecret, 'https://api.nimbbl.tech/api/v3');

$orderData = [
    'amount_before_tax' => 100.00,
    'tax'               => 0.00,
    'total_amount'      => 100.00,
    'currency'          => 'INR',
    'invoice_id'        => 'your-unique-invoice-id-001',
    'user' => [
        'email'         => 'customer@example.com',
        'first_name'    => 'John',
        'mobile_number' => '9999999999',
        'country_code'  => '+91'
    ]
];

$order = $api->orders()->createOrder($orderData);
```

Response is an array. On success it contains the payment token and order details; on error it contains an `error` key:

```php
if (!empty($order['error'])) {
    // Handle error: $order['error']
} else {
    $token = $order['token'];  // Pass to frontend for Standard Checkout
    $orderId = $order['order_id'] ?? $order['nimbbl_order_id'] ?? null;
}
```

Common request body fields:

- `amount_before_tax`, `tax`, `total_amount` (numbers, 2 decimals)
- `currency` (e.g. `"INR"`)
- `invoice_id` (string, unique per order)
- `user` (object: `email`, `first_name`, `last_name`, `mobile_number`, `country_code`)

Use the returned `token` in your frontend to open Nimbbl Standard Checkout.

## Validating Payment Response (Callback / Webhook)

On completion, Nimbbl sends transaction details to your backend (redirect callback or webhook). Always verify the signature before fulfilling the order.

### Webhook (server-to-server POST)

Webhook payloads may be plain JSON, an encrypted envelope, or a v4 signed envelope. Pass the **raw** body to `SignatureVerifier::verifyWebhook()` — it reads the `version` field (source of truth) and selects the handling: `version == "v4"` → signed-envelope verification (or AES-GCM decryption for encrypted payloads); absent `version` → legacy per-field verification. It returns the parsed payload, so you don't parse separately.

Example webhook handler outline:

```php
use Nimbbl\Api\Common\SignatureVerifier;

$rawBody = file_get_contents('php://input');
$secret  = $yourAccessSecret;

$verifier = new SignatureVerifier();
$result   = $verifier->verifyWebhook($rawBody, $secret);
// => ['success' => bool, 'version' => 'v4'|'legacy', 'event_type' => ..., 'payload' => [...]]

if (!$result['success']) {
    http_response_code(401);
    echo json_encode(['error' => 'Signature verification failed']);
    exit;
}

$payload   = $result['payload'];
$eventType = $result['event_type'] ?? null;
// Handle: payment_success/_failed, refund_success/_failed,
//         and pre-auth: payment_authorized, capture_success, void_success, authorization_expired.
// Implement idempotency (same webhook may be received multiple times).
// Must return 200 within 15 seconds.

http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Webhook processed']);
```

Supported webhook events include: `payment_success`, `payment_failed`, `payment_reversing`, `payment_reversal_failed`, `payment_reversed`, `refund_success`, `refund_failed`, `refund_pending`, and the pre-auth lifecycle `payment_authorized`, `capture_success`, `capture_failed`, `void_success`, `authorization_expired`.

> The legacy `verifySignature(array)` / `verifyCallbackSignature(array)` (operate on a pre-parsed array) remain for backward compatibility, but new integrations should use the raw-body `verifyWebhook()` / `verifyCallback()` so v4 signed envelopes are verified.

### Callback (redirect with payment context)

For redirect/callback responses, pass the **raw** callback body (the base64 `response` query param, or the POST body in popup mode) to `verifyCallback()`. It unwraps the checkout wrappers (`globalHandleCheckoutResponse` / `globalCloseCheckoutModal`), verifies the signature (v4 callbacks are signed under `nimbbl_signature`; encrypted v4 is authenticated by decryption; legacy v3 by the per-field HMAC), and returns the payload:

```php
$verifier = new SignatureVerifier();
$rawBody  = $_GET['response'] ?? file_get_contents('php://input');
$result   = $verifier->verifyCallback($rawBody, $secret);
// => ['success' => bool, 'version' => 'v4'|'legacy', 'event_type' => ..., 'payload' => [...]]
if (!$result['success']) { /* signature/decryption failed — reject */ }
$payload = $result['payload'];
```

**The callback is not the source of truth — confirm via Transaction Enquiry (or the webhook) before fulfilling.** How you read the result differs by version:

**v4 (minimal callback):** carries only `checkout_status`, `reason`, `nimbbl_order_id`, `nimbbl_transaction_id`, `invoice_id`, `retry`, `message` — no transaction/amount block. Take `nimbbl_transaction_id` and enquire for the authoritative status:

```php
$txnId = $payload['nimbbl_transaction_id'] ?? null;
if ($txnId) {
    $enq    = $api->transactions()->transactionEnquiry(['transaction_id' => $txnId]);
    $status = $enq['transaction'][0]['payment_status'] ?? null;   // succeeded | pending | failed
}
// reason === 'payment_authorized' -> pre-auth: funds HELD, not captured. Do NOT fulfil; capture first.
```

**Legacy v3 (full callback):** the transaction is inline — read `transaction.status` / `transaction.transaction_id` directly, but still reconcile via webhook or Transaction Enquiry before fulfilment.

Recommendation: implement both callback (for UX) and webhook (for fulfilment). Treat the webhook / Transaction Enquiry as the source of truth; use the callback to render an interim result and always re-verify server-side.

## Processing Refunds (v3)

Initiate a refund with the Refunds API. You can pass a merchant token or omit it (SDK will auto-generate).

Example:

```php
$refundData = [
    'transaction_id' => 'o_XXXXXXXXXXXX',
    // 'refund_amount' => 50.00,  // Optional partial refund
    // 'comment' => 'Customer request',
    // 'refund_request_id' => 'unique-id-for-idempotency'
];

$refund = $api->refunds()->initiateRefund($refundData);
```

You can use `invoice_id` instead of `transaction_id` where the API supports it. Response is an array; check for `$refund['error']` and enforce idempotency (e.g. with `refund_request_id`).

## Transaction Enquiry (v3)

To confirm status (e.g. when callbacks/webhooks are delayed):

```php
$data = [
    'invoice_id' => 'your-unique-invoice-id-001',
    // or 'transaction_id' => 'o_XXXXXXXXXXXX'
];

$result = $api->transactions()->transactionEnquiry($data);
```

Use webhooks as source of truth; use enquiry to reconcile or when needed. Avoid aggressive polling.

## Error Handling

- Check for `error` in response arrays and handle non-2xx responses.
- The SDK throws exceptions (e.g. `Nimbbl\Api\Exception\BadRequestException`, `AuthenticationException`) for API errors; catch and log appropriately.
- On signature verification failure, do not fulfill the order; log the attempt.
- Retry network or 5xx errors according to your policy.

Typical error shape:

```json
{
  "error": {
    "nimbbl_error_code": "BAD_REQUEST_ERROR",
    "nimbbl_merchant_message": "..."
  }
}
```

## Optional: Request payload encryption

If your account uses encrypted request payloads, initialize the client with `encrypt_payload` set to `true`. The SDK will encrypt request bodies for create order, refund, transaction enquiry, and supported checkout utility APIs. Webhook responses can be encrypted; `PayloadHelperUtils::parseResponse()` decrypts them when you pass the access secret.

## Logging

The SDK logs requests/responses (with sensitive data masked unless debug logging is enabled). Configure a log file path in the client constructor. See `LOGGING.md` in the repo for details.

## Test Checklist

- Create order returns a token; frontend can open Standard Checkout.
- Callback and/or webhook received; parse (and decrypt if needed) and verify signature successfully.
- Fulfillment only after signature success; idempotency applied.
- Refund initiation works; response or error handled.
- Transaction enquiry returns expected status when used.

## Production Tips

- Store credentials securely; never log raw secrets.
- Use HTTPS for all endpoints and verify certificates.
- Enforce idempotency for callbacks, webhooks, and refunds.
- Return HTTP 200 for webhook within 15 seconds to avoid retries.

## Further Documentation

- **Examples:** `example/` in the SDK (order, refund, webhook-handler, etc.).
- **Webhook example:** `example/webhook-handler.php` for a full parse → verify → event-handling flow.
- **API docs:** [Nimbbl API Reference](https://nimbbl.biz/docs/category/api-reference/).
- **Checkout integration:** [Standard Checkout – Completing integration](https://nimbbl.biz/docs/standard-checkout/completing-integration/keeping-system-updated/).
