# Nimbbl PHP SDK - Test Suite

This directory contains comprehensive test scripts for all Nimbbl PHP SDK API clients.

##  Structure

```
tests/
├── README.md                 # This file
├── AddressTest.php           # Addresses API test (NEW)
├── PaymentTest.php           # Payments API test (NEW)
├── PaymentLinkTest.php        # Payment Links API test (NEW)
├── CheckoutUtilitiesTest.php # Checkout Utilities API test (NEW)
├── TransactionStatusTest.php  # Transaction Status API test (NEW)
├── run-all-tests.php         # Master test runner (NEW)
├── OrderTest.php             # Order API test (existing)
├── RefundTest.php            # Refund API test (existing)
├── TransactionTest.php       # Transaction API test (existing)
# └── UserTest.php              # REMOVED - Users API not available
```

##  Quick Start

### 1. Setup Configuration

The tests use the configuration file from the `example/` directory:

```bash
# Copy the example config file (if not already done)
cp example/config.php.example example/config.php

# Edit config.php with your API credentials
nano example/config.php
```

### 2. Install Dependencies

```bash
# From the SDK root directory
composer install
```

### 3. Run Tests

```bash
# Run individual tests
php tests/AddressTest.php
php tests/PaymentTest.php
php tests/PaymentLinkTest.php
php tests/CheckoutUtilitiesTest.php
php tests/TransactionStatusTest.php

# Run all tests at once
php tests/run-all-tests.php
```

##  Test Files

### Comprehensive Test Suite

For comprehensive testing of all official APIs, use:
- **test-all-apis.php** - Tests all official API endpoints with detailed output

### New API Client Tests (Phase 1)

These tests cover the newly implemented API clients:

#### AddressTest.php
Tests all Addresses API endpoints:
- List addresses
- Create address
- Retrieve address
- Update address
- Delete address
- Import addresses
- Check address eligibility
- Link address with order

### PaymentTest.php
Tests Payments API endpoints:
- Initiate payment
- Complete payment (for Pay Later with OTP)
- Resend OTP

**Note:** Requires an existing order. The test creates an order automatically.

### PaymentLinkTest.php
Tests Payment Links API endpoints:
- Create payment link
- Payment link enquiry
- Update payment link
- Payment link actions (cancel, pause, resume)

### CheckoutUtilitiesTest.php
Tests Checkout Utilities API endpoints:
- List payment modes
- List banks
- List wallets
- List EMIs
- Get offers
- Get card BIN data
- Validate UPI VPA

**Note:** Requires an existing order. The test creates an order automatically.

### TransactionStatusTest.php
Tests Transaction Status API:
- Transaction enquiry by order_id
- Transaction enquiry by invoice_id
- Transaction enquiry by transaction_id

**Note:** Requires an existing order. The test creates an order automatically.

### run-all-tests.php
Master test runner that executes all new API client tests sequentially and provides a summary report.

### Existing Tests

The following test files were already present in the tests directory:

- **OrderTest.php** - Tests Order API endpoints:
  - Create Order
  - Retrieve Order by Order ID
  
- **RefundTest.php** - Tests Refund API endpoints:
  - Initiate Refund (official API only)
  - Note: Other refund methods (Get by ID, List, etc.) are not official APIs

- **TransactionTest.php** - [WARNING] **No active tests** (all tests commented out)
  - All tests are commented out as they test non-official API methods
  - Use `test-all-apis.php` for Transaction Enquiry testing (official API)
  - Use Transaction Status API tests for transaction status checking

- ~~**UserTest.php**~~ - **REMOVED** - Users API has been removed from the SDK

These can be run individually or integrated into the master test runner.

##  Configuration

Tests use the configuration from `example/config.php`. Make sure it contains:

```php
return [
    'access_key' => 'your_access_key_here',
    'access_secret' => 'your_access_secret_here',
   'api_host' => 'https://apipp.nimbbl.tech', // or production host
    // Note: Webhook verification uses access_secret automatically
    'enable_logging' => true,
    'log_file' => __DIR__ . '/../logs/nimbbl_debug.log',
];
```

##  Test Output

Each test provides:
- [OK] Success indicators for passed tests
- [ERROR] Error indicators with detailed error messages
- Step-by-step output showing what's being tested
- Execution time for each test

The master test runner (`run-all-tests.php`) provides:
- Individual test results
- Summary with pass/fail counts
- Total execution time

##  Troubleshooting

### Common Issues

1. **"Config file not found"**
   - Make sure `example/config.php` exists
   - Copy from `example/config.php.example` if needed

2. **"Invalid API credentials"**
   - Verify your `access_key` and `access_secret` in `example/config.php`
   - Check that you're using the correct environment (UAT vs Production)

3. **"Order creation failed"**
   - Check your API credentials
   - Verify the API URL is correct
   - Check network connectivity

4. **"Class not found" errors**
   - Run `composer install` from the SDK root directory
   - Verify `vendor/autoload.php` exists

##  Related Documentation

- [Main SDK README](../README.md)
- [Example Applications](../example/README.md)
- [API Documentation](https://nimbbl.biz/docs/api-reference/introduction/)

