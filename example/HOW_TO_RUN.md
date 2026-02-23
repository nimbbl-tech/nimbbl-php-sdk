# How to Run Sample App Examples

## 🚀 Quick Start

### Step 1: Install Dependencies

```bash
cd /path/to/nimbbl-php-sdk
composer install
```

### Step 2: Configure Credentials

Copy the example configuration file and update with your credentials:

```bash
cp example/config.php.example example/config.php
```

Edit `example/config.php` and set:

- `access_key` - Your Nimbbl API access key
- `access_secret` - Your Nimbbl API access secret
- `api_host` - API host (e.g., `https://api.nimbbl.tech`)

### Step 3: Run Examples

#### Option 1: Interactive CLI Menu (Recommended) 🎯

The easiest way to run examples is through the interactive CLI menu:

```bash
php example/cli.php
```

This will display an interactive menu with all available examples:

```text
=== Nimbbl PHP SDK - Event-Based CLI ===

Select an API to test:

=== Authentication ===
1.  Generate Token

=== Orders API ===
2.  Create Order
3.  Get Order by ID
4.  Get Order by Invoice ID

=== Payments API ===
5.  Initiate Payment
6.  Complete Payment
7.  Resend OTP

=== Payment Links API ===
8.  Create Payment Link
9.  Update Payment Link
10. Payment Link Enquiry
11. Payment Link Actions

=== Addresses API ===
12. List Addresses
13. Create Address
14. Update Address
15. Delete Address
16. Get Address by ID
17. Import Addresses
18. Check Address Eligibility
19. Link Order to Address

=== Refunds API ===
20. Initiate Refund

=== Transactions API ===
21. Transaction Enquiry

=== Checkout Utilities API ===
22. List Payment Modes
23. List Banks
24. List Wallets
25. List EMIs
26. Get Offers
27. Get Card BIN Data
28. Get Card Details
29. Validate UPI VPA
30. Get UPI App Details

=== Webhooks ===
31. Webhook Handling

=== Examples ===
32. Encryption Examples

0.  Exit
```

Simply enter the number of the example you want to run and follow the prompts.

#### Option 2: Run Individual Examples Standalone

All example files can be run directly from the command line. Each file is self-contained and will prompt for required inputs.

**Authentication:**

```bash
# Generate authentication token
php example/generate-token.php
```

**Order Management:**

```bash
# Order examples (Create, Get by ID, Get by Invoice ID)
php example/order-examples.php
```

**Payment Processing:**

```bash
# Payments API examples (Initiate, Complete, Resend OTP)
php example/payments-examples.php

# Payment Links examples (Create, Update, Enquiry, Actions)
php example/payment-links-examples.php

# Checkout Utilities (payment modes, banks, wallets, EMIs, offers, etc.)
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
# Addresses API examples (List, Create, Update, Delete, Import, etc.)
php example/addresses-examples.php
```

**Error Handling:**

```bash
# Exception handling examples
php example/exception-handling-examples.php
```

**Webhooks:**

```bash
# Webhook handler (displays setup information when run from CLI)
php example/webhook-handler.php
```

**Encryption/Decryption:**

```bash
# Encryption and decryption examples (for Standard Checkout encrypted responses)
php example/encryption-examples.php
```

#### Option 3: Run All Examples (Legacy)

```bash
php example/index.php
```

This runs multiple examples in sequence (legacy method).

## 📋 Example File Structure

All example files follow a consistent structure:

- **Standalone Execution**: Each file can be run directly with `php example/filename.php`
- **Function-Based**: Each file exports functions that can be called from `cli.php` or other scripts
- **Self-Contained**: Each example initializes the API internally and handles all inputs

### Example File Pattern

```php
#!/usr/bin/env php
<?php
// File header with documentation

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';
require_once __DIR__ . '/utils/cli_output.php';

function exampleFunction() {
    // Initialize API
    $config = loadConfig();
    $api = initApi($config);
    
    // Collect inputs and make API calls
    // ...
}

// Standalone execution
if (basename($_SERVER['PHP_SELF']) === 'example-file.php') {
    // Run example when executed directly
    exampleFunction();
}
```

## 📺 Example Output

When you run an example through the CLI menu or standalone, you'll see output like:

```text
=== Nimbbl PHP SDK - Event-Based CLI ===

Select an API to test:
...

[SUCCESS] Order created successfully!
Order ID: o_XXXXXXXXXX
Invoice ID: INV-1234567890
Token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

 Reference: https://nimbbl.biz/docs/api-reference/create-an-order-v-3/ - Create Order API
```

Or when running standalone:

```text
=== Order Examples (Create + Get) ===

Step 1: Create Order
------------------------------------------------------------
Enter Merchant Token: 
Enter Invoice ID: INV-12345
...
[SUCCESS] Order created successfully!
```

## 🔧 Troubleshooting

### Error: "Configuration file not found"

Make sure `config.php` exists in the `example/` directory:

```bash
cp example/config.php.example example/config.php
# Then edit config.php with your credentials
```

### Error: "Please update example/config.php with your Nimbbl credentials"

- Copy `config.php.example` to `config.php` if it doesn't exist
- Update `config.php` with your actual `access_key` and `access_secret`
- Ensure values are not set to placeholder values like `your_access_key_here`

### Error: "Class not found" or "Autoload error"

Install Composer dependencies:

```bash
composer install
```

### Error: "Invalid credentials" or "Authentication failed"

- Check your `access_key` and `access_secret` in `config.php`
- Make sure you're using the correct environment (UAT vs Production)
- Verify your API keys are active in the Nimbbl dashboard
- Try generating a new token using option 1 in the CLI menu

### Error: "Order Token is required" or "Merchant Token is required"

- For order-related operations: Create an order first (option 2) to get an order token
- For merchant operations: Generate a token first (option 1) or use cached token
- The SDK automatically caches merchant tokens, so you can press Enter to use cached tokens when prompted

### Error: "API request failed"

- Check your internet connection
- Verify the API URL is correct in `config.php`
- Check if you're hitting rate limits
- Review the error message for specific API errors
- Check the error code and HTTP status in the error output

### Error: "Function not found" or "Call to undefined function"

- Ensure you're running examples from the `example/` directory
- Make sure `utils/cli_output.php` and `utils/helpers.php` exist
- Verify all `require_once` statements are correct

## 💡 Key Features

### Modular Design

- **Standalone Execution**: Each example file can run independently
- **Function-Based**: All examples export functions that can be called from other scripts
- **Centralized Utilities**: Common functions are in `utils/cli_output.php` and `utils/helpers.php`
- **Consistent Structure**: All files follow the same pattern for easy understanding

### Interactive CLI

- **Menu-Driven**: Easy navigation through all examples
- **Input Prompts**: Clear prompts for all required inputs
- **Error Handling**: Comprehensive error messages and exception handling
- **Documentation Links**: Each example includes links to relevant API documentation

### Token Management

- **Automatic Caching**: SDK automatically caches generated merchant tokens
- **Token Reuse**: Press Enter when prompted to use cached merchant tokens
- **Explicit Tokens**: You can always provide a token explicitly if needed

## 📚 Next Steps

1. **Start with CLI Menu** - Run `php example/cli.php` to explore all examples interactively
2. **Review Individual Examples** - Each example file contains detailed comments and documentation
3. **Check API Documentation** - Each example includes links to the relevant Nimbbl API documentation
4. **Read USING_SDK.md** - See `example/USING_SDK.md` for detailed SDK usage patterns
5. **Integrate into Your App** - Copy patterns from examples into your application
6. **Test with Real Data** - Replace test IDs with actual order/transaction IDs from your account

## 🔗 Framework Integration

These examples work in **any PHP framework**:

- **Laravel**: Use in Controllers, Services, or Commands
- **CodeIgniter**: Use in Controllers or Libraries
- **Symfony**: Use in Controllers or Services
- **Plain PHP**: Use directly as shown

The example functions can be called from any PHP script:

```php
require_once __DIR__ . '/example/order-examples.php';
createOrderExample(); // Call the function directly
```

## 📁 File Structure

```text
example/
├── cli.php                      # Interactive CLI menu (main entry point)
├── generate-token.php           # Token generation example
├── order-examples.php           # Order management examples
├── payments-examples.php        # Payment processing examples
├── payment-links-examples.php  # Payment link examples
├── addresses-examples.php      # Address management examples
├── refund-examples.php         # Refund examples
├── transaction-status.php      # Transaction enquiry example
├── checkout-utilities-examples.php  # Checkout utilities examples
├── encryption-examples.php     # Encryption/decryption examples
├── webhook-handler.php         # Webhook handler example
├── exception-handling-examples.php  # Exception handling examples
├── config.php.example          # Configuration template
├── config.php                  # Your configuration (create from .example)
├── utils/
│   ├── cli_output.php          # CLI output utilities (colors, print functions)
│   └── helpers.php             # Helper functions (config loading, API init)
└── HOW_TO_RUN.md              # This file
```

## 🎯 Quick Reference

| Task | Command |
| :--- | :------ |
| Run interactive menu | `php example/cli.php` |
| Generate token | `php example/generate-token.php` |
| Create order | `php example/order-examples.php` (or use CLI option 2) |
| Process payment | `php example/payments-examples.php` (or use CLI option 5-7) |
| Check transaction | `php example/transaction-status.php` (or use CLI option 21) |
| Handle webhook | Deploy `webhook-handler.php` to your server |

For more details, see the individual example files or `USING_SDK.md`.
