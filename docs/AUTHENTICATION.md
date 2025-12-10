# Authentication API Documentation

The Authentication API allows you to generate and refresh authentication tokens for API access.

## Overview

- **Token Type**: Merchant Token (generated from access_key and access_secret)
- **Base URL**: `/api/v3/token`
- **API Documentation**: https://nimbbl.biz/docs/api-reference/generate-token-v-3/

## Methods

### 1. generateToken

Generate a new authentication token using access key and secret.

**Method Signature:**
```php
public function generateToken($attributes = null)
```

**Parameters:**
- `$attributes` (array|null): Optional attributes:
  - `access_key` (string, optional): Access key (if not provided, uses value from Api initialization)
  - `access_secret` (string, optional): Access secret (if not provided, uses value from Api initialization)
  - If `null`, uses credentials from Api initialization

**Returns:**
- `array`: Token response containing:
  - `token`: Authentication token (use this for API calls)
  - `expires_at`: Token expiration timestamp
  - `token_type`: Token type (usually 'Bearer')

**Example:**
```php
use Nimbbl\Api\Api;

$api = new Api($accessKey, $accessSecret, $baseUrl, $apiVersion);

// Generate token using credentials from Api initialization
$tokenResponse = $api->auth()->generateToken();

if (!isset($tokenResponse['error'])) {
    $merchantToken = $tokenResponse['token'];
    $expiresAt = $tokenResponse['expires_at'];
    
    echo "Token generated successfully!\n";
    echo "Token: {$merchantToken}\n";
    echo "Expires at: {$expiresAt}\n";
    
    // Use this token for API calls that require merchant token
    // e.g., Transaction Enquiry, Refunds
} else {
    echo "Error: " . $tokenResponse['error']['nimbbl_merchant_message'] . "\n";
}
```

**Example - With Custom Credentials:**
```php
// Generate token with custom credentials
$tokenResponse = $api->auth()->generateToken([
    'access_key' => 'custom_access_key',
    'access_secret' => 'custom_access_secret'
]);
```

---

### 2. refreshToken

Refresh an authentication token using a refresh token.

**Method Signature:**
```php
public function refreshToken($refreshToken, $token = null)
```

**Parameters:**
- `$refreshToken` (string): Refresh token (obtained from order creation response)
- `$token` (string|null): Bearer token for authentication (required)

**Returns:**
- `array`: Token response containing:
  - `token`: New authentication token
  - `token_expiration`: Token expiration timestamp

**Example:**
```php
// Get refresh token from order creation response
$order = $api->orders()->createOrder($orderData, $merchantToken);
$refreshToken = $order['refresh_token'] ?? null;

if ($refreshToken) {
    // Refresh token using refresh token
    $tokenResponse = $api->auth()->refreshToken($refreshToken, $merchantToken);
    
    if (!isset($tokenResponse['error'])) {
        $newToken = $tokenResponse['token'];
        echo "Token refreshed successfully!\n";
        echo "New Token: {$newToken}\n";
    }
}
```

---

## Token Types

The Nimbbl SDK uses two types of tokens:

### 1. Merchant Token
- **Generated from**: `access_key` and `access_secret`
- **Method**: `generateToken()`
- **Used for**:
  - Transaction Enquiry
  - Refund operations
  - Initial order creation

### 2. Order Token
- **Obtained from**: Order creation response (`$order['token']`)
- **Used for**:
  - Getting order details
  - Payment operations
  - Payment link operations
  - Address operations
  - Checkout utilities

---

## Complete Authentication Flow

```php
// Step 1: Initialize API
$api = new Api($accessKey, $accessSecret, $baseUrl, $apiVersion);

// Step 2: Generate merchant token
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

// Step 3: Create order (uses merchant token initially)
$order = $api->orders()->createOrder($orderData, $merchantToken);

// Step 4: Extract order token from order response
$orderToken = $order['token'];

// Step 5: Use order token for subsequent operations
$orderDetails = $api->orders()->getOrderById($order['order_id'], $orderToken);
$payment = $api->payments()->initiatePayment($paymentData, $orderToken);
```

---

## Token Caching

The SDK automatically caches tokens to avoid unnecessary API calls:

```php
// First call - generates and caches token
$token1 = $api->auth()->generateToken();
$merchantToken1 = $token1['token'];

// Second call - uses cached token (if not expired)
$token2 = $api->auth()->generateToken();
$merchantToken2 = $token2['token'];

// Both tokens are the same (if cached token is still valid)
```

To get cached token:
```php
use Nimbbl\Api\Request;

$cachedToken = Request::getCachedToken();
if ($cachedToken) {
    echo "Cached token available: {$cachedToken}\n";
} else {
    echo "No cached token found.\n";
}
```

---

## Token Expiration

Tokens have expiration times. Always check token expiration:

```php
$tokenResponse = $api->auth()->generateToken();
$expiresAt = $tokenResponse['expires_at'];

// Check if token is expired
$expiresTimestamp = strtotime($expiresAt);
$currentTimestamp = time();

if ($currentTimestamp >= $expiresTimestamp) {
    // Token expired - generate new token
    $tokenResponse = $api->auth()->generateToken();
    $merchantToken = $tokenResponse['token'];
} else {
    $merchantToken = $tokenResponse['token'];
}
```

---

## Use Cases

### 1. Generate Token for Transaction Enquiry
```php
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

$transaction = $api->transactions()->transactionEnquiry([
    'transaction_id' => 't_abc123xyz'
], $merchantToken);
```

### 2. Generate Token for Refund
```php
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

$refund = $api->refunds()->initiateRefund([
    'transaction_id' => 't_abc123xyz',
    'comment' => 'Customer requested refund'
], $merchantToken);
```

### 3. Refresh Order Token
```php
// Get refresh token from order
$order = $api->orders()->createOrder($orderData, $merchantToken);
$refreshToken = $order['refresh_token'];

// Refresh token when needed
$newTokenResponse = $api->auth()->refreshToken($refreshToken, $merchantToken);
$newOrderToken = $newTokenResponse['token'];
```

---

## Error Codes

Common error codes you may encounter:

- `AUTHENTICATION_FAILED`: Invalid access key or secret
- `INVALID_CREDENTIALS`: Credentials are missing or invalid
- `TOKEN_EXPIRED`: Token has expired
- `INVALID_REFRESH_TOKEN`: Refresh token is invalid or expired

---

## Best Practices

1. **Store tokens securely**: Never expose tokens in client-side code or logs
2. **Handle expiration**: Always check token expiration and refresh when needed
3. **Use appropriate token type**: Use merchant token for merchant operations, order token for order operations
4. **Cache tokens**: Let the SDK cache tokens to avoid unnecessary API calls
5. **Regenerate on error**: If you get authentication errors, regenerate the token
6. **Don't hardcode tokens**: Always generate tokens dynamically
7. **Use refresh tokens**: Use refresh tokens to extend session without re-authenticating

---

## Security Considerations

1. **Never log tokens**: Avoid logging tokens in production
2. **Use HTTPS**: Always use HTTPS for API calls
3. **Rotate credentials**: Regularly rotate access keys and secrets
4. **Store secrets securely**: Store access secrets in environment variables or secure vaults
5. **Validate tokens**: Always validate token expiration before use

---

## Related Documentation

- [Orders API](./ORDERS.md) - Create orders (uses merchant token initially)
- [Transactions API](./TRANSACTIONS.md) - Transaction enquiry (uses merchant token)
- [Refunds API](./REFUNDS.md) - Process refunds (uses merchant token)



