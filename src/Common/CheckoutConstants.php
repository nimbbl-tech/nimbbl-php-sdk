<?php

namespace Nimbbl\Api\Common;

/**
 * Constants for checkout payment modes and option keys
 * 
 * Centralized constants for checkout-related values.
 */
class CheckoutConstants
{
    // Payment Mode Codes
    const PAYMENT_MODE_NET_BANKING = 'net_banking';
    const PAYMENT_MODE_WALLET = 'wallet';
    const PAYMENT_MODE_UPI = 'upi';
    const PAYMENT_MODE_CREDIT_CARD = 'credit_card';
    const PAYMENT_MODE_DEBIT_CARD = 'debit_card';
    const PAYMENT_MODE_EMI = 'emi';
    const PAYMENT_MODE_ALL = 'allpayment';

    // Checkout Modes
    const CHECKOUT_MODE_POPUP = 'popup';
    const CHECKOUT_MODE_REDIRECT = 'redirect';

    // Checkout Option Keys
    const OPTION_KEY_BANK_CODE = 'bank_code';
    const OPTION_KEY_WALLET_CODE = 'wallet_code';
    const OPTION_KEY_PAYMENT_FLOW = 'payment_flow';
    const OPTION_KEY_EMI_CODE = 'emi_code';
    const OPTION_KEY_PAYMENT_MODE_CODE = 'payment_mode_code';
    const OPTION_KEY_CALLBACK_URL = 'callback_url';
    const OPTION_KEY_CALLBACK_HANDLER = 'callback_handler';
    const OPTION_KEY_CALLBACK_HANDLER_JS = 'callback_handler_js';
    const OPTION_KEY_HANDLER_POST_URL = 'handler_post_url';
    const OPTION_KEY_UPI_ID = 'upi_id';
    const OPTION_KEY_UPI_APP_CODE = 'upi_app_code';

    // Checkout Config Keys
    const CONFIG_KEY_TOKEN = 'token';
    const CONFIG_KEY_API_HOST = 'apiHost';
    const CONFIG_KEY_CHECKOUT_HOST = 'checkoutHost';

    // Default Values
    const DEFAULT_MODE = self::CHECKOUT_MODE_POPUP;
    const DEFAULT_HANDLER_POST_URL = 'handle-callback';
    const DEFAULT_CALLBACK_ROUTE = 'payment-callback';
}
