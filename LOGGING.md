# Logging

The SDK emits logs in a uniform format and always prints INFO lines for every API request/response (stdout in CLI, `error_log` on web) with sensitive data masked.

```
[timestamp][SDK_NAME SDK_VERSION][LEVEL][module:line][function]: message
```

- Logger: `Nimbbl\Api\Logger::getInstance($logFile = null)`
- Convenience methods: `info|debug|error|warning|critical|exception($message, $exception = null)`
- Outputs:
  - File: plain text (`logs/nimbbl_debug.log` by default or `Api::getLogFile()`)
  - Web (non-CLI): also sent to `error_log`
  - CLI: also printed to stdout
- Masking: Request/response bodies and URLs are masked for tokens, secrets, card data, etc.

Example:
```php
use Nimbbl\Api\Logger;

$logger = Logger::getInstance();
$logger->info('Preparing order request');
```
