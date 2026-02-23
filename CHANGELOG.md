# Changelog

All notable changes to the Nimbbl PHP SDK are documented here. This release is the first of the current refactored SDK line.

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
