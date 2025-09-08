# Nimbbl SDK Enhanced Logging System

## Overview

The Nimbbl SDK has been enhanced with a comprehensive logging system that ensures all logs are always printed and visible in nginx error.log. This system provides multiple output methods and detailed debugging information.

## Features

### 1. Multiple Log Outputs
- **PHP Error Log**: All logs are written to PHP error log (appears in nginx error.log)
- **Custom Log File**: Dedicated log file at `logs/nimbbl_debug.log`
- **Console Output**: Logs are also printed to stdout when running in CLI mode

### 2. Enhanced Log Format
- **Timestamps**: All logs include precise timestamps
- **Log Levels**: INFO, DEBUG, ERROR levels for better categorization
- **Component Tags**: Each log entry is tagged with the component name (NimbblUtil, NimbblApi, etc.)
- **Detailed Context**: Request/response details, parameters, and error information

### 3. Comprehensive Coverage
- **API Requests**: Full request/response logging with headers and bodies
- **Signature Verification**: Detailed logging of payment signature verification process
- **Error Handling**: Complete error tracking with stack traces
- **Token Generation**: Authentication token generation and validation logs

## Log Files

### 1. Custom Debug Log
**Location**: `logs/nimbbl_debug.log`

This file contains all Nimbbl-related logs in a structured format:
```
[2024-01-15 10:30:45] [INFO] [NimbblApi] getSecret START
[2024-01-15 10:30:45] [INFO] [NimbblApi] getSecret END - returning masked secret: test****1234
[2024-01-15 10:30:46] [DEBUG] [NimbblRequest] request HEADERS: Array(...)
[2024-01-15 10:30:47] [INFO] [NimbblUtil] verifyPaymentSignature START - orderAmount: 100.00
```

### 2. Nginx Error Log
**Location**: `/var/log/nginx/error.log` (or your configured nginx error log)

All Nimbbl logs also appear here for easy monitoring with nginx.

## Monitoring Tools

### 1. Log Monitor Script
A dedicated monitoring script is provided: `log_monitor.php`

#### Usage Examples:

```bash
# Show last 50 lines from all log sources
php log_monitor.php

# Follow logs in real-time
php log_monitor.php --follow

# Show last 100 lines
php log_monitor.php --lines=100

# Monitor specific log file
php log_monitor.php --file=/path/to/custom.log

# Monitor nginx error log only
php log_monitor.php --nginx
```

### 2. Command Line Monitoring

```bash
# Monitor nginx error log for Nimbbl entries
tail -f /var/log/nginx/error.log | grep -i nimbbl

# Monitor custom log file
tail -f logs/nimbbl_debug.log

# Show recent Nimbbl logs from nginx
grep -i nimbbl /var/log/nginx/error.log | tail -50
```

## Log Levels

### INFO
- Method entry/exit points
- Successful operations
- Important state changes

### DEBUG
- Request/response details
- Parameter values
- Internal processing steps

### ERROR
- Exception details
- API errors
- Validation failures

## Enhanced Components

### 1. NimbblLogger.php (Core Logger)
- Centralized singleton logger used across the SDK: `Nimbbl\Api\NimbblLogger::getInstance()->log($message, $level, $component)`
- Outputs to three sinks simultaneously:
  - PHP error log (visible in nginx error.log)
  - Custom file: `logs/nimbbl_debug.log`
  - Stdout when running under CLI
- Log format: `[YYYY-MM-DD HH:MM:SS] [LEVEL] [Component] Message`

Example:
```
[2024-01-15 10:30:45] [INFO] [NimbblSDK] Initialization complete
```

### 2. NimbblRequest.php (HTTP Layer)
- Captures full request lifecycle for all SDK API calls.
- Key logs:
  - START/END markers with method and relative/absolute URLs
  - Prepared headers (safely printable), request body for POST
  - HTTP response status, headers, and body
  - JSON-decoded response and success/failure markers
  - Token generation call details (`generateToken()`)
  - cURL SSL options applied (`setCurlSslOpts`)
  - Error pathways via `checkErrors()`, `processError()`, `throwServerError()`

Example (request excerpt):
```
[2024-01-15 10:30:46] [INFO] [NimbblRequest] request START - method: POST, url: v3/create-order
[2024-01-15 10:30:46] [DEBUG] [NimbblRequest] request HEADERS: Array(...)
[2024-01-15 10:30:46] [DEBUG] [NimbblRequest] request BODY: {"amount_before_tax":100,"..."}
[2024-01-15 10:30:47] [INFO] [NimbblRequest] request RESPONSE - status: 200
```

### 3. NimbblApi.php (Configuration/Context)
- Constructor logs SDK setup with masked keys and selected base URL/API version.
- `setHeader()` logs additions to global request headers.
- `getBaseUrl()`, `getAPIVersion()`, `getKey()`, `getSecret()` log read access (keys/secrets masked).
- `getTokenEndpoint()` and `getFullUrl()` log constructed URLs for traceability.

Example:
```
[2024-01-15 10:30:45] [DEBUG] [NimbblApi] __construct START - key: test****, url: default
[2024-01-15 10:30:45] [INFO]  [NimbblApi] __construct END - baseUrl: https://api.nimbbl.tech/api/, apiVersion: v3
```

### 4. NimbblUtil.php (Signature & Utilities)
- `verifyPaymentSignature()` logs inputs, computed signature string, and final verification result.
- Signature v3 support with canonical payload: `invoice_id|nimbbl_transaction_id|amount|currency|status|transaction_type`.
- `formatAmount()` logs normalization of amounts to two decimals.
- `verifySignature()` logs payload and attributes (line breaks sanitized) and whether comparison succeeded.

Example:
```
[2024-01-15 10:30:48] [INFO]  [NimbblUtil] verifyPaymentSignature START - orderAmount: 100.00
[2024-01-15 10:30:48] [DEBUG] [NimbblUtil] signature_string v3 generated: inv_001|o_abc|100.00|INR|success|payment
[2024-01-15 10:30:48] [INFO]  [NimbblUtil] verifyPaymentSignature END - Result: SUCCESS
```

### 5. NimbblOrder.php (Orders)
- `create()` logs endpoint resolution, headers/body preparation, raw HTTP response status/body, and parsed JSON.
- Detects whether `token` is present at top-level or inside `order`, logs which pattern matched.
- All errors/exceptions are captured with stack traces to aid debugging.
- `retrieveOne()`/`retrieveMany()` log input parameters, counts, and success markers.

Example:
```
[2024-01-15 10:30:46] [DEBUG] [NimbblOrder] create PREPARED - endpoint: v3/create-order, fullUrl: https://api.nimbbl.tech/api/v3/create-order
[2024-01-15 10:30:47] [DEBUG] [NimbblOrder] create RAW JSON RESPONSE: {"token":"tok_123","order":{...}}
[2024-01-15 10:30:47] [DEBUG] [NimbblOrder] create SUCCESS - token found in response
```

### 6. NimbblRefund.php (Refunds)
- `initiateRefund()` logs start, request payload, raw API response, and errors (with Nimbbl error codes if present).
- `retrieveOne()`/`retrieveMany()` helpers log lookup and list responses (avoid using legacy "fetch" endpoints in new flows).

Example:
```
[2024-01-15 10:31:10] [INFO]  [php] ... NimbblRefund::initiateRefund START
[2024-01-15 10:31:11] [INFO]  [php] ... NimbblRefund::initiateRefund API RESPONSE: Array(...)
```

### 7. NimbblSegment.php (Analytics Hooks)
- `identify()` and `track()` send events to Segment with basic auth and JSON payloads.
- Logs START/END and error messages; leverages same cURL SSL hooks for consistency.

Example:
```
[2024-01-15 10:31:30] [INFO]  [php] ... NimbblSegment::track START
[2024-01-15 10:31:31] [INFO]  [php] ... NimbblSegment::track END
```

### 8. log_monitor.php (Operational Utility)
- Monitors custom log file and nginx error log simultaneously.
- Supports:
  - `--follow` (tail -f behavior)
  - `--lines=N` (last N lines)
  - `--file=path` (custom file)
  - `--nginx` (nginx error log only)
- Filters nginx stream to show only Nimbbl-related entries.

Example:
```
php log_monitor.php --follow
php log_monitor.php --lines=100
php log_monitor.php --file=/var/log/custom.log
```

## Configuration

### PHP Error Log Configuration
Ensure your PHP configuration has proper error logging:

```ini
; php.ini
log_errors = On
error_log = /var/log/php_errors.log
error_reporting = E_ALL
```

### Nginx Configuration
Make sure nginx is configured to capture PHP errors:

```nginx
# nginx.conf
error_log /var/log/nginx/error.log;
```

## Troubleshooting

### 1. Logs Not Appearing in Nginx Error Log
- Check PHP error log configuration
- Verify nginx error log path
- Ensure proper file permissions

### 2. Custom Log File Not Created
- Check directory permissions for `logs/` folder
- Verify PHP has write access
- Check disk space

### 3. Missing Log Entries
- Verify log levels are appropriate
- Check for PHP errors preventing execution
- Ensure logging functions are being called

## Best Practices

### 1. Production Environment
- Set appropriate log levels (INFO/ERROR only)
- Implement log rotation
- Monitor log file sizes

### 2. Development Environment
- Use DEBUG level for detailed information
- Monitor logs in real-time during testing
- Keep log files for debugging sessions

### 3. Security Considerations
- Sensitive data (keys, secrets) are masked in logs
- Log files should have restricted access
- Consider log encryption for sensitive environments

## Example Log Output

```
[2024-01-15 10:30:45] [INFO] [NimbblApi] __construct START - key: test****, url: default
[2024-01-15 10:30:45] [INFO] [NimbblApi] __construct END - baseUrl: https://api.nimbbl.tech/api/, apiVersion: v3
[2024-01-15 10:30:46] [INFO] [NimbblRequest] request START - method: POST, url: v3/create-order
[2024-01-15 10:30:46] [DEBUG] [NimbblRequest] request HEADERS: Array([Content-Type] => application/json, [Authorization] => Bearer ****)
[2024-01-15 10:30:46] [DEBUG] [NimbblRequest] request BODY: {"amount":100,"currency":"INR"}
[2024-01-15 10:30:47] [INFO] [NimbblRequest] request RESPONSE - status: 200
[2024-01-15 10:30:47] [DEBUG] [NimbblRequest] response BODY: {"order":{"id":"order_123","token":"token_456"}}
[2024-01-15 10:30:47] [INFO] [NimbblRequest] request END - SUCCESS
[2024-01-15 10:30:48] [INFO] [NimbblUtil] verifyPaymentSignature START - orderAmount: 100.00
[2024-01-15 10:30:48] [INFO] [NimbblUtil] verifyPaymentSignature END - Result: SUCCESS
```

This enhanced logging system ensures you have complete visibility into all Nimbbl SDK operations and can easily debug any issues that arise. 