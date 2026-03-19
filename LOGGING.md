# Logging

The SDK emits logs in a uniform format and always prints INFO lines for every API request/response (stdout in CLI, `error_log` on web) with sensitive data masked.

```
[timestamp][SDK_NAME SDK_VERSION][LEVEL][module:line][function]: message
```

- Logger: `Nimbbl\Api\Log\Logger::getInstance($logFile = null)`
- Convenience methods: `info|debug|error|warning|critical|exception($message, $exception = null, $subMerchantId = null, $orderId = null, $transactionId = null, $apiVersion = null, $apiTag = null, $uri = null, $statusCode = null)`
- Safer option for context-heavy logs: pass a context array as the third argument (instead of long positional chains), e.g. `['subMerchantId' => 'sm_987', 'orderId' => 'order_001', 'apiTag' => 'Order', 'uri' => '/api/v3/create-order', 'statusCode' => 201]`.
- `log()` is an internal/private method; use only the convenience methods above.
- Outputs:
  - File: plain text (`logs/nimbbl_debug.log` by default or `Api::getLogFile()`)
  - Web (non-CLI): also sent to `error_log`
  - CLI: also printed to stdout
- Masking: Request/response bodies and URLs are masked for tokens, secrets, card data, etc.

Example:
```php
use Nimbbl\Api\Log\Logger;

$logger = Logger::getInstance();
$logger->info(
  'Preparing order request',
  null,
  'sm_987',
  'order_001',
  'txn_001',
  'v3',
  'CreateOrder',
  '/api/v3/create-order',
  201
);
```

Context fields are prefixed to message in this order (when provided):

`[APIVersion:...][APITag:...][URI:...][StatusCode:...][SubMerchantID:...][OrderID:...][TransactionID:...] message`

If `apiVersion` is not provided, logger tries to resolve it from `NimbblClient::getAPIVersion()`.
If `apiTag` is not provided, logger falls back to component/function context.
