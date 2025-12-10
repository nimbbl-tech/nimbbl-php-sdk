# Logging

The SDK provides lightweight logging with masked data and a consistent format. INFO logs for every API request/response are always emitted (stdout in CLI, `error_log` on web) even if other logging is disabled, to aid debugging.

## Format
```
[timestamp][SDK_NAME SDK_VERSION][LEVEL][module:line][function]: message
```

## Outputs
- File: `logs/nimbbl_debug.log` by default (or via `Api::setLogFile()`)
- Web (non-CLI): also written to `error_log`
- CLI: also printed to stdout

## Usage
```php
use Nimbbl\Api\Logger;

$logger = Logger::getInstance();
$logger->info('Request prepared');
$logger->error('Something failed');
```

## Masking
Request/response bodies and URLs are masked for tokens, secrets, card data, etc., before logging.

## Always-on API INFO logs
The Request client logs INFO lines for every API call (request + response) to ensure operability visibility; these are emitted regardless of log level configuration.
