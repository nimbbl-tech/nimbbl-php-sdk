<?php

namespace Nimbbl\Api\Common;

/**
 * JSON keys used in API requests and responses
 * 
 * Centralized constants for JSON keys to ensure consistency and make it easier to maintain.
 */
class JsonKeys
{
    // Error response keys
    const ERROR_CODE = 'nimbbl_error_code';
    const ERROR_MERCHANT_MESSAGE = 'nimbbl_merchant_message';
    const ERROR_CONSUMER_MESSAGE = 'nimbbl_consumer_message';
    const SUCCESS = 'success';
    const ERROR = 'error';
    const MESSAGE = 'message';
    const VALID = 'valid';
    const CODE = 'code';
    const DESCRIPTION = 'description';
    const RAW_BODY = 'raw_body';
    const RESPONSE = 'response';
    const AUTHORIZATION = 'authorization';

    // Token response/request JSON keys
    const TOKEN = 'token';
    const EXPIRES_AT = 'expires_at';
    const ACCESS_KEY = 'access_key';
    const ACCESS_SECRET = 'access_secret';
    const REFRESH_TOKEN = 'refresh_token';

    // Encryption JSON keys
    const ENCRYPTED_PAYLOAD = 'encrypted_payload';
    const ENCRYPTED_RESPONSE = 'encrypted_response';

    // Request parameter keys
    const INVOICE_ID = 'invoice_id';
    const PAYMENT_LINK_ID = 'payment_link_id';
    const ACTION = 'action';
    const ORDER_ID = 'order_id';
    const NIMBBL_ORDER_ID = 'nimbbl_order_id';
    const TOTAL_AMOUNT = 'total_amount';
    const ADDRESS = 'address';
    const ADDRESS_ID = 'address_id';
    const PINCODE = 'pincode';
    const POSTAL_CODE = 'postal_code';
    const ZIP_CODE = 'zip_code';
    const USER_ID = 'user_id';
    const ADDRESS_1 = 'address_1';
    const STATE = 'state';
    const ADDRESS_TYPE = 'address_type';
    const COUNTRY = 'country';
    const COUNTRY_CODE = 'country_code';
    const LINK_AS = 'link_as';
    const ADDRESSES = 'addresses';
    const FIRST_NAME = 'first_name';
    const LAST_NAME = 'last_name';
    const CARD_HOLDER_NAME = 'card_holder_name';
    // Response-side PII field names (webhook/callback payloads use these short forms).
    const NAME = 'name';
    const MOBILE = 'mobile';
    const CARD_HOLDER = 'card_holder';
    const UPI_HOLDER = 'upi_holder';
    const UPI_ID = 'upi_id';
    const WALLET_CODE = 'wallet_code';
    const BANK_CODE = 'bank_code';
    const PAYMENT_MODE_CODE = 'payment_mode_code';
    const USER = 'user';
    const MOBILE_NUMBER = 'mobile_number';
    const EMAIL = 'email';
    const STREET = 'street';
    const LANDMARK = 'landmark';
    const AREA = 'area';
    const CITY = 'city';
    const VPA = 'vpa';
    const CARD_NO = 'card_no';
    const CARD_NUMBER = 'card_number';
    const CVV = 'cvv';
    const EXPIRY_DATE = 'expiry_date';
    const ACCOUNT_NUMBER = 'account_number';
    const ACCOUNT_NO = 'account_no';
    const IFSC_CODE = 'ifsc_code';
    const PAN_CARD = 'pan_card';

    // Signature/Webhook keys
    const SIGNATURE_VERSION = 'signature_version';
    const NIMBBL_SIGNATURE = 'nimbbl_signature';
    const SIGNATURE = 'signature';
    const NIMBBL_TRANSACTION_ID = 'nimbbl_transaction_id';
    const TRANSACTION_ID = 'transaction_id';
    const TRANSACTION_TYPE = 'transaction_type';
    const TRANSACTION_AMOUNT = 'transaction_amount';
    const TRANSACTION_CURRENCY = 'transaction_currency';
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const STATUS = 'status';
    const REFUND_STATUS = 'refund_status';
    const REFUND_AMOUNT = 'refund_amount';
    const REFUND_DETAILS = 'refund_details';
    const REFUNDABLE_CURRENCY = 'refundable_currency';
    const EVENT_TYPE = 'event_type';
    const ORDER = 'order';
    const TRANSACTION = 'transaction';
    const TYPE = 'type';
    const AMOUNT_PAID = 'amount_paid';
    const ORDER_LINE_ITEMS = 'order_line_items';
    const PAYMENT_LINK_AMOUNT_PAID = 'payment_link_amount_paid';
    const PAYMENT_LINK_HASH = 'payment_link_hash';
    const PAYLOAD = 'payload';
    const CALLBACK = 'callback';
    const GLOBAL_HANDLE_CHECKOUT_RESPONSE = 'globalHandleCheckoutResponse';
    const GLOBAL_CLOSE_CHECKOUT_MODAL = 'globalCloseCheckoutModal';

    // Payload version — source of truth for choosing v4 (new) vs legacy handling.
    const VERSION = 'version';
    const SUB_MERCHANT_ID = 'sub_merchant_id';

    // Pre-auth / capture / void keys
    const CAPTURE_STATUS = 'capture_status';
    const VOID_STATUS = 'void_status';
    const CAPTURE_TYPE = 'capture_type';
    const ORIGINAL_PAYMENT_TRANSACTION_ID = 'original_payment_transaction_id';
    const PAYMENT_TRANSACTION_AMOUNT = 'payment_transaction_amount';
    const AUTHORIZATION_DETAILS = 'authorization_details';
    const REVERSAL_REASON = 'reversal_reason';
    const COMMENT = 'comment';
    const NEXT = 'next';
    const LAPSED_REASON = 'lapsed_reason';

    // Checkout callback keys
    const CHECKOUT_STATUS = 'checkout_status';
    const REASON = 'reason';
    const RETRY = 'retry';
}
