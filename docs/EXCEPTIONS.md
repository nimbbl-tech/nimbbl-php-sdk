# Exception Handling Documentation

The Nimbbl PHP SDK provides a comprehensive exception hierarchy for better error handling.

## Overview

- **Exception Hierarchy**: Specific exception types for different error scenarios
- **Error Codes**: Standardized error codes for programmatic handling
- **HTTP Status Codes**: HTTP status codes included in exceptions
- **Detailed Messages**: Clear error messages for debugging

## Exception Classes

### 1. NimbblException

Base exception class for all Nimbbl SDK exceptions.

**Class**: `Nimbbl\Api\Exception\NimbblException`

**Properties:**
- `$message`: Error message
- `$errorCode`: Nimbbl error code
- `$httpStatusCode`: HTTP status code
- `$errorResponse`: Full error response array

**Example:**
```php
use Nimbbl\Api\Exception\NimbblException;

try {
    // API call
} catch (NimbblException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getErrorCode() . "\n";
    echo "HTTP Status: " . $e->getHttpStatusCode() . "\n";
}
```

---

### 2. ApiException

Base exception for API-related errors.

**Class**: `Nimbbl\Api\Exception\ApiException`

**Extends**: `NimbblException`

**Example:**
```php
use Nimbbl\Api\Exception\ApiException;

try {
    $order = $api->orders()->createOrder($orderData, $token);
} catch (ApiException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
}
```

---

### 3. AuthenticationException

Thrown when authentication fails.

**Class**: `Nimbbl\Api\Exception\AuthenticationException`

**Extends**: `ApiException`

**HTTP Status**: 401

**Example:**
```php
use Nimbbl\Api\Exception\AuthenticationException;

try {
    $token = $api->auth()->generateToken();
} catch (AuthenticationException $e) {
    echo "Authentication failed. Check your credentials.\n";
    echo "Error: " . $e->getMessage() . "\n";
}
```

---

### 4. BadRequestException

Thrown for invalid request parameters.

**Class**: `Nimbbl\Api\Exception\BadRequestException`

**Extends**: `ApiException`

**HTTP Status**: 400

**Example:**
```php
use Nimbbl\Api\Exception\BadRequestException;

try {
    $order = $api->orders()->createOrder($invalidOrderData, $token);
} catch (BadRequestException $e) {
    echo "Invalid request: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getErrorCode() . "\n";
    
    // Get full error response
    $errorResponse = $e->getErrorResponse();
    if (isset($errorResponse['nimbbl_merchant_message'])) {
        echo "Merchant Message: " . $errorResponse['nimbbl_merchant_message'] . "\n";
    }
}
```

---

### 5. NotFoundException

Thrown when a resource is not found.

**Class**: `Nimbbl\Api\Exception\NotFoundException`

**Extends**: `ApiException`

**HTTP Status**: 404

**Example:**
```php
use Nimbbl\Api\Exception\NotFoundException;

try {
    $order = $api->orders()->getOrderById('invalid_order_id', $token);
} catch (NotFoundException $e) {
    echo "Order not found: " . $e->getMessage() . "\n";
}
```

---

### 6. RateLimitException

Thrown when rate limit is exceeded.

**Class**: `Nimbbl\Api\Exception\RateLimitException`

**Extends**: `ApiException`

**HTTP Status**: 429

**Example:**
```php
use Nimbbl\Api\Exception\RateLimitException;

try {
    $order = $api->orders()->createOrder($orderData, $token);
} catch (RateLimitException $e) {
    echo "Rate limit exceeded. Please wait before retrying.\n";
    echo "Retry after: " . ($e->getRetryAfter() ?? 'unknown') . " seconds\n";
}
```

---

### 7. ServerException

Thrown for server-side errors.

**Class**: `Nimbbl\Api\Exception\ServerException`

**Extends**: `ApiException`

**HTTP Status**: 500, 502, 503, 504

**Example:**
```php
use Nimbbl\Api\Exception\ServerException;

try {
    $order = $api->orders()->createOrder($orderData, $token);
} catch (ServerException $e) {
    echo "Server error occurred. Please try again later.\n";
    echo "Error: " . $e->getMessage() . "\n";
}
```

---

## Complete Exception Handling Example

```php
<?php
require_once 'vendor/autoload.php';

use Nimbbl\Api\Api;
use Nimbbl\Api\Exception\NimbblException;
use Nimbbl\Api\Exception\AuthenticationException;
use Nimbbl\Api\Exception\BadRequestException;
use Nimbbl\Api\Exception\NotFoundException;
use Nimbbl\Api\Exception\RateLimitException;
use Nimbbl\Api\Exception\ServerException;
use Nimbbl\Api\Exception\ApiException;

$api = new Api($accessKey, $accessSecret, $baseUrl, $apiVersion);

try {
    // Generate token
    $tokenResponse = $api->auth()->generateToken();
    $merchantToken = $tokenResponse['token'];
    
    // Create order
    $order = $api->orders()->createOrder($orderData, $merchantToken);
    $orderToken = $order['token'];
    
    // Initiate payment
    $payment = $api->payments()->initiatePayment($paymentData, $orderToken);
    
} catch (AuthenticationException $e) {
    // Handle authentication errors
    error_log("Authentication failed: " . $e->getMessage());
    echo "Please check your API credentials.\n";
    
} catch (BadRequestException $e) {
    // Handle invalid request errors
    $errorResponse = $e->getErrorResponse();
    $merchantMessage = $errorResponse['nimbbl_merchant_message'] ?? $e->getMessage();
    $consumerMessage = $errorResponse['nimbbl_consumer_message'] ?? '';
    
    error_log("Bad request: " . $merchantMessage);
    echo "Error: " . ($consumerMessage ?: $merchantMessage) . "\n";
    echo "Error Code: " . $e->getErrorCode() . "\n";
    
} catch (NotFoundException $e) {
    // Handle not found errors
    error_log("Resource not found: " . $e->getMessage());
    echo "The requested resource was not found.\n";
    
} catch (RateLimitException $e) {
    // Handle rate limit errors
    error_log("Rate limit exceeded: " . $e->getMessage());
    $retryAfter = $e->getRetryAfter() ?? 60;
    echo "Rate limit exceeded. Please retry after {$retryAfter} seconds.\n";
    
} catch (ServerException $e) {
    // Handle server errors
    error_log("Server error: " . $e->getMessage());
    echo "A server error occurred. Please try again later.\n";
    
} catch (ApiException $e) {
    // Handle other API errors
    error_log("API error: " . $e->getMessage());
    echo "An API error occurred: " . $e->getMessage() . "\n";
    
} catch (NimbblException $e) {
    // Handle SDK errors
    error_log("SDK error: " . $e->getMessage());
    echo "An error occurred: " . $e->getMessage() . "\n";
    
} catch (\Exception $e) {
    // Handle any other errors
    error_log("Unexpected error: " . $e->getMessage());
    echo "An unexpected error occurred.\n";
}
```

---

## Error Response Structure

API errors typically return the following structure:

```json
{
    "error": {
        "nimbbl_error_code": "BANK_MISSING",
        "nimbbl_merchant_message": "The parameter bank_code is mandatory for the payment_mode in your request.",
        "nimbbl_consumer_message": "Currently this bank is facing some issue, Please try with other bank to continue"
    }
}
```

**Accessing Error Details:**
```php
try {
    $payment = $api->payments()->initiatePayment($paymentData, $token);
} catch (BadRequestException $e) {
    $errorResponse = $e->getErrorResponse();
    
    $errorCode = $e->getErrorCode(); // or $errorResponse['nimbbl_error_code']
    $merchantMessage = $errorResponse['nimbbl_merchant_message'] ?? '';
    $consumerMessage = $errorResponse['nimbbl_consumer_message'] ?? '';
    
    // Show consumer-friendly message
    echo $consumerMessage ?: $merchantMessage;
    
    // Log merchant message for debugging
    error_log("Error Code: {$errorCode}, Message: {$merchantMessage}");
}
```

---

## Common Error Codes

### Authentication Errors
- `AUTHENTICATION_FAILED`: Invalid credentials
- `TOKEN_EXPIRED`: Token has expired
- `INVALID_TOKEN`: Invalid token provided

### Request Errors
- `INVALID_REQUEST`: Invalid request parameters
- `BANK_MISSING`: Bank code required for net banking
- `INVALID_PAYMENT_MODE`: Invalid payment mode
- `INVALID_CARD`: Invalid card details
- `OTP_REQUIRED`: OTP required to complete payment
- `OTP_INVALID`: Invalid OTP provided

### Resource Errors
- `ORDER_NOT_FOUND`: Order not found
- `TRANSACTION_NOT_FOUND`: Transaction not found
- `INVOICE_NOT_FOUND`: Invoice not found
- `ADDRESS_NOT_FOUND`: Address not found

### Payment Errors
- `PAYMENT_FAILED`: Payment processing failed
- `REFUND_NOT_ALLOWED`: Refund not allowed
- `REFUND_ALREADY_PROCESSED`: Refund already processed

---

## Best Practices

1. **Catch Specific Exceptions**: Catch specific exception types for better error handling
2. **Log Errors**: Always log errors for debugging
3. **Show User-Friendly Messages**: Use `nimbbl_consumer_message` for user-facing errors
4. **Handle Rate Limits**: Implement retry logic for rate limit errors
5. **Retry on Server Errors**: Retry on server errors (5xx) with exponential backoff
6. **Validate Before API Calls**: Validate input before making API calls to avoid BadRequestException
7. **Check Error Codes**: Use error codes for programmatic error handling

---

## Retry Logic Example

```php
function makeApiCallWithRetry($apiCall, $maxRetries = 3) {
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            return $apiCall();
        } catch (RateLimitException $e) {
            $retryAfter = $e->getRetryAfter() ?? 60;
            sleep($retryAfter);
            $attempt++;
        } catch (ServerException $e) {
            if ($attempt < $maxRetries - 1) {
                sleep(pow(2, $attempt)); // Exponential backoff
                $attempt++;
            } else {
                throw $e;
            }
        } catch (ApiException $e) {
            // Don't retry on client errors
            throw $e;
        }
    }
    
    throw new \Exception("Max retries exceeded");
}

// Usage
try {
    $order = makeApiCallWithRetry(function() use ($api, $orderData, $token) {
        return $api->orders()->createOrder($orderData, $token);
    });
} catch (\Exception $e) {
    echo "Failed after retries: " . $e->getMessage() . "\n";
}
```

---

## Related Documentation

- [Orders API](./ORDERS.md) - Order operations
- [Payments API](./PAYMENTS.md) - Payment operations
- [Authentication API](./AUTHENTICATION.md) - Token management

