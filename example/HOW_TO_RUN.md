# How to Run Sample App Examples

## 🚀 Quick Start

### Step 1: Install Dependencies

```bash
cd /Users/sandeepyadav/Downloads/sdks/nimbbl-php-sdk
composer install
```

### Step 2: Verify Configuration

Your `config.php` is already set up with credentials. Make sure it contains:
- `access_key` - Your Nimbbl API access key
- `access_secret` - Your Nimbbl API access secret
- `api_endpoint` - Combined base URL with version (e.g., https://api.nimbbl.tech/api/v3)

### Step 3: Run Examples

#### Main Examples (All-in-One)
```bash
php example/index.php
```
This runs multiple examples in sequence:
- Create Order
- Get Order by Order ID
- Initiate Refund
- Payment Signature Verification
- Addresses API
- Checkout Utilities
- Transaction Status
- Encryption/Decryption (if enabled)

#### Individual Examples

**Order Management:**
```bash
# Create an order
```

**Payment Processing:**
```bash
# Payments API examples
php example/payments-examples.php

# Payment Links examples
php example/payment-links-examples.php

# Checkout Utilities (payment modes, banks, wallets, etc.)
php example/checkout-utilities-examples.php
```

**Refunds & Status:**
```bash
# Refund examples
php example/refund-examples.php

# Transaction status enquiry
php example/transaction-status.php
```

**Addresses:**
```bash
# Addresses API examples
php example/addresses-examples.php
```

**Error Handling:**
```bash
# Exception handling examples
php example/exception-handling-examples.php
```

**Webhooks:**
```bash
# Webhook signature verification
php example/webhook-handler.php
```

**Encryption/Decryption:**
```bash
# Encryption and decryption examples (for Standard Checkout encrypted responses)
php example/encryption-examples.php
```

## 📝 Example Output

When you run an example, you'll see output like:

```
=== Nimbbl PHP SDK Examples ===

1. Creating an Order...
   Order created successfully!
   Order ID: o_XXXXXXXXXX
   Invoice ID: INV-1234567890
   Token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

2. Getting Order by Order ID...
   Order retrieved successfully!
   Order ID: o_XXXXXXXXXX
   Status: created
```

## 🔧 Troubleshooting

### Error: "Configuration file not found"
Make sure `config.php` exists in the `example/` directory:
```bash
cp example/config.php.example example/config.php
# Then edit config.php with your credentials
```

### Error: "Class not found" or "Autoload error"
Install Composer dependencies:
```bash
composer install
```

### Error: "Invalid credentials"
- Check your `access_key` and `access_secret` in `config.php`
- Make sure you're using the correct environment (UAT vs Production)
- Verify your API keys are active in the Nimbbl dashboard

### Error: "API request failed"
- Check your internet connection
- Verify the API URL is correct
- Check if you're hitting rate limits
- Review the error message for specific API errors

## 📚 Next Steps

1. **Review the examples** - Each example file contains detailed comments
2. **Check the README** - See `example/README.md` for more details
3. **Integrate into your app** - Copy patterns from examples into your application
4. **Test with real data** - Replace test IDs with actual order/transaction IDs

## 🎯 Framework Integration

These examples work in **any PHP framework**:
- **Laravel**: Use in Controllers, Services, or Commands
- **CodeIgniter**: Use in Controllers or Libraries
- **Symfony**: Use in Controllers or Services
- **Plain PHP**: Use directly as shown

See `FRAMEWORK_INTEGRATION.md` for framework-specific integration examples.

