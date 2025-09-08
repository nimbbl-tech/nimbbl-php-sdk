# Nimbbl PHP SDK - Merchant Integration (v3)

This guide describes the end-to-end server-side integration using the Nimbbl PHP SDK with API v3 and signature version v3. It covers:

- Creating an order (v3)
- Completing the integration: validating the payment response (Signature v3)
- Processing refunds (v3)

Notes:
- Always use API version v3.
- Always validate with Signature v3 when present.
- Ignore all endpoints/APIs named "fetch" during integration.

## Integration Flow at a Glance

1) Create order (server) → return token to frontend
2) Customer pays via Nimbbl Standard Checkout (frontend)
3) Nimbbl redirects to your callback URL and/or posts to your webhook (server)
4) Verify Signature v3 (server) → fulfill order if valid
5) Optional: Refunds (server)
6) Optional: Enquiry/status checks (server)

## Prerequisites

- PHP 7.4+ recommended
- Composer
- Nimbbl Access Key and Access Secret

## Installation

```bash
composer require nimbbl/nimbbl-sdk
```

## Initialization

```php
require __DIR__.'/../Nimbbl.php';

use Nimbbl\Api\NimbblApi;

$accessKey = 'your_access_key';
$secretKey = 'your_access_secret';
$baseUrl   = 'https://api.nimbbl.tech/api/'; // Production base
$api       = new NimbblApi($accessKey, $secretKey, $baseUrl, 'v3');
```

### Token generation

The SDK auto-generates a short-lived token for each server request. If you need it explicitly:

```php
use Nimbbl\Api\NimbblRequest;

$req = new NimbblRequest();
$tokenArr = $req->generateToken();
// $tokenArr example: [ 'token' => '...', 'expires_in' => 900 ]
```

All SDK methods use this under the hood and attach `Authorization: Bearer <token>` automatically.

## Create Order (v3)

Endpoint: `POST /v3/create-order`

Example:

```php
use Nimbbl\Api\NimbblOrder;

$orderData = [
  'amount_before_tax' => 100.00,
  'tax'               => 0.00,
  'total_amount'      => 100.00,
  'currency'          => 'INR',
  'invoice_id'        => 'your-unique-invoice-id-001',
  'user' => [
    'email'        => 'customer@example.com',
    'first_name'   => 'John',
    'mobile_number'=> '9999999999'
  ]
];

$order = $api->order->create($orderData); // NimbblOrder instance

// Access top-level fields if present
$token = $order->token ?? ($order->attributes['token'] ?? null);
```

The response contains the payment token and order details. Render Nimbbl Standard Checkout on your frontend using the token.

Request headers (SDK-managed):
- `Authorization: Bearer <token>`
- `Content-Type: application/json`
- `User-Agent: Nimbbl/v1 PHPSDK/<sdk-version> PHP/<php-version>`

Common request body fields:
- `amount_before_tax` (number, 2 decimals)
- `tax` (number, 2 decimals)
- `total_amount` (number, 2 decimals)
- `currency` (string, e.g., "INR")
- `invoice_id` (string, unique per order)
- `user` (object: `email`, `first_name`, `mobile_number`)

Common response fields:
- `token` (string, used by frontend checkout)
- `order` (object, may include `id`, `invoice_id`, amounts, etc.)
- `error` (object), if present contains `nimbbl_error_code`, `message`, etc.

## Validating Payment Response (Signature v3)

On completion, Nimbbl sends transaction details to your backend (via redirect or webhook). Always verify the signature before fulfilling the order.

Signature v3 payload order:

```
invoice_id | nimbbl_transaction_id | transaction_amount | transaction_currency | status | transaction_type
```

- Compute HMAC SHA256 of the above pipe-separated string using your Access Secret.
- Compare with `transaction.signature` provided in the response.

Example v3 signature string:

```
<invoice_id>|<nimbbl_transaction_id>|<amount>|<currency>|<status>|<transaction_type>
```

Using the SDK helper:

```php
use Nimbbl\Api\NimbblUtil;

// Example payload structure you receive from Nimbbl callback/webhook
$attributes = [
  'order' => [
    'invoice_id' => 'your-unique-invoice-id-001',
  ],
  'nimbbl_transaction_id' => 'o_XXXXXXXXXXXX',
  'transaction' => [
    'transaction_amount'   => '100.00',
    'transaction_currency' => 'INR',
    'status'               => 'success',
    'transaction_type'     => 'payment',
    'signature'            => 'received_signature_here',
    'signature_version'    => 'v3',
  ],
];

$util = new NimbblUtil();
$isValid = $util->verifyPaymentSignature($attributes, 100.00);

if ($isValid) {
  // Mark order as paid, provision goods/services
} else {
  // Log and treat as invalid
}
```

Important:
- If `transaction.signature_version` is `v3`, the SDK constructs the v3 string automatically. Provide the full `$attributes` and the order amount you created.
- Ensure amount formats use two decimals (e.g., 100.00).

### Callback vs Webhook

- Callback: Customer browser is redirected to your return URL with payment context. Use it to show UI; always re-verify on server.
- Webhook: Server-to-server POST from Nimbbl to your endpoint with JSON body. Treat webhook as source of truth.

Recommended:
- Implement both. Use callback for UX, webhook for fulfillment.
- Webhook must return HTTP 200 on success; otherwise Nimbbl may retry (respect idempotency).

Minimal webhook handler outline:

```php
// pseudo-code
// Read JSON body
$payload = file_get_contents('php://input');
$attributes = json_decode($payload, true);

// Verify signature v3
$util = new Nimbbl\\Api\\NimbblUtil();
$valid = $util->verifyPaymentSignature($attributes, /* expected total amount */ 100.00);

if ($valid) {
  // idempotency: check if invoice_id already processed
  // fulfill order, persist transaction id, mark paid
  http_response_code(200);
  echo 'OK';
} else {
  // log and ignore
  http_response_code(400);
}
```

## Processing Refunds (v3)

- Initiate a refund by `POST /v3/refund`.
- You can refund by `transaction_id` (and optionally specify an `amount` for partial refunds).

Example:

```php
use Nimbbl\Api\NimbblRefund;

$refundInput = [
  'transaction_id' => 'o_XXXXXXXXXXXX',
  // 'amount' => 50.00, // Optional for partial refund
];

$refund = $api->refund->initiateRefund($refundInput); // NimbblRefund instance

if (!empty($refund->error)) {
  // Handle error
} else {
  // Persist refund details from $refund->attributes
}
```

Notes:
- Ignore deprecated or older "fetch" endpoints as per instruction.
- For audit and troubleshooting, the SDK logs to a file and PHP error log. See `LOGGING_README.md`.

Request body (typical):
- `transaction_id` (string, paid transaction id)
- `amount` (number, optional for partial refund)

Response (typical):
- Refund entity attributes (id, status, amounts)
- Or `error` with `nimbbl_error_code`

Validation:
- Ensure the refund amount ≤ captured amount and enforce idempotency on your side.

## Error Handling

- All API responses should be verified and errors handled gracefully.
- On signature failure, do not fulfill the order and log the attempt.
- Network or 5xx errors should be retried as per your policy.

Standard error shape (typical):
```json
{
  "error": {
    "nimbbl_error_code": "BAD_REQUEST_ERROR",
    "message": "..."
  }
}
```

The SDK logs details and throws `Nimbbl\Api\NimbblError` for non-2xx responses when using low-level helpers.

## Test Checklist

- Create order returns a token.
- Frontend completes payment and calls your backend callback.
- Backend verifies signature v3 successfully for valid payments.
- Refund initiation works and returns refund details.

## Enquiry (Status Check)

Use an enquiry endpoint to confirm the latest status if needed (e.g., when callbacks/webhooks are delayed). Example pattern with the SDK request helper:

```php
use Nimbbl\Api\NimbblRequest;

$req = new NimbblRequest();
$payload = [
  'invoice_id' => 'your-unique-invoice-id-001',
  // or 'transaction_id' => 'o_XXXXXXXXXXXX'
];

// Prefer v3 endpoint when available, e.g., 'v3/transaction-enquiry'
$res = $req->universalRequest('POST', 'v3/transaction-enquiry', $payload);

// Validate structure and act on status fields in $res
```

Guidelines:
- Prefer webhooks for source-of-truth; use enquiry to reconcile.
- Don’t poll aggressively—use backoff and only when necessary.

## Production Tips

- Store and mask credentials. Never log raw secrets.
- Enforce HTTPS endpoints and verify certificates.
- Implement idempotency on your server for callbacks and refunds.
