<?php

namespace Nimbbl\Api\Common;

class ApiConstants
{
    // Base API URL and version
    const API_PATH = '/api/';
    const BASE_URL = 'https://api.nimbbl.tech' . self::API_PATH;
    const API_VERSION = 'v3';

    // Orders
    const ORDER_CREATE = self::API_VERSION . '/create-order';
    const ORDER_GET = self::API_VERSION . '/order';

    // Addresses
    const ADDRESS_LIST = self::API_VERSION . '/addresses';
    const ADDRESS_CREATE = self::API_VERSION . '/addresses';
    const ADDRESS_GET = self::API_VERSION . '/addresses';
    const ADDRESS_UPDATE = self::API_VERSION . '/addresses';
    const ADDRESS_DELETE = self::API_VERSION . '/addresses';
    const ADDRESS_IMPORT = self::API_VERSION . '/addresses/import';
    const ADDRESS_CHECK_ELIGIBILITY = self::API_VERSION . '/addresses/eligibility';
    const ADDRESS_LINK_ORDER = self::API_VERSION . '/addresses/link';

    // Payments
    const PAYMENT_INITIATE = self::API_VERSION . '/initiate-payment';
    const PAYMENT_COMPLETE = self::API_VERSION . '/payment';
    const PAYMENT_RESEND_OTP = self::API_VERSION . '/resend-otp';

    // Payment Links
    const PAYMENT_LINK_CREATE = self::API_VERSION . '/payment-link';
    const PAYMENT_LINK_UPDATE = self::API_VERSION . '/payment-link';
    const PAYMENT_LINK_ENQUIRY = self::API_VERSION . '/payment-link/enquiry';
    const PAYMENT_LINK_ACTIONS = self::API_VERSION . '/payment-link/actions';

    // Checkout Utilities
    const CHECKOUT_PAYMENT_MODES = self::API_VERSION . '/payment-modes';
    const CHECKOUT_LIST_BANKS = self::API_VERSION . '/list-of-banks';
    const CHECKOUT_LIST_WALLETS = self::API_VERSION . '/list-of-wallets';
    const CHECKOUT_LIST_EMIS = self::API_VERSION . '/emis';
    const CHECKOUT_OFFERS = self::API_VERSION . '/offers';
    const CHECKOUT_GET_BIN_DATA = self::API_VERSION . '/get-bin-data';
    const CHECKOUT_GET_CARD_DETAILS = self::API_VERSION . '/cards';
    const CHECKOUT_VALIDATE_VPA = self::API_VERSION . '/validate-vpa';
    const CHECKOUT_GET_UPI_APP_DETAILS = self::API_VERSION . '/get-upi-app-details';

    // Transactions
    const TRANSACTION_ENQUIRY = self::API_VERSION . '/transaction-enquiry';

    // Refunds
    const REFUND_INITIATE = self::API_VERSION . '/refund';

    // Pre-auth actions (capture / void)
    const CAPTURE = self::API_VERSION . '/capture';
    const VOID = self::API_VERSION . '/void';

    // Auth
    const AUTH_GENERATE_TOKEN = self::API_VERSION . '/generate-token';
    const AUTH_REFRESH_TOKEN = self::API_VERSION . '/refresh-token';

    // HTTP Methods
    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const HTTP_PATCH = 'PATCH';
    const HTTP_PUT = 'PUT';
    const HTTP_DELETE = 'DELETE';

    // Token expiration threshold (in seconds)
    // Tokens are considered expired if they will expire within this threshold.
    // Keeping this buffer small ensures token caching remains effective.
    const TOKEN_EXPIRATION_THRESHOLD_SECONDS = 60; // 1 minute buffer

    // HTTP client timeout (in seconds)
    // Default timeout for all HTTP requests (read/write operations)
    const DEFAULT_HTTP_TIMEOUT_SECONDS = 60; // 1 minute

    // Retry configuration
    // Number of retry attempts for failed requests (1 = 1 retry = 2 total attempts)
    // When authentication failure (401/403) is detected, tokens are cleared and request is retried
    const DEFAULT_RETRY_COUNT = 1;
}

