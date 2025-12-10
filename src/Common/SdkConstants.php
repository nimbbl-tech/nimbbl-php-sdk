<?php

namespace Nimbbl\Api;

class SdkConstants
{
    // SDK identifiers
    const SDK_NAME = 'NimbblPHP';
    const SDK_VERSION = '4.0.0';

    // Log Levels
    const LOG_ERROR = 'ERROR';
    const LOG_REQUEST = 'REQUEST';
    const LOG_RESPONSE = 'RESPONSE';
    const LOG_INFO = 'INFO';
    const LOG_DEBUG = 'DEBUG';
    const LOG_WARNING = 'WARNING';
    const LOG_DESERIALIZATION_ERROR = 'DESERIALIZATION_ERROR';

    // Component Names (SDK Classes)
    const COMPONENT_ORDER = 'Order';
    const COMPONENT_ADDRESS = 'Address';
    const COMPONENT_PAYMENT = 'Payment';
    const COMPONENT_PAYMENT_LINK = 'PaymentLink';
    const COMPONENT_REQUEST = 'Request';
    const COMPONENT_CHECKOUT_UTILITIES = 'CheckoutUtilities';
    const COMPONENT_TRANSACTION = 'Transaction';
    const COMPONENT_REFUND = 'Refund';
    const COMPONENT_AUTH = 'Auth';
    const COMPONENT_SDK = 'SDK';
    const COMPONENT_WEBHOOK = 'Webhook';
}

