# Checkout Utilities API Documentation

The Checkout Utilities API provides helper methods to get available payment options, validate inputs, and enhance the checkout experience.

## Overview

- **Token Type**: Order Token (obtained from order creation)
- **Base URL**: `/api/v3/checkout-utilities`
- **API Documentation**: https://nimbbl.biz/docs/category/api-reference/checkout-utilities/

## Methods

### 1. listPaymentModes

Get list of available payment modes for an order.

**Method Signature:**
```php
public function listPaymentModes($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Request body attributes:
  - `order_id` (string, required): Order ID
  - `amount` (number, optional): Order amount
  - `currency` (string, optional): Currency code (default: 'INR')
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Payment modes response containing:
  - `fast_payment_modes`: Array of fast payment modes
  - `other_payment_modes`: Array of other payment modes
  - Each payment mode contains: `code`, `name`, `display_name`, etc.

**Example:**
```php
use Nimbbl\Api\Api;

$api = new Api($accessKey, $accessSecret, $baseUrl, $apiVersion);

$paymentModesData = [
    'order_id' => 'o_4KQ3NzX4oO3PwYw2',
    'amount' => 1000.00,
    'currency' => 'INR'
];

$paymentModes = $api->checkoutUtilities()->listPaymentModes($paymentModesData, $orderToken);

if (!isset($paymentModes['error'])) {
    echo "Available Payment Modes:\n";
    
    if (isset($paymentModes['fast_payment_modes']['items'])) {
        echo "\nFast Payment Modes:\n";
        foreach ($paymentModes['fast_payment_modes']['items'] as $mode) {
            echo "  - " . $mode['display_name'] . " (" . $mode['code'] . ")\n";
        }
    }
    
    if (isset($paymentModes['other_payment_modes']['items'])) {
        echo "\nOther Payment Modes:\n";
        foreach ($paymentModes['other_payment_modes']['items'] as $mode) {
            echo "  - " . $mode['display_name'] . " (" . $mode['code'] . ")\n";
        }
    }
} else {
    echo "Error: " . $paymentModes['error']['nimbbl_merchant_message'] . "\n";
}
```

---

### 2. listBanks

Get list of available banks for net banking.

**Method Signature:**
```php
public function listBanks($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Bank list attributes (all fields are optional):
  - `order_id` (string, optional): Order ID. If provided, `total_amount` and `currency` are not required
  - `total_amount` (number, optional): Order amount. Required only if `order_id` is not provided
  - `currency` (string, optional): Currency code (default: 'INR'). Required only if `order_id` is not provided
  - `encrypted_payload` (string, optional): Encrypted payload for encrypted requests
  - **Note**: If you don't send any field, send an empty body object `[]`. Empty request body will provide only the list of banks without any offers or additional charges
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Banks list response containing:
  - `bank_list`: Array of bank objects, each containing:
    - `bank_name`: Bank name (e.g., 'HDFC Bank')
    - `code`: Bank code (e.g., 'hdfc', 'icici')
    - `logo`: Public URL for bank logo (nullable)
    - `next`: Array of next action recommendations
      - `action`: Next action (e.g., 'initiate_payment')
      - `url`: API endpoint URL
    - `health_status`: Bank health status ('up', 'down', 'fluctuating')
    - `additional_charges`: Additional charges for this bank (nullable)
    - `shipping_charges`: Shipping charges for this bank (nullable)

**Example - With Order ID:**
```php
$banksData = [
    'order_id' => 'o_4KQ3NzX4oO3PwYw2'
];

$banks = $api->checkoutUtilities()->listBanks($banksData, $orderToken);

if (!isset($banks['error'])) {
    echo "Available Banks:\n";
    if (isset($banks['bank_list'])) {
        foreach ($banks['bank_list'] as $bank) {
            echo "  - " . $bank['bank_name'] . " (" . $bank['code'] . ")\n";
            echo "    Health Status: " . $bank['health_status'] . "\n";
            if (isset($bank['additional_charges'])) {
                echo "    Additional Charges: " . $bank['additional_charges'] . "\n";
            }
        }
    }
} else {
    echo "Error: " . $banks['error']['nimbbl_merchant_message'] . "\n";
}
```

**Example - With Amount and Currency (without Order ID):**
```php
$banksData = [
    'total_amount' => 1000.00,
    'currency' => 'INR'
];

$banks = $api->checkoutUtilities()->listBanks($banksData, $orderToken);
```

**Example - Empty Request Body:**
```php
// Get banks without offers or additional charges
$banks = $api->checkoutUtilities()->listBanks([], $orderToken);
```

**API Reference**: [List of Banks v3](https://nimbbl.biz/docs/api-reference/list-of-banks-v-3/)

---

### 3. listWallets

Get list of available wallets.

**Method Signature:**
```php
public function listWallets($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Wallet list attributes:
  - `order_id` (string, required): Order ID
  - `amount` (number, optional): Order amount
  - `currency` (string, optional): Currency code (default: 'INR')
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Wallets list response containing:
  - `wallets`: Array of wallet objects with `code`, `name`, `display_name`

**Example:**
```php
$walletsData = [
    'order_id' => 'o_4KQ3NzX4oO3PwYw2',
    'amount' => 1000.00,
    'currency' => 'INR'
];

$wallets = $api->checkoutUtilities()->listWallets($walletsData, $orderToken);

if (!isset($wallets['error'])) {
    echo "Available Wallets:\n";
    if (isset($wallets['wallets'])) {
        foreach ($wallets['wallets'] as $wallet) {
            echo "  - " . $wallet['name'] . " (" . $wallet['code'] . ")\n";
        }
    }
}
```

---

### 4. listEMIs

Get list of available EMI options.

**Method Signature:**
```php
public function listEMIs($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): EMI list attributes:
  - `order_id` (string, required): Order ID
  - `amount` (number, optional): Order amount
  - `currency` (string, optional): Currency code (default: 'INR')
  - `payment_mode_code` (string, optional): Payment mode (e.g., 'credit_card', 'debit_card')
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: EMIs list response containing:
  - `emis`: Array of EMI options, each containing:
    - `tenure`: EMI tenure in months
    - `interest_rate`: Interest rate
    - `emi_amount`: EMI amount
    - `total_amount`: Total amount including interest

**Example:**
```php
$emisData = [
    'order_id' => 'o_4KQ3NzX4oO3PwYw2',
    'amount' => 10000.00,
    'currency' => 'INR',
    'payment_mode_code' => 'credit_card'
];

$emis = $api->checkoutUtilities()->listEMIs($emisData, $orderToken);

if (!isset($emis['error'])) {
    echo "Available EMI Options:\n";
    if (isset($emis['emis'])) {
        foreach ($emis['emis'] as $emi) {
            echo "  - " . $emi['tenure'] . " months: " . $emi['emi_amount'] . " per month\n";
            echo "    Interest Rate: " . $emi['interest_rate'] . "%\n";
            echo "    Total Amount: " . $emi['total_amount'] . "\n\n";
        }
    }
}
```

---

### 5. getOffers

Get available offers for a payment mode.

**Method Signature:**
```php
public function getOffers($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Request body attributes:
  - `order_id` (string, required): Order ID
  - `payment_mode_code` (string, required): Payment mode code (e.g., 'net_banking', 'credit_card')
  - `currency` (string, optional): Currency code (default: 'INR')
  - `card` (array, optional): Card details (for card offers):
    - `card_no`: Card number
    - `card_input_type`: 'card_pan', 'merchant_network_token', or 'nimbbl_token_id'
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Offers response containing:
  - `offers`: Array of offer objects, each containing:
    - `offer_id`: Offer identifier
    - `offer_name`: Offer name
    - `discount_amount`: Discount amount
    - `discount_percentage`: Discount percentage
    - `terms_and_conditions`: Terms and conditions

**Example:**
```php
$offersData = [
    'order_id' => 'o_4KQ3NzX4oO3PwYw2',
    'payment_mode_code' => 'net_banking',
    'currency' => 'INR'
];

$offers = $api->checkoutUtilities()->getOffers($offersData, $orderToken);

if (!isset($offers['error'])) {
    echo "Available Offers:\n";
    if (isset($offers['offers'])) {
        foreach ($offers['offers'] as $offer) {
            echo "  - " . $offer['offer_name'] . "\n";
            if (isset($offer['discount_amount'])) {
                echo "    Discount: " . $offer['discount_amount'] . "\n";
            }
            if (isset($offer['discount_percentage'])) {
                echo "    Discount: " . $offer['discount_percentage'] . "%\n";
            }
        }
    }
}
```

**Example - Card Offers:**
```php
$offersData = [
    'order_id' => 'o_4KQ3NzX4oO3PwYw2',
    'payment_mode_code' => 'credit_card',
    'currency' => 'INR',
    'card' => [
        'card_input_type' => 'card_pan',
        'card_no' => '4111111111111111'
    ]
];

$offers = $api->checkoutUtilities()->getOffers($offersData, $orderToken);
```

---

### 6. getCardBinData

Get card BIN (Bank Identification Number) data.

**Method Signature:**
```php
public function getCardBinData($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Card BIN attributes:
  - `card_bin` (string, required): First 6 digits of card number
  - `order_id` (string, optional): Order ID
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Card BIN data response containing:
  - `card_type`: Card type (credit/debit)
  - `bank_name`: Bank name
  - `network`: Card network (Visa, Mastercard, etc.)
  - `country`: Country code

**Example:**
```php
$cardBinData = [
    'card_bin' => '411111', // First 6 digits of card
    'order_id' => 'o_4KQ3NzX4oO3PwYw2'
];

$binData = $api->checkoutUtilities()->getCardBinData($cardBinData, $orderToken);

if (!isset($binData['error'])) {
    echo "Card BIN Data:\n";
    echo "  Card Type: " . $binData['card_type'] . "\n";
    echo "  Bank: " . $binData['bank_name'] . "\n";
    echo "  Network: " . $binData['network'] . "\n";
    echo "  Country: " . $binData['country'] . "\n";
}
```

---

### 7. validateUpiVpa

Validate a UPI VPA (Virtual Payment Address).

**Method Signature:**
```php
public function validateUpiVpa($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): UPI VPA attributes:
  - `upi_id` (string, required): UPI ID to validate (e.g., 'customer@paytm')
  - `order_id` (string, optional): Order ID
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: UPI VPA validation response containing:
  - `valid`: Boolean indicating if UPI ID is valid
  - `upi_id`: Validated UPI ID
  - `name`: Account holder name (if available)

**Example:**
```php
$upiData = [
    'upi_id' => 'customer@paytm',
    'order_id' => 'o_4KQ3NzX4oO3PwYw2'
];

$validation = $api->checkoutUtilities()->validateUpiVpa($upiData, $orderToken);

if (!isset($validation['error'])) {
    if ($validation['valid']) {
        echo "UPI ID is valid!\n";
        if (isset($validation['name'])) {
            echo "Account Name: " . $validation['name'] . "\n";
        }
    } else {
        echo "UPI ID is invalid.\n";
    }
}
```

---

### 8. getUpiAppDetails

Get UPI app details for a platform.

**Method Signature:**
```php
public function getUpiAppDetails($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Request body attributes:
  - `platform` (string, required): Platform ('android' or 'ios')
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: UPI app details response containing:
  - `apps`: Array of UPI app objects, each containing:
    - `app_name`: App name
    - `package_name`: Package name (for Android) or bundle ID (for iOS)
    - `deep_link_scheme`: Deep link scheme

**Example:**
```php
$upiAppData = [
    'platform' => 'android' // or 'ios'
];

$appDetails = $api->checkoutUtilities()->getUpiAppDetails($upiAppData, $orderToken);

if (!isset($appDetails['error'])) {
    echo "Available UPI Apps:\n";
    if (isset($appDetails['apps'])) {
        foreach ($appDetails['apps'] as $app) {
            echo "  - " . $app['app_name'] . "\n";
            echo "    Package: " . $app['package_name'] . "\n";
        }
    }
}
```

---

## Complete Checkout Flow Example

```php
// Step 1: Create order
$order = $api->orders()->createOrder($orderData, $merchantToken);
$orderToken = $order['token'];
$orderId = $order['order_id'];

// Step 2: Get available payment modes
$paymentModes = $api->checkoutUtilities()->listPaymentModes([
    'order_id' => $orderId,
    'amount' => 1000.00,
    'currency' => 'INR'
], $orderToken);

// Step 3: If user selects net banking, get banks
if ($selectedPaymentMode === 'net_banking') {
    $banks = $api->checkoutUtilities()->listBanks([
        'order_id' => $orderId,
        'amount' => 1000.00,
        'currency' => 'INR'
    ], $orderToken);
    
    // Display banks to user
    // User selects bank
    $selectedBank = 'HDFC';
    
    // Step 4: Initiate payment with selected bank
    $payment = $api->payments()->initiatePayment([
        'order_id' => $orderId,
        'payment_mode_code' => 'net_banking',
        'bank_code' => $selectedBank,
        'callback_url' => 'https://yourwebsite.com/callback'
    ], $orderToken);
}

// Step 5: If user enters card, validate BIN
if ($selectedPaymentMode === 'credit_card') {
    $cardNumber = '4111111111111111';
    $cardBin = substr($cardNumber, 0, 6);
    
    $binData = $api->checkoutUtilities()->getCardBinData([
        'card_bin' => $cardBin,
        'order_id' => $orderId
    ], $orderToken);
    
    // Check if card is valid and get offers
    $offers = $api->checkoutUtilities()->getOffers([
        'order_id' => $orderId,
        'payment_mode_code' => 'credit_card',
        'card' => [
            'card_input_type' => 'card_pan',
            'card_no' => $cardNumber
        ]
    ], $orderToken);
}
```

---

## Use Cases

### 1. Build Payment Mode Selection UI
```php
$paymentModes = $api->checkoutUtilities()->listPaymentModes([
    'order_id' => $orderId
], $orderToken);

// Display payment modes in UI dropdown
foreach ($paymentModes['fast_payment_modes']['items'] as $mode) {
    echo "<option value='" . $mode['code'] . "'>" . $mode['display_name'] . "</option>";
}
```

### 2. Build Bank Selection UI
```php
$banks = $api->checkoutUtilities()->listBanks([
    'order_id' => $orderId
], $orderToken);

// Display banks in UI
foreach ($banks['bank_list'] as $bank) {
    echo "<option value='" . $bank['code'] . "'>" . $bank['bank_name'] . "</option>";
}
```

### 3. Validate UPI Before Payment
```php
$upiId = $_POST['upi_id'];
$validation = $api->checkoutUtilities()->validateUpiVpa([
    'upi_id' => $upiId,
    'order_id' => $orderId
], $orderToken);

if (!$validation['valid']) {
    echo "Invalid UPI ID. Please check and try again.";
    exit;
}

// Proceed with payment
```

---

## Error Codes

Common error codes you may encounter:

- `ORDER_NOT_FOUND`: Order ID not found
- `INVALID_PAYMENT_MODE`: Invalid payment mode code
- `INVALID_CARD_BIN`: Invalid card BIN
- `INVALID_UPI_ID`: Invalid UPI ID format
- `NO_OFFERS_AVAILABLE`: No offers available for the given criteria

---

## Best Practices

1. **Cache results**: Payment modes, banks, and wallets don't change frequently - cache them
2. **Validate inputs**: Use validation methods (validateUpiVpa, getCardBinData) before payment
3. **Show offers**: Display available offers to increase conversion
4. **Handle errors gracefully**: Always check for `error` key in response
5. **Use order_id**: Always provide order_id for accurate results
6. **Progressive enhancement**: Load payment modes first, then load banks/wallets when user selects payment mode
7. **Display EMI options**: Show EMI options for high-value orders to improve conversion

---

## Related Documentation

- [Orders API](./ORDERS.md) - Create orders
- [Payments API](./PAYMENTS.md) - Process payments
- [Addresses API](./ADDRESSES.md) - Manage addresses



