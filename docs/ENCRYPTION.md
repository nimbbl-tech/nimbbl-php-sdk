# Encryption Documentation

The Encryption utility provides AES-GCM encryption/decryption for payloads and responses, primarily used for Standard Checkout integration.

## Overview

- **Purpose**: Encrypt/decrypt payloads for Standard Checkout integration
- **Algorithm**: AES-256-GCM
- **Use Case**: Decrypt responses from Standard Checkout when client forwards encrypted response to server
- **Documentation**: 
  - https://nimbbl.biz/docs/standard-checkout/completing-integration/
  - https://nimbbl.biz/docs/guides/encrypt-decrypt-payload/

## Usage

### Initialization

```php
use Nimbbl\Api\Encryption;

$encryption = new Encryption($accessSecret, $keyIterations = 1);
```

**Parameters:**
- `$accessSecret` (string, required): Access secret from Nimbbl dashboard
- `$keyIterations` (int, optional): Number of SHA256 iterations for key generation (default: 1)
  - Must match merchant settings in Nimbbl dashboard
  - Any mismatch will cause encryption/decryption to fail

---

### encrypt

Encrypt data using AES-GCM.

**Method Signature:**
```php
public function encrypt($data)
```

**Parameters:**
- `$data` (string|array): Data to encrypt (string, array, or JSON-encodable data)

**Returns:**
- `string`: Hex-encoded encrypted string

**Encrypted Payload Format:**
- First 16 bytes: Nonce (IV)
- Middle bytes: Encrypted payload
- Last 16 bytes: Authentication tag

**Example:**
```php
$encryption = new Encryption($accessSecret);

// Encrypt string
$plaintext = 'Hello, World!';
$encrypted = $encryption->encrypt($plaintext);
echo "Encrypted: {$encrypted}\n";

// Encrypt array (automatically JSON-encoded)
$data = [
    'order_id' => 'o_123',
    'amount' => 1000.00,
    'status' => 'success'
];
$encrypted = $encryption->encrypt($data);
```

---

### decrypt

Decrypt data using AES-GCM.

**Method Signature:**
```php
public function decrypt($encryptedData, $returnAsArray = false)
```

**Parameters:**
- `$encryptedData` (string): Hex-encoded encrypted string
- `$returnAsArray` (bool): If `true`, return decoded JSON as array; otherwise return string

**Returns:**
- `string|array`: Decrypted data

**Example:**
```php
$encryption = new Encryption($accessSecret);

// Decrypt to string
$encrypted = '3164351ca6195e9871cca9de3117cb8f...';
$decrypted = $encryption->decrypt($encrypted);
echo "Decrypted: {$decrypted}\n";

// Decrypt to array (if payload is JSON)
$decrypted = $encryption->decrypt($encrypted, true);
echo "Order ID: " . $decrypted['order_id'] . "\n";
echo "Status: " . $decrypted['status'] . "\n";
```

---

## Standard Checkout Integration

### Complete Flow

```php
use Nimbbl\Api\Encryption;
use Nimbbl\Api\Webhook;

// Step 1: Client forwards checkout response to server
// Client sends: { "encrypted_response": "3164351ca6195e9871cca9de3117cb8f..." }

// Step 2: Decrypt on server
$encryption = new Encryption($accessSecret);
$encryptedResponse = $_POST['encrypted_response'] ?? '';

$decrypted = $encryption->decrypt($encryptedResponse, true);

// Step 3: Validate and process
$status = $decrypted['status']; // 'success', 'failed', or 'pending'
$orderId = $decrypted['nimbbl_order_id'];
$transactionId = $decrypted['nimbbl_transaction_id'] ?? null;

if ($status === 'success') {
    // Payment successful - fulfill order
    fulfillOrder($orderId);
} elseif ($status === 'failed') {
    // Payment failed - notify customer
    notifyPaymentFailed($orderId);
}
```

---

## Example: Decrypting Checkout Response

```php
<?php
require_once 'vendor/autoload.php';

use Nimbbl\Api\Encryption;

$accessSecret = 'your_access_secret';
$encryption = new Encryption($accessSecret);

// Get encrypted response from client
$encryptedResponse = $_POST['encrypted_response'] ?? '';

if (empty($encryptedResponse)) {
    http_response_code(400);
    echo json_encode(['error' => 'Encrypted response required']);
    exit;
}

try {
    // Decrypt response
    $decrypted = $encryption->decrypt($encryptedResponse, true);
    
    // Extract payment details
    $status = $decrypted['status'] ?? null;
    $orderId = $decrypted['nimbbl_order_id'] ?? null;
    $transactionId = $decrypted['nimbbl_transaction_id'] ?? null;
    $amount = $decrypted['amount'] ?? null;
    
    // Process based on status
    switch ($status) {
        case 'success':
            // Payment successful
            updateOrderStatus($orderId, 'paid');
            sendConfirmationEmail($orderId);
            break;
            
        case 'failed':
            // Payment failed
            updateOrderStatus($orderId, 'payment_failed');
            notifyCustomer($orderId);
            break;
            
        case 'pending':
            // Payment pending
            updateOrderStatus($orderId, 'pending');
            break;
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'processed']);
    
} catch (\Exception $e) {
    error_log("Decryption error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Decryption failed']);
}
```

---

## Key Generation

The encryption key is generated from the access secret:

1. Remove `access_secret_` prefix from access secret
2. Generate SHA256 hash (with optional iterations)
3. Use resulting 32-byte key for AES-256-GCM

**Example:**
```php
$accessSecret = 'access_secret_abc123';
$keyIterations = 1;

// Key generation (internal)
$keyString = str_replace('access_secret_', '', $accessSecret); // 'abc123'
$byteKey = hash('sha256', $keyString, true); // 32-byte key
```

---

## Error Handling

```php
try {
    $encryption = new Encryption($accessSecret);
    $decrypted = $encryption->decrypt($encryptedData, true);
    
} catch (\Nimbbl\Api\Exception\NimbblException $e) {
    // Handle encryption-specific errors
    switch ($e->getErrorCode()) {
        case 'INVALID_HEX_STRING':
            echo "Invalid encrypted data format\n";
            break;
            
        case 'DECRYPTION_FAILED':
            echo "Decryption failed - key may be incorrect\n";
            break;
            
        case 'DECRYPTION_NOT_SUPPORTED':
            echo "AES-GCM not supported - check OpenSSL installation\n";
            break;
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

---

## Common Errors

### 1. Invalid Hex String
```
Error: Invalid hex string provided for decryption
```
**Solution**: Ensure encrypted data is a valid hex string

### 2. Decryption Failed
```
Error: Decryption failed. The encrypted data may be corrupted or the key is incorrect.
```
**Solution**: 
- Verify access secret is correct
- Check if key iterations match merchant settings
- Ensure encrypted data is not corrupted

### 3. AES-GCM Not Supported
```
Error: AES-256-GCM cipher is not available
```
**Solution**: 
- Ensure OpenSSL extension is installed
- Check OpenSSL version supports GCM mode
- Update OpenSSL if needed

---

## Best Practices

1. **Store secret securely**: Never expose access secret in client-side code
2. **Match key iterations**: Ensure key iterations match merchant settings
3. **Handle errors gracefully**: Always wrap decryption in try-catch
4. **Validate decrypted data**: Always validate decrypted data structure
5. **Use HTTPS**: Always use HTTPS when transmitting encrypted data
6. **Log errors**: Log decryption errors for debugging
7. **Test thoroughly**: Test encryption/decryption with various payloads

---

## Testing

### Test Encryption/Decryption

```php
$encryption = new Encryption($accessSecret);

// Test data
$testData = [
    'order_id' => 'o_test123',
    'status' => 'success',
    'amount' => 1000.00
];

// Encrypt
$encrypted = $encryption->encrypt($testData);
echo "Encrypted: {$encrypted}\n";

// Decrypt
$decrypted = $encryption->decrypt($encrypted, true);
echo "Decrypted: " . json_encode($decrypted, JSON_PRETTY_PRINT) . "\n";

// Verify
assert($decrypted === $testData, "Encryption/decryption failed");
echo "✓ Test passed\n";
```

---

## Related Documentation

- [Webhooks](./WEBHOOKS.md) - Receive payment status updates
- [Payments API](./PAYMENTS.md) - Process payments
- [Standard Checkout Integration](https://nimbbl.biz/docs/standard-checkout/completing-integration/)



