# Nimbbl PHP SDK - Testing Guide (v4.0.1)

This guide explains how to test all APIs in the Nimbbl PHP SDK v4.0.1.

## v4.0.1 Changes

The SDK has been restructured with the following changes:
- **Client Class**: `Nimbbl\Api\Api` is replaced by `Nimbbl\Api\RestClient\NimbblClient`.
- **Namespaces**: Services are now in `Nimbbl\Api\Services`.
- **Tests**: All tests have been updated to use the new structure.


##  Quick Start

### 1. Setup Configuration

```bash
# Copy config template
cp example/config.php.example example/config.php

# Edit with your credentials
nano example/config.php
```

**Required Configuration:**
```php
return [
    'access_key' => 'your_access_key_here',
    'access_secret' => 'your_access_secret_here',
   'api_host' => 'https://apipp.nimbbl.tech', // UAT/Sandbox host
];
```

### 2. Run Comprehensive Test Suite

```bash
# Run all API tests
php tests/test-all-apis.php
```

This will test:
- [OK] Orders API (Create, Get by Order ID, Get by Invoice ID)
- [OK] Addresses API (List, Create, Get, Update, Delete, Check Eligibility, Link Order)
- [OK] Payments API (Initiate)
- [OK] Payment Links API (Create, Enquiry)
- [OK] Checkout Utilities API (Payment Modes, Banks, Wallets, EMIs, Offers, Card BIN, UPI VPA)
- [OK] Transaction Status API (Enquiry by Order ID, Invoice ID)
- [OK] Transactions API (Transaction Enquiry)
- [OK] Refunds API (Initiate Refund - Full and Partial)
- [ERROR] Users API (Removed - not an official public API)

### Run Offline Unit Tests (No Credentials)
If you only want offline/unit tests (no live API calls and no `example/config.php`), run:

```bash
vendor/bin/phpunit -c phpunit.xml.dist
```

This suite runs focused security/logging/encryption/token-cache tests only (no network).

### 3. Run Individual Test Files

```bash
# Test specific API
php tests/OrderTest.php
php tests/AddressTest.php
php tests/PaymentTest.php
php tests/PaymentLinkTest.php
php tests/CheckoutUtilitiesTest.php
php tests/TransactionStatusTest.php
php tests/TransactionTest.php
php tests/RefundTest.php
# php tests/UserTest.php  # REMOVED - Users API not available
```

### 4. Run Master Test Runner

```bash
# Run all new API client tests
php tests/run-all-tests.php
```

##  Test Coverage

### Orders API
- [OK] Create Order
- [OK] Get Order by Order ID
- [OK] Get Order by Invoice ID

### Addresses API
- [OK] List Addresses
- [OK] Create Address
- [OK] Get Address
- [OK] Update Address
- [OK] Delete Address
- [OK] Import Addresses
- [OK] Check Address Eligibility
- [OK] Link Address with Order

### Payments API
- [OK] Initiate Payment
- [OK] Complete Payment (for Pay Later with OTP)
- [OK] Resend OTP

### Payment Links API
- [OK] Create Payment Link
- [OK] Payment Link Enquiry
- [OK] Update Payment Link
- [OK] Payment Link Actions (cancel, pause, resume)

### Checkout Utilities API
- [OK] List Payment Modes
- [OK] List Banks
- [OK] List Wallets
- [OK] List EMIs
- [OK] Get Offers
- [OK] Get Card BIN Data
- [OK] Validate UPI VPA

### Transaction Status API
- [OK] Transaction Enquiry by Order ID
- [OK] Transaction Enquiry by Invoice ID
- [OK] Transaction Enquiry by Transaction ID

### Transactions API
- [OK] Transaction Enquiry (by Order ID)
- [ERROR] Get Transaction by ID (Not official API - use Transaction Status API instead)
- [ERROR] List Transactions (Not official API)
- [ERROR] Get Transactions by Order ID (Not official API - use Transaction Status API instead)
- [ERROR] Cancel Transaction (Not official public API - internal API only)

### Refunds API
- [OK] Initiate Refund (Full and Partial)
- [ERROR] Get Refund by ID (Not official API - use Transaction Status API instead)
- [ERROR] List Refunds (Not official API)
- [ERROR] Get Refunds by Order ID (Not official API - use Transaction Status API instead)
- [ERROR] Get Refunds by Transaction ID (Not official API - use Transaction Status API instead)

### Users API
- [ERROR] **REMOVED** - Users API has been removed from the SDK as it's not an official public API

##  Test Output

The comprehensive test suite (`test-all-apis.php`) provides:
- [OK] Pass/Fail status for each test
- [INFO] Execution time for each test
-  Summary with total tests, passed, failed
- [ERROR] Detailed error messages for failures

## [WARNING] Important Notes

1. **API Credentials Required:**
   - Tests require valid API credentials in `example/config.php`
   - Use UAT/Sandbox environment for testing

2. **Test Dependencies:**
   - Some tests depend on previous tests (e.g., Payment tests need an Order)
   - Tests create real API calls - use test credentials

3. **Test Data:**
   - Tests use unique identifiers (timestamps, random numbers)
   - Test data is isolated per run

4. **Error Handling:**
   - Tests catch and report exceptions
   - Failed tests show detailed error information

##  Troubleshooting

### "Config file not found"
```bash
cp example/config.php.example example/config.php
# Then edit with your credentials
```

### "Invalid API credentials"
- Verify `access_key` and `access_secret` in `config.php`
- Check API URL (UAT vs Production)
- Ensure credentials are for the correct environment

### "Class not found"
```bash
# Install dependencies
composer install
```

### "Tests fail with API errors"
- Check API credentials are valid
- Verify API URL is correct
- Check network connectivity
- Review API error messages in test output

##  Test Structure

```
tests/
├── test-all-apis.php          # Comprehensive test suite (NEW)
├── OrderTest.php              # Orders API tests
├── AddressTest.php            # Addresses API tests
├── PaymentTest.php            # Payments API tests
├── PaymentLinkTest.php        # Payment Links API tests
├── CheckoutUtilitiesTest.php  # Checkout Utilities API tests
├── TransactionStatusTest.php  # Transaction Status API tests
├── TransactionTest.php        # Transactions API tests
├── RefundTest.php             # Refunds API tests
# ├── UserTest.php               # REMOVED - Users API not available
├── run-all-tests.php          # Master test runner
└── README.md                  # Test documentation
```

##  Best Practices

1. **Run tests in UAT/Sandbox environment first**
2. **Review test output carefully**
3. **Fix any failing tests before production**
4. **Keep test credentials secure**
5. **Don't commit config.php to version control**

---

For more information, see:
- [Main SDK README](../README.md)
- [Example Applications](../example/README.md)
- [API Documentation](https://nimbbl.biz/docs/api-reference/introduction/)

