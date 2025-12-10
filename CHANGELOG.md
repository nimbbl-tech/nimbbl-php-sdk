# Changelog

All notable changes to the Nimbbl PHP SDK are documented here. This release is the first of the current refactored SDK line.

## [3.6.9] - 2024-12-09

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
