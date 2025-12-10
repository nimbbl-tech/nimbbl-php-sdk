# Addresses API Documentation

The Addresses API allows you to manage customer addresses for delivery and billing purposes.

## Overview

- **Token Type**: Order Token (obtained from order creation)
- **Base URL**: `/api/v3/addresses`
- **API Documentation**: https://nimbbl.biz/docs/api-reference/list-addresses-v-3/

## Methods

### 1. listAddresses

List addresses for a user with optional filtering.

**Method Signature:**
```php
public function listAddresses($options = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$options` (array): Query parameters:
  - `user_id` (string, required): User identifier
  - `amount` (number, optional): Order amount for eligibility checking
  - `currency` (string, optional): Currency code (default: 'INR')
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Response containing:
  - `addresses`: Array of address objects
  - `next`: Pagination information
  - `providers`: Available address providers

**Example:**
```php
use Nimbbl\Api\Api;

$api = new Api($accessKey, $accessSecret, $baseUrl, $apiVersion);

$options = [
    'user_id' => 'user_123',
    'amount' => 1000.00,
    'currency' => 'INR'
];

$response = $api->addresses()->listAddresses($options, $orderToken);

if (!isset($response['error'])) {
    echo "Found " . count($response['addresses']) . " addresses\n";
    
    foreach ($response['addresses'] as $address) {
        echo "Address ID: " . $address['id'] . "\n";
        echo "Address: " . $address['address_1'] . ", " . $address['city'] . "\n";
    }
} else {
    echo "Error: " . $response['error']['nimbbl_merchant_message'] . "\n";
}
```

---

### 2. createAddress

Create a new address.

**Method Signature:**
```php
public function createAddress($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Address creation attributes:
  - `addresses` (array, required): Array of address objects, each containing:
    - `address_1` (string, required): Primary address line
    - `address_2` (string, optional): Secondary address line
    - `street` (string, optional): Street name
    - `landmark` (string, optional): Landmark
    - `area` (string, optional): Area/locality
    - `city` (string, required): City
    - `state` (string, required): State
    - `pincode` (string, required): PIN code
    - `country` (string, optional): Country (default: 'India')
    - `contact_name` (string, optional): Contact person name
    - `contact_phone` (string, optional): Contact phone number
  - `user_id` (string, optional): User identifier
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Array of created address objects

**Example:**
```php
$addressData = [
    'addresses' => [
        [
            'address_1' => '123 Main Street',
            'street' => 'MG Road',
            'landmark' => 'Near Park',
            'area' => 'Downtown',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'country' => 'India',
            'contact_name' => 'John Doe',
            'contact_phone' => '9876543210'
        ]
    ],
    'user_id' => 'user_123'
];

$createdAddresses = $api->addresses()->createAddress($addressData, $orderToken);

if (!isset($createdAddresses['error'])) {
    echo "Address created successfully!\n";
    foreach ($createdAddresses as $address) {
        echo "Address ID: " . $address['id'] . "\n";
    }
} else {
    echo "Error: " . $createdAddresses['error']['nimbbl_merchant_message'] . "\n";
}
```

---

### 3. updateAddress

Update an existing address.

**Method Signature:**
```php
public function updateAddress($id, $attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$id` (string): Address ID to update
- `$attributes` (array): Address attributes to update (same structure as createAddress)
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Updated address object

**Example:**
```php
$addressId = 'addr_123';
$updateData = [
    'address_1' => '456 New Street',
    'city' => 'Bangalore',
    'state' => 'Karnataka',
    'pincode' => '560001'
];

$updatedAddress = $api->addresses()->updateAddress($addressId, $updateData, $orderToken);

if (!isset($updatedAddress['error'])) {
    echo "Address updated successfully!\n";
    echo "Updated Address: " . $updatedAddress['address_1'] . "\n";
} else {
    echo "Error: " . $updatedAddress['error']['nimbbl_merchant_message'] . "\n";
}
```

---

### 4. deleteAddress

Delete an address.

**Method Signature:**
```php
public function deleteAddress($id, $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$id` (string): Address ID to delete
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Deleted address object

**Example:**
```php
$addressId = 'addr_123';
$deletedAddress = $api->addresses()->deleteAddress($addressId, $orderToken);

if (!isset($deletedAddress['error'])) {
    echo "Address deleted successfully!\n";
    echo "Deleted Address ID: " . $deletedAddress['id'] . "\n";
} else {
    echo "Error: " . $deletedAddress['error']['nimbbl_merchant_message'] . "\n";
}
```

---

### 5. importAddresses

Import addresses from external providers (e.g., e-commerce platforms).

**Method Signature:**
```php
public function importAddresses($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Import attributes:
  - `provider` (string, required): Provider name (e.g., 'amazon', 'flipkart')
  - `command` (string, required): Import command
  - `otp` (string, optional): OTP for verification
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Import response

**Example:**
```php
$importData = [
    'provider' => 'amazon',
    'command' => 'import',
    'otp' => '123456' // If required by provider
];

$result = $api->addresses()->importAddresses($importData, $orderToken);

if (!isset($result['error'])) {
    echo "Addresses imported successfully!\n";
} else {
    echo "Error: " . $result['error']['nimbbl_merchant_message'] . "\n";
}
```

---

### 6. checkAddressEligibility

Check if an address is eligible for delivery.

**Method Signature:**
```php
public function checkAddressEligibility($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Eligibility check attributes:
  - `pincode` (string, required): PIN code to check
  - `country_code` (string, optional): Country code (default: '+91')
  - `amount` (number, optional): Order amount
  - `currency` (string, optional): Currency code (default: 'INR')
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Eligibility response with delivery options

**Example:**
```php
$eligibilityData = [
    'pincode' => '400001',
    'country_code' => '+91',
    'amount' => 1000.00,
    'currency' => 'INR'
];

$eligibility = $api->addresses()->checkAddressEligibility($eligibilityData, $orderToken);

if (!isset($eligibility['error'])) {
    if ($eligibility['eligible']) {
        echo "Address is eligible for delivery!\n";
        echo "Available providers: " . implode(', ', $eligibility['providers']) . "\n";
    } else {
        echo "Address is not eligible for delivery.\n";
    }
} else {
    echo "Error: " . $eligibility['error']['nimbbl_merchant_message'] . "\n";
}
```

---

### 7. linkAddressWithOrder

Link an address with an order.

**Method Signature:**
```php
public function linkAddressWithOrder($attributes = array(), $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$attributes` (array): Link attributes:
  - `order_id` (string, required): Order ID
  - `address` (array, required): Address object (same structure as createAddress)
  - `link_as` (string, optional): Link type ('shipping' or 'billing', default: 'shipping')
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Link response

**Example:**
```php
$linkData = [
    'order_id' => 'o_4KQ3NzX4oO3PwYw2',
    'address' => [
        'address_1' => '123 Main Street',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
        'country' => 'India'
    ],
    'link_as' => 'shipping'
];

$result = $api->addresses()->linkAddressWithOrder($linkData, $orderToken);

if (!isset($result['error'])) {
    echo "Address linked to order successfully!\n";
} else {
    echo "Error: " . $result['error']['nimbbl_merchant_message'] . "\n";
}
```

---

### 8. getAddressById

Get address details by address ID.

**Method Signature:**
```php
public function getAddressById($addressId, $token = null, $apiVersion = ApiConstants::API_VERSION)
```

**Parameters:**
- `$addressId` (string): Address ID
- `$token` (string|null): Order token (optional, will use cached token if available)
- `$apiVersion` (string): API version (default: 'v3')

**Returns:**
- `array`: Address object with full details

**Example:**
```php
$addressId = 'addr_123';
$address = $api->addresses()->getAddressById($addressId, $orderToken);

if (!isset($address['error'])) {
    echo "Address Details:\n";
    echo "  ID: " . $address['id'] . "\n";
    echo "  Address: " . $address['address_1'] . "\n";
    echo "  City: " . $address['city'] . "\n";
    echo "  State: " . $address['state'] . "\n";
    echo "  PIN: " . $address['pincode'] . "\n";
} else {
    echo "Error: " . $address['error']['nimbbl_merchant_message'] . "\n";
}
```

---

## Complete Address Management Flow

```php
// Step 1: Check address eligibility
$eligibilityData = [
    'pincode' => '400001',
    'amount' => 1000.00,
    'currency' => 'INR'
];

$eligibility = $api->addresses()->checkAddressEligibility($eligibilityData, $orderToken);

if ($eligibility['eligible']) {
    // Step 2: Create address
    $addressData = [
        'addresses' => [
            [
                'address_1' => '123 Main Street',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
                'country' => 'India'
            ]
        ],
        'user_id' => 'user_123'
    ];
    
    $createdAddresses = $api->addresses()->createAddress($addressData, $orderToken);
    $addressId = $createdAddresses[0]['id'];
    
    // Step 3: Link address with order
    $linkData = [
        'order_id' => 'o_4KQ3NzX4oO3PwYw2',
        'address' => $createdAddresses[0],
        'link_as' => 'shipping'
    ];
    
    $api->addresses()->linkAddressWithOrder($linkData, $orderToken);
}
```

---

## Use Cases

### 1. List User Addresses
```php
$options = [
    'user_id' => 'user_123',
    'amount' => 1000.00,
    'currency' => 'INR'
];

$response = $api->addresses()->listAddresses($options, $orderToken);
// Display addresses to user for selection
```

### 2. Create Multiple Addresses
```php
$addressData = [
    'addresses' => [
        [
            'address_1' => 'Home Address',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001'
        ],
        [
            'address_1' => 'Office Address',
            'city' => 'Bangalore',
            'state' => 'Karnataka',
            'pincode' => '560001'
        ]
    ],
    'user_id' => 'user_123'
];

$createdAddresses = $api->addresses()->createAddress($addressData, $orderToken);
```

### 3. Update Address
```php
$addressId = 'addr_123';
$updateData = [
    'address_1' => 'Updated Address Line',
    'pincode' => '400002'
];

$updatedAddress = $api->addresses()->updateAddress($addressId, $updateData, $orderToken);
```

---

## Error Codes

Common error codes you may encounter:

- `ADDRESS_NOT_FOUND`: Address with given ID not found
- `INVALID_PINCODE`: Invalid PIN code provided
- `ADDRESS_NOT_ELIGIBLE`: Address is not eligible for delivery
- `USER_ID_REQUIRED`: User ID is required for address operations
- `INVALID_ADDRESS_DATA`: Invalid address data provided

---

## Best Practices

1. **Check eligibility first**: Always check address eligibility before creating addresses
2. **Validate PIN codes**: Ensure PIN codes are valid before submission
3. **Store address IDs**: Save address IDs for future reference and updates
4. **Link addresses to orders**: Always link addresses to orders for shipping
5. **Handle pagination**: Use the `next` parameter for paginated address lists
6. **Update addresses carefully**: Verify address data before updating
7. **Delete unused addresses**: Clean up addresses that are no longer needed

---

## Related Documentation

- [Orders API](./ORDERS.md) - Create orders with addresses
- [Checkout Utilities API](./CHECKOUT_UTILITIES.md) - Validate addresses



