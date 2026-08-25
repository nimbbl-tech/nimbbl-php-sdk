# Nimbbl PHP SDK — Testing Guide (v4.1.0)

How to test the Nimbbl PHP SDK. Tests fall into two groups:

1. **Offline unit tests** — deterministic, no network, no credentials. Cover signature/webhook/callback verification, encryption, logging, token cache, and the pre-auth payload E2E.
2. **Live/integration tests** — make real API calls; need credentials in `example/config.php`.

### Layout

`tests/` mirrors `src/` — each test sits under the folder of the `src` module it exercises:

| Folder | Mirrors `src/` | Tests |
|--------|----------------|-------|
| `tests/Common/` | `src/Common/` | `CentralMaskerTest`, `EncryptionTest`, `EncryptedPayloadHelperTest`, `PayloadHelperUtilsTest`, `SignatureVerifierTest` |
| `tests/Log/` | `src/Log/` | `LoggerTest` |
| `tests/RestClient/` | `src/RestClient/` | `NimbblClientTest`, `RequestTokenCacheTest`, `RequestLogContextSanitizationTest`, `RequestEncrypted2xxErrorHandlingTest`, `RequestExceptionMappingTest` |
| `tests/Services/` | `src/Services/` | `AuthTest`, `OrderTest`, `PaymentTest`, `PaymentLinkTest`, `RefundTest`, `AddressesTest`, `CheckoutUtilitiesTest`, `TransactionTest` |
| `tests/Integration/` | (cross-cutting) | `E2ETest`, `PreAuthE2ETest`, and the live runner scripts (`live-api-suite.php`, `test-all-apis.php`, `run-all-tests.php`, …) |

---

## Setup

```bash
composer install
cp example/config.php.example example/config.php   # then edit credentials
```

`example/config.php` (live tests read this via `loadConfig()`):

```php
return [
    'access_key'    => 'your_access_key',
    'access_secret' => 'your_access_secret',
    'api_host'      => 'https://apipp.nimbbl.tech', // pp/UAT. Prod: https://api.nimbbl.tech
    'log_file'      => __DIR__ . '/../logs/nimbbl_debug.log',
];
```

> Webhook/callback signature verification uses `access_secret` automatically. Never commit `config.php`.

---

## 1. Offline unit tests (no credentials)

Run the whole offline suite (configured in `phpunit.xml.dist`):

```bash
vendor/bin/phpunit
```

Covers (112 tests):

| File | What it verifies |
|------|------------------|
| `SignatureVerifierTest.php` | v3 per-field signatures (payment/refund/link, **capture/void/authorized**); **v4 envelope** valid/tampered/wrong-secret; legacy-no-`version` regression; `verifyWebhook`/`verifyCallback`; event predicates |
| `PreAuthE2ETest.php` | **Pre-auth end-to-end** — `payment_authorized`, `capture_success`, `void_success` (incl. `authorization_expired`), `capture_failed`, tampered rejection, v4 checkout & payment callbacks, legacy v3 capture. Payloads modeled byte-for-byte on the backend `webhook-payload-generator` |
| `PayloadHelperUtilsTest.php` | Payload parsing/unwrapping — encrypted, `globalHandleCheckoutResponse`, **`globalCloseCheckoutModal`**, v4 base64 envelope, encrypted-inner |
| `EncryptionTest.php` | AES-GCM encrypt/decrypt |
| `EncryptedPayloadHelperTest.php` | Request-payload encryption on/off contract — pass-through when disabled, `{encrypted_payload}` envelope + round-trip when enabled |
| `CentralMaskerTest.php` | PII masking |
| `NimbblClientTest.php` | URL resolution (`getFullUrl`/`getTokenEndpoint`, default / combined `…/api/v3` / custom base, no double-version); static-config conflict guard; encrypt flag; `VERSION` |
| `RequestExceptionMappingTest.php` | HTTP status → exception mapping (401/403/400/422/404/429/5xx/unmapped) + error detail propagation |
| `LoggerTest.php`, `RequestLogContextSanitizationTest.php` | Structured logging + log sanitization |
| `RequestEncrypted2xxErrorHandlingTest.php` | Encrypted error envelope handling |
| `RequestTokenCacheTest.php` | Merchant token caching/expiry |

---

## 2. Live / integration tests (need credentials)

### Comprehensive runner

```bash
php tests/Integration/test-all-apis.php
```

Runs every SDK API against the configured environment and prints a PASS/FAIL/duration table.

### PHPUnit integration classes

These are real `TestCase` classes; run them individually (PHPUnit only runs the first file when several are passed as args). Cases that need data they don't have will **skip**, not fail.

```bash
vendor/bin/phpunit tests/Services/AuthTest.php
vendor/bin/phpunit tests/Services/OrderTest.php
vendor/bin/phpunit tests/Services/RefundTest.php
```

> Pre-auth **capture/void** are `Payment` methods; they're exercised live by `tests/Integration/E2ETest.php` (`testLiveCapture` / `testLiveVoid`, env-gated) and offline by `tests/Integration/PreAuthE2ETest.php` — see §3 below.

### Demo scripts (run standalone, not via phpunit)

`AddressesTest.php`, `PaymentTest.php`, `PaymentLinkTest.php`, `CheckoutUtilitiesTest.php`, `TransactionTest.php` are procedural demos that execute at file load — run them directly:

```bash
php tests/Services/AddressesTest.php
php tests/Services/CheckoutUtilitiesTest.php
```

> Do **not** run these via `vendor/bin/phpunit tests/` — their load-time calls abort PHPUnit discovery. They're not in `phpunit.xml.dist` for this reason.

---

## 3. Pre-Authorization (Capture / Void)

Pre-auth holds funds at checkout; you then **capture** (collect) or **void** (release).

- **Payload-level E2E (offline):** `PreAuthE2ETest.php` — already in the offline suite above.
- **Live capture/void:** needs a real transaction in the `authorized` state, from a sub-merchant with **`capture_mode=manual`** (Nimbbl enables this per sub-merchant; complete a test checkout to produce an `authorized` payment). Then:

```bash
# Capture an authorized transaction
NIMBBL_PREAUTH_TXN_ID=o_xxxx-yyyy NIMBBL_PREAUTH_ACTION=capture \
  vendor/bin/phpunit tests/Integration/PreAuthE2ETest.php --filter testLiveCaptureThenVoid

# Void a (different) authorized transaction
NIMBBL_PREAUTH_TXN_ID=o_aaaa-bbbb NIMBBL_PREAUTH_ACTION=void \
  vendor/bin/phpunit tests/Integration/PreAuthE2ETest.php --filter testLiveCaptureThenVoid
```

Capture is terminal — one transaction can be captured **or** voided, not both. Without `NIMBBL_PREAUTH_TXN_ID` the live case skips.

SDK usage:

```php
$api->payments()->capture(['transaction_id' => $txnId, 'comment' => 'Goods dispatched']);
$api->payments()->void(['transaction_id' => $txnId, 'comment' => 'Customer cancelled']);
```

---

## 4. Webhook & callback verification

The SDK picks handling from the payload's `version` field (source of truth): `version == "v4"` → signed-envelope handling; absent/`v1`/`v2`/`v3` → legacy per-field handling. Both are covered offline by `SignatureVerifierTest` and `PreAuthE2ETest`.

```php
use Nimbbl\Api\Common\SignatureVerifier;
$v = new SignatureVerifier();

$result = $v->verifyWebhook($rawBody, $accessSecret);   // webhooks
$result = $v->verifyCallback($rawBody, $accessSecret);  // payment + checkout callbacks
// => ['success' => bool, 'version' => 'v4'|'legacy', 'event_type' => ..., 'payload' => [...]]
```

Encrypted v4 payloads carry no signature — a successful AES-GCM decryption authenticates them.

See `example/webhook-handler.php` and `example/callback-handler.php`.

---

## What is / isn't testable server-side

| Area | Status | Notes |
|------|--------|-------|
| Auth, Create/Get Order, Transaction Enquiry, Refund init, **Capture, Void**, Payment Link (create/update/enquiry/actions) | Server-to-server | Use the **merchant token**. Payment Link needs the merchant token (not the order token). |
| list-of-banks, list-of-wallets, get-bin-data, validate-vpa | Works with order/Bearer token | |
| payment-modes, offers, initiate/complete payment, addresses | Need a **user-verified Bearer token** | Obtained via the consumer `resolve-user` → verify-OTP flow (OTP goes to a real device). The S2S SDK doesn't wrap that flow, so these can't be driven purely server-side. All Nimbbl APIs authorize via **Bearer token** (per the docs) — there is no separate header to set. |
| Refund / Capture / Void (live) | Need real transaction data | Refund needs a paid txn; capture/void need an `authorized` (manual-capture) txn. |

---

## Troubleshooting

- **"Config file not found"** — `cp example/config.php.example example/config.php` and add credentials.
- **401 / 403 on payment-modes/offers/addresses** — expected server-side; those need a user-verified Bearer token (see table above).
- **Class not found** — run `composer install`.
- **`vendor/bin/phpunit tests/` aborts mid-run** — you pulled in the procedural demo scripts; run the offline suite with plain `vendor/bin/phpunit`, and run demos individually with `php tests/Services/X.php`.

---

## Related

- [Main SDK README](../README.md)
- [Examples](../example/README.md)
- [API Documentation](https://nimbbl.biz/docs/api-reference/introduction/)
