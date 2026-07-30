# Changelog

All notable changes to the Nimbbl PHP SDK are documented here. This release is the first of the current refactored SDK line.

## [4.1.0] - 2026-07-27

### Added
- **Pre-Authorization (Capture / Void)**: `payments()->capture([...])` and `payments()->void([...])` for `capture_mode=manual` sub-merchants — capture the held funds or release the hold on an `authorized` transaction (`POST /v3/capture`, `POST /v3/void`). Both route through `EncryptedPayloadHelper::preparePayload()` so they honour optional request encryption. Example: `example/capture-void-examples.php`.
- **v4 signed-envelope webhook & callback verification**: `SignatureVerifier::verifyWebhook($rawBody, $secret)` and `verifyCallback($rawBody, $secret)` operate on the **raw** body and select handling from the payload's `version` field — the source of truth. `version == "v4"` → HMAC-SHA256 envelope verification over the inner JSON (`verifyEnvelopeSignature()`, constant-time via `hash_equals`), or AES-GCM decryption for encrypted payloads; **absent** `version` → legacy per-field handling. Strict contract: v4 always carries `version`; no `version` means legacy. A commented extension point is in place for a future v5.
- **`WebhookEvents`** constants class — canonical `event_type` wire values (payment, pre-auth capture/void/authorized, refund, payment-link), mirroring the backend dispatcher enum; `SignatureVerifier` routes refund/payment-link signature formats off the `WebhookEvents::REFUND_EVENTS` / `PAYMENT_LINK_EVENTS` groups instead of inline literals.
- `JsonKeys` entries for checkout wrappers (`globalCloseCheckoutModal` / `globalHandleCheckoutResponse`).
- **Auth-failure retry**: `Request` transparently refreshes the merchant token and retries once on a 401/403 (guarded so token generation itself never loops), ported from the .NET SDK.
- **Minimal v4 callback + Transaction Enquiry flow**: v4 checkout callbacks are minimal (`checkout_status`, `reason`, `nimbbl_order_id`, `nimbbl_transaction_id`, `invoice_id`, `retry`, `message`) and are **not** authoritative. Verify the signature, then take `nimbbl_transaction_id` and call `transactions()->transactionEnquiry()` for the real `payment_status`. The PHP sample app implements this end-to-end for **both v4 and legacy** callbacks, with pre-auth `payment_authorized` surfaced as a distinct "authorized (capture pending)" state.
- **Signature key by source (no fallback)**: v4 **webhooks** are signed under `signature`; v4 **callbacks** (checkout and payment) under `nimbbl_signature` — confirmed against live traffic + the backend `_sign_v4_payload`. Encrypted payloads carry no signature (AES-GCM decryption authenticates); no redundant HMAC is performed.
- **Structured log context**: added `EventType` and `InvoiceID` fields; `SignatureVerifier` stamps `SubMerchantID`/`OrderID`/`InvoiceID`/`TransactionID`/`EventType` on verified webhook/callback lines, and every successful API call logs a concise `API request successful` line at INFO (even with DEBUG off). Encrypted webhooks/callbacks log both the received ciphertext and the decrypted payload (PII-masked).
- **Tests**: pre-auth payload E2E (`PreAuthE2ETest`) modeled byte-for-byte on the backend `webhook-payload-generator`; `SignatureVerifierTest` covers v4 valid/tampered/wrong-secret, encrypted v4 webhook/callback, legacy-no-`version` regression, per-source signature keys; plus `NimbblClientTest` (URL resolution + static-config guard), `RequestExceptionMappingTest` (status→exception), and `EncryptedPayloadHelperTest`. Offline suite: **116 tests**.

### Changed
- **Encrypted v4 webhook/callback verification**: when the body carries a top-level `encrypted_response` (the whole signed v4 envelope is AES-GCM-encrypted, so `version` lives inside the ciphertext), `verifyWebhook()`/`verifyCallback()` now **decrypt first**, then unwrap to the event. Successful GCM decryption is the authentication (128-bit tag) — no HMAC/signature check is required, and a `signature` field may be absent. Verified against a live `payment_authorized` webhook. Plaintext v4 still requires the envelope HMAC.
- **Masking — response-side PII keys**: `CentralMasker` now also masks `name`, `mobile`, `card_holder`, and `state` (webhook/callback payloads use these short forms, distinct from the request-side `first_name`/`mobile_number`/`card_holder_name`). Fixes unmasked customer name/phone/cardholder/state in response and decrypted-payload logs.
- **Log context — webhook/callback traceability**: added `InvoiceID` and `EventType` to the structured log context, and `SignatureVerifier` now stamps `SubMerchantID`/`OrderID`/`InvoiceID`/`TransactionID`/`EventType` (with `APITag` = `Webhook`/`Callback`) on verified/decrypted webhook & callback log lines — previously these lines carried only `[APITag:SignatureVerifier]` and couldn't be correlated to an order/txn/event.
- **Callback/checkout unwrapping**: `verifyCallback()` handles both `globalHandleCheckoutResponse` (additive) and `globalCloseCheckoutModal` wrappers.
- **Log masking aligned with the backend `nimbbl_api` central masker**: masking is unconditional at all log levels; token/access_secret/email/VPA/card/CVV/expiry and city-area rules applied consistently in headers and message bodies. Removed duplicate DEBUG "Raw JSON" log blocks.
- **`tests/` reorganized to mirror `src/`**: `Common/`, `Log/`, `RestClient/`, `Services/`, and a cross-cutting `Integration/` group (sub-namespaced under `Nimbbl\Tests\*`).
- **Docs**: README, `docs/MERCHANT_INTEGRATION.md`, and `tests/README.md` updated for capture/void and the raw-body `verifyWebhook`/`verifyCallback` API.

### Removed
- **Legacy non-Composer autoloader (`Nimbbl.php`) and the bundled `libs/Requests-1.8/`** — redundant with the `rmccue/requests` Composer dependency (the `Requests` class loads from `vendor/`). Install via Composer / `vendor/autoload.php`.

### Packaging
- **composer.json**: declared `ext-openssl`; moved the test PSR-4 map to `autoload-dev`; relaxed `rmccue/requests` to `^1.8`; added `keywords`/`homepage`/`support`. Added `.gitattributes` `export-ignore` so `tests/`, `example/`, `docs`-adjacent dev files, and CI config stay out of the published dist.

### Deprecated
- Legacy `SignatureVerifier::verifySignature(array)` and `verifyCallbackSignature(array)` remain for backward compatibility but are superseded by `verifyWebhook()` / `verifyCallback()` (raw body), which additionally verify v4 signed envelopes. Existing merchants on the legacy methods are unaffected.

### Compatibility
- No breaking changes. Legacy (no-`version`) webhooks/callbacks verify exactly as before; v4 is opt-in via the backend and detected automatically.

## [4.0.1] - 2026-02-23

### Added
- **Webhook & callback**: `PayloadHelperUtils::parseResponse()` to parse and decrypt webhook payloads (plain JSON or `encrypted_response`). `SignatureVerifier::verifySignature()` and `verifyCallbackSignature()` for payment, refund, and payment-link events.
- **Optional request payload encryption**: When `NimbblClient` is constructed with `encrypt_payload` enabled, Order, Refund, Transaction, and Checkout Utilities (list banks/wallets) requests are encrypted via `EncryptedPayloadHelper`.
- **Shared encryption helper**: `EncryptedPayloadHelper::preparePayload()` centralizes encrypted payload preparation for Order, Refund, Transaction, and CheckoutUtilities services.

### Changed
- **NimbblException**: All call sites updated to use correct constructor argument order `(message, errorCode, requestId, httpStatusCode, errorData, previous)` (fixes misassigned errorCode/httpStatusCode in services and Request).
- **Request logging**: Caller context is resolved in a single place; `logInfoWithSdkCallerContext()` now uses `resolveSdkCallerContext()` (removed duplicated backtrace logic). Fallback for non-SDK callers preserved.
- **MERCHANT_INTEGRATION.md**: Rewritten for current SDK — `NimbblClient`, `orders()->createOrder()`, `refunds()->initiateRefund()`, `transactions()->transactionEnquiry()`, webhook flow with `PayloadHelperUtils` and `SignatureVerifier`, optional API base URL (default production), base URL documented as host-only (e.g. `https://api.nimbbl.tech`) with `/api/v3` appended.
- **.gitignore**: Added `example/config.php`, `logs/`, `*.code-workspace`, `PUBLISHING.md`, `publish-sdk.sh`, `.DS_Store` to keep local config and workspace files untracked.

### Fixed
- Encryption error exceptions in Order, Refund, Transaction, and CheckoutUtilities now pass the correct `errorCode` and `httpStatusCode` to `NimbblException` and attach the original exception as `previous`.
- Request token/auth `NimbblException` calls now pass `requestId` as null where appropriate and use correct parameter order.

---

## [4.0.0] - 2025-12-10

### Highlights
- **PHP 7.4+** minimum, updated docs and examples accordingly.
- **Unified method naming**: `{action}{Resource}` across all clients (Orders, Payments, Payment Links, Addresses, Checkout Utilities, Refunds, Transactions, Auth, Webhook).
- **Centralized request handling**: `Request` builds HTTP queries; all methods pass data arrays; JSON decode fallback returns `response` key.
- **Always-on INFO logs**: Every API request/response logged at INFO (stdout in CLI, `error_log` on web), with masking of sensitive data.
- **Masked logging**: Central masking for tokens, secrets, card data in requests/responses.
- **Constants split**: `ApiConstants`, `SdkConstants`, `ErrorMessages` (replaces monolithic constants; removed legacy Error dir).
- **Cleanup**: Removed unused interfaces, `universalRequest`, legacy Error classes, and structured logging (`APIContext`).
- **Bundled Requests fallback**: Updated to Requests 1.8.0 at `libs/Requests-1.8`; Composer uses `rmccue/requests` 1.8.0.

### Added
- Comprehensive docs per feature in `docs/` and logging updates reflecting SDK name/version in log format.
- Webhook tests and auth tests; example CLI hints (e.g., default bank code).

### Changed
- Renamed `src/Core` to `src/Common`; classmap updated.
- Logger format: `[timestamp][SDK_NAME SDK_VERSION][LEVEL][module:line][function]: message`.
- Token management clarified: merchant token vs order token; errors returned as JSON from `Request`.

### Removed
- Legacy interfaces and unused methods; `universalRequest`; `src/Error` directory; structured logging helpers; old Requests-1.7 bundle.

---
