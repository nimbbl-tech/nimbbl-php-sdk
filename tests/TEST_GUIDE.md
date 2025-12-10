# Nimbbl PHP SDK - API Testing Guide

This guide explains how to test all APIs in the Nimbbl PHP SDK.

## 🚀 Quick Start

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
    'api_url' => 'https://apipp.nimbbl.tech/api/', // UAT/Sandbox
    'api_version' => 'v3',
];
```

### 2. Run Comprehensive Test Suite

```bash
# Run all API tests
php tests/test-all-apis.php
```

This will test:
- ✅ Orders API (Create, Get by Order ID, Get by Invoice ID)
- ✅ Addresses API (List, Create, Get, Update, Delete, Check Eligibility, Link Order)
- ✅ Payments API (Initiate)
- ✅ Payment Links API (Create, Enquiry)
- ✅ Checkout Utilities API (Payment Modes, Banks, Wallets, EMIs, Offers, Card BIN, UPI VPA)
- ✅ Transaction Status API (Enquiry by Order ID, Invoice ID)
- ✅ Transactions API (Transaction Enquiry)
- ✅ Refunds API (Initiate Refund - Full and Partial)
- ❌ Users API (Removed - not an official public API)

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

## 📊 Test Coverage

### Orders API
- ✅ Create Order
- ✅ Get Order by Order ID
- ✅ Get Order by Invoice ID

### Addresses API
- ✅ List Addresses
- ✅ Create Address
- ✅ Get Address
- ✅ Update Address
- ✅ Delete Address
- ✅ Import Addresses
- ✅ Check Address Eligibility
- ✅ Link Address with Order

### Payments API
- ✅ Initiate Payment
- ✅ Complete Payment (for Pay Later with OTP)
- ✅ Resend OTP

### Payment Links API
- ✅ Create Payment Link
- ✅ Payment Link Enquiry
- ✅ Update Payment Link
- ✅ Payment Link Actions (cancel, pause, resume)

### Checkout Utilities API
- ✅ List Payment Modes
- ✅ List Banks
- ✅ List Wallets
- ✅ List EMIs
- ✅ Get Offers
- ✅ Get Card BIN Data
- ✅ Validate UPI VPA

### Transaction Status API
- ✅ Transaction Enquiry by Order ID
- ✅ Transaction Enquiry by Invoice ID
- ✅ Transaction Enquiry by Transaction ID

### Transactions API
- ✅ Transaction Enquiry (by Order ID)
- ❌ Get Transaction by ID (Not official API - use Transaction Status API instead)
- ❌ List Transactions (Not official API)
- ❌ Get Transactions by Order ID (Not official API - use Transaction Status API instead)
- ❌ Cancel Transaction (Not official public API - internal API only)

### Refunds API
- ✅ Initiate Refund (Full and Partial)
- ❌ Get Refund by ID (Not official API - use Transaction Status API instead)
- ❌ List Refunds (Not official API)
- ❌ Get Refunds by Order ID (Not official API - use Transaction Status API instead)
- ❌ Get Refunds by Transaction ID (Not official API - use Transaction Status API instead)

### Users API
- ❌ **REMOVED** - Users API has been removed from the SDK as it's not an official public API

## 🔍 Test Output

The comprehensive test suite (`test-all-apis.php`) provides:
- ✅ Pass/Fail status for each test
- ⏱️ Execution time for each test
- 📊 Summary with total tests, passed, failed
- ❌ Detailed error messages for failures

## ⚠️ Important Notes

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

## 🐛 Troubleshooting

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

## 📝 Test Structure

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

## 🎯 Best Practices

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

