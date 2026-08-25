<?php

namespace Nimbbl\Api\Common;

class SdkConstants
{
    // SDK identifiers
    const SDK_NAME = 'Nimbbl PHP SDK';
    const SDK_VERSION = '4.1.0';

    // Signature Constants
    const SIGNATURE_VERSION_V3 = 'v3';

    // Webhook/Callback payload version. When the response carries `version` == v4,
    // the SDK uses the new envelope handling; otherwise it falls back to legacy handling.
    const WEBHOOK_CALLBACK_VERSION_V4 = 'v4';

    // Component Names (SDK Classes) - used for APITag in logs
    const COMPONENT_ORDER = 'Order';
    const COMPONENT_ADDRESS = 'Address';
    const COMPONENT_PAYMENT = 'Payment';
    const COMPONENT_PAYMENT_LINK = 'PaymentLink';
    const COMPONENT_REQUEST = 'Request';
    const COMPONENT_CHECKOUT_UTILITIES = 'CheckoutUtilities';
    const COMPONENT_TRANSACTION = 'Transaction';
    const COMPONENT_REFUND = 'Refund';
    const COMPONENT_CAPTURE = 'Capture';
    const COMPONENT_VOID = 'Void';
    const COMPONENT_AUTH = 'Auth';
    const COMPONENT_SDK = 'SDK';
    const COMPONENT_WEBHOOK = 'Webhook';
    const COMPONENT_NIMBBL_CLIENT = 'NimbblClient';
    const COMPONENT_ENCRYPTION = 'Encryption';
    const COMPONENT_ENCRYPTED_PAYLOAD_HELPER = 'EncryptedPayloadHelper';
    const COMPONENT_PAYLOAD_HELPER_UTILS = 'PayloadHelperUtils';
    const COMPONENT_SIGNATURE_VERIFIER = 'SignatureVerifier';
    const COMPONENT_CHECKOUT_CLIENT = 'CheckoutClient';

    /**
     * Map filename to component name for consistent APITag in logs
     */
    const FILENAME_TO_COMPONENT = [
        'Order.php' => self::COMPONENT_ORDER,
        'Addresses.php' => self::COMPONENT_ADDRESS,
        'Payment.php' => self::COMPONENT_PAYMENT,
        'PaymentLink.php' => self::COMPONENT_PAYMENT_LINK,
        'Request.php' => self::COMPONENT_REQUEST,
        'CheckoutUtilities.php' => self::COMPONENT_CHECKOUT_UTILITIES,
        'Transaction.php' => self::COMPONENT_TRANSACTION,
        'Refund.php' => self::COMPONENT_REFUND,
        'Auth.php' => self::COMPONENT_AUTH,
        'Webhook.php' => self::COMPONENT_WEBHOOK,
        'NimbblClient.php' => self::COMPONENT_NIMBBL_CLIENT,
        'Encryption.php' => self::COMPONENT_ENCRYPTION,
        'EncryptedPayloadHelper.php' => self::COMPONENT_ENCRYPTED_PAYLOAD_HELPER,
        'PayloadHelperUtils.php' => self::COMPONENT_PAYLOAD_HELPER_UTILS,
        'SignatureVerifier.php' => self::COMPONENT_SIGNATURE_VERIFIER,
        'CheckoutClient.php' => self::COMPONENT_CHECKOUT_CLIENT,
    ];

    /**
     * Get component name from filename
     * 
     * @param string $filename The filename (e.g., 'Order.php')
     * @return string The component name (e.g., 'Order')
     */
    public static function getComponentFromFilename($filename)
    {
        return self::FILENAME_TO_COMPONENT[$filename] ?? pathinfo($filename, PATHINFO_FILENAME);
    }
}

