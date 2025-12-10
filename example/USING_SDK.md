# Using the Nimbbl PHP SDK in Your Application

This guide explains how to use the Nimbbl PHP SDK in your application, whether installed via Composer or manually.

## 📦 Installation Methods

### Method 1: Using Composer (Recommended)

#### Install from Packagist

```bash
composer require nimbbl/nimbbl-sdk
```

#### Install from Local Path (Development)

```bash
# In your project's composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "../nimbbl-php-sdk"
        }
    ],
    "require": {
        "nimbbl/nimbbl-sdk": "*"
    }
}

# Then run
composer update
```

#### Install from Git Repository

```bash
# In your project's composer.json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/your-username/nimbbl-php-sdk"
        }
    ],
    "require": {
        "nimbbl/nimbbl-sdk": "dev-master"
    }
}
```

### Method 2: Manual Installation

1. **Download the SDK**:
   ```bash
   git clone https://github.com/your-username/nimbbl-php-sdk.git
   # or download ZIP and extract
   ```

2. **Install Dependencies**:
   ```bash
   cd nimbbl-php-sdk
   composer install
   ```

3. **Include in Your Project**:
   ```php
   require_once 'path/to/nimbbl-php-sdk/vendor/autoload.php';
   ```

## 🚀 Quick Start

### Basic Setup

```php
<?php
require_once 'vendor/autoload.php';

use Nimbbl\Api\Api;

// Initialize the SDK
$api = new Api(
    'your_access_key',
    'your_access_secret',
    'https://api.nimbbl.tech/api/', // API base URL
    'v3' // API version
);
```

### Complete Example

```php
<?php
require_once 'vendor/autoload.php';

use Nimbbl\Api\Api;

// Initialize SDK
$api = new Api(
    getenv('NIMBBL_ACCESS_KEY'),
    getenv('NIMBBL_ACCESS_SECRET'),
    'https://api.nimbbl.tech/api/',
    'v3'
);

// Step 1: Generate merchant token
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

// Step 2: Create order
$order = $api->orders()->createOrder([
    'invoice_id' => 'INV-' . time(),
    'amount_before_tax' => 100.00,
    'tax' => 18.00,
    'total_amount' => 118.00,
    'currency' => 'INR',
    'user' => [
        'email' => 'customer@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_number' => '9876543210',
        'country_code' => '+91'
    ]
], $merchantToken);

// Step 3: Extract order token
if (!isset($order['error'])) {
    $orderToken = $order['token'];
    $orderId = $order['order_id'] ?? $order['nimbbl_order_id'];
    
    // Step 4: Initiate payment
    $payment = $api->payments()->initiatePayment([
        'order_id' => $orderId,
        'payment_mode_code' => 'net_banking',
        'bank_code' => 'HDFC',
        'callback_url' => 'https://yourwebsite.com/payment/callback'
    ], $orderToken);
    
    echo "Payment initiated: " . ($payment['transaction_id'] ?? 'N/A');
}
```

## 🏗️ Framework Integration

### Laravel

#### Installation

```bash
composer require nimbbl/nimbbl-sdk
```

#### Service Provider (Laravel < 5.5)

Create `app/Providers/NimbblServiceProvider.php`:

```php
<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nimbbl\Api\Api;

class NimbblServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('nimbbl', function ($app) {
            return new Api(
                config('services.nimbbl.access_key'),
                config('services.nimbbl.access_secret'),
                config('services.nimbbl.api_endpoint', 'https://api.nimbbl.tech/api/v3')
            );
        });
    }
}
```

#### Configuration (`config/services.php`)

```php
'nimbbl' => [
    'access_key' => env('NIMBBL_ACCESS_KEY'),
    'access_secret' => env('NIMBBL_ACCESS_SECRET'),
    'api_endpoint' => env('NIMBBL_API_ENDPOINT', 'https://api.nimbbl.tech/api/v3'),
],
```

#### Usage in Controller

```php
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function createOrder(Request $request)
    {
        $api = app('nimbbl');
        
        $tokenResponse = $api->auth()->generateToken();
        $merchantToken = $tokenResponse['token'];
        
        $order = $api->orders()->createOrder([
            'invoice_id' => 'INV-' . time(),
            'total_amount' => $request->amount,
            'currency' => 'INR',
            'user' => [
                'email' => $request->email,
                'first_name' => $request->first_name,
                'mobile_number' => $request->mobile,
                'country_code' => '+91'
            ]
        ], $merchantToken);
        
        return response()->json($order);
    }
}
```

### CodeIgniter

#### Installation

```bash
composer require nimbbl/nimbbl-sdk
```

#### Library (`application/libraries/Nimbbl.php`)

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '../vendor/autoload.php';

use Nimbbl\Api\Api;

class Nimbbl
{
    private $api;
    
    public function __construct()
    {
        $this->api = new Api(
            getenv('NIMBBL_ACCESS_KEY'),
            getenv('NIMBBL_ACCESS_SECRET'),
            'https://api.nimbbl.tech/api/',
            'v3'
        );
    }
    
    public function getApi()
    {
        return $this->api;
    }
}
```

#### Usage in Controller

```php
<?php
class Payment extends CI_Controller
{
    public function create_order()
    {
        $this->load->library('nimbbl');
        $api = $this->nimbbl->getApi();
        
        $tokenResponse = $api->auth()->generateToken();
        $merchantToken = $tokenResponse['token'];
        
        $order = $api->orders()->createOrder([
            'invoice_id' => 'INV-' . time(),
            'total_amount' => 100.00,
            'currency' => 'INR',
            'user' => [
                'email' => 'customer@example.com',
                'first_name' => 'John',
                'mobile_number' => '9876543210',
                'country_code' => '+91'
            ]
        ], $merchantToken);
        
        $this->output->set_content_type('application/json')->set_output(json_encode($order));
    }
}
```

### Symfony

#### Installation

```bash
composer require nimbbl/nimbbl-sdk
```

#### Service Configuration (`config/services.yaml`)

```yaml
services:
    Nimbbl\Api\Api:
        arguments:
            $accessKey: '%env(NIMBBL_ACCESS_KEY)%'
            $accessSecret: '%env(NIMBBL_ACCESS_SECRET)%'
            $baseUrl: '%env(NIMBBL_API_URL)%'
            $apiVersion: '%env(NIMBBL_API_VERSION)%'
```

#### Usage in Controller

```php
<?php
namespace App\Controller;

use Nimbbl\Api\Api;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{
    private $api;
    
    public function __construct(Api $api)
    {
        $this->api = $api;
    }
    
    public function createOrder()
    {
        $tokenResponse = $this->api->auth()->generateToken();
        $merchantToken = $tokenResponse['token'];
        
        $order = $this->api->orders()->createOrder([
            'invoice_id' => 'INV-' . time(),
            'total_amount' => 100.00,
            'currency' => 'INR',
            'user' => [
                'email' => 'customer@example.com',
                'first_name' => 'John',
                'mobile_number' => '9876543210',
                'country_code' => '+91'
            ]
        ], $merchantToken);
        
        return $this->json($order);
    }
}
```

### Plain PHP

#### Installation

```bash
composer require nimbbl/nimbbl-sdk
```

#### Usage

```php
<?php
require_once 'vendor/autoload.php';

use Nimbbl\Api\Api;

// Load configuration
$config = require 'config.php';

// Initialize SDK
$api = new Api(
    $config['access_key'],
    $config['access_secret'],
    $config['api_endpoint'],
    null
);

// Use SDK
$tokenResponse = $api->auth()->generateToken();
$merchantToken = $tokenResponse['token'];

$order = $api->orders()->createOrder($orderData, $merchantToken);
```

## 🔧 Configuration

### Environment Variables

Recommended approach for production:

```bash
# .env file
NIMBBL_ACCESS_KEY=your_access_key
NIMBBL_ACCESS_SECRET=your_access_secret
NIMBBL_API_URL=https://api.nimbbl.tech/api/
NIMBBL_API_VERSION=v3
```

### Configuration File

```php
<?php
// config.php
return [
    'access_key' => getenv('NIMBBL_ACCESS_KEY') ?: 'your_access_key',
    'access_secret' => getenv('NIMBBL_ACCESS_SECRET') ?: 'your_access_secret',
    'api_endpoint' => getenv('NIMBBL_API_ENDPOINT') ?: 'https://api.nimbbl.tech/api/v3',
];
```

## 📝 Using the Example App

The example app in this repository demonstrates all SDK features:

### Running Examples

```bash
# Navigate to example directory
cd example

# Copy configuration
cp config.php.example config.php

# Edit config.php with your credentials
nano config.php

# Run interactive CLI
php cli.php

# Or run specific examples
php create-order.php
php payments-examples.php
php checkout-utilities-examples.php
```

### Example App Structure

```
example/
├── cli.php                      # Interactive CLI (main entry point)
├── config.php                   # Your configuration (create from example)
├── config.php.example           # Configuration template
├── create-order.php             # Order creation example
├── payments-examples.php         # Payment examples
├── checkout-utilities-examples.php # Checkout utilities examples
└── ...                          # Other examples
```

### Interactive CLI

The `cli.php` provides an interactive menu:

```bash
php example/cli.php
```

Menu options:
1. Create Order
2. Get Order by ID
3. Get Order by Invoice ID
4. Initiate Payment
5. Complete Payment
6. Resend OTP
7. Create Payment Link
8. List Addresses
9. ... and more

## 🔐 Security Best Practices

1. **Never commit credentials**: Use environment variables or `.env` files
2. **Use HTTPS**: Always use HTTPS for API calls
3. **Validate input**: Validate all user input before sending to API
4. **Verify webhooks**: Always verify webhook signatures
5. **Store tokens securely**: Don't expose tokens in client-side code

## 🐛 Troubleshooting

### Autoload Issues

```bash
# Regenerate autoload files
composer dump-autoload
```

### Missing Dependencies

```bash
# Install dependencies
composer install
```

### API Errors

- Check credentials in configuration
- Verify API URL is correct
- Check network connectivity
- Review error messages in response

## 📚 Additional Resources

- [SDK Documentation](../README.md)
- [API Reference](https://nimbbl.biz/docs/api-reference/introduction/)
- [Example App README](./README.md)
- [Publishing Guide](../PUBLISHING.md)

## 📞 Support

For issues or questions:
- Check the [API Documentation](https://nimbbl.biz/docs/api-reference/introduction/)
- Review example files in `example/` directory
- Contact support: support@nimbbl.biz

