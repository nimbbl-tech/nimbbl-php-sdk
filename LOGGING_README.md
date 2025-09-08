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

### 1. NimbblUtil.php
- Payment signature verification logging
- Amount formatting details
- Hash comparison results

### 2. NimbblApi.php
- API configuration logging
- Token and secret management
- URL construction details

### 3. NimbblRequest.php
- HTTP request/response logging
- Header management
- Error processing

### 4. NimbblOrder.php
- Order creation and retrieval
- API response parsing
- Error handling

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