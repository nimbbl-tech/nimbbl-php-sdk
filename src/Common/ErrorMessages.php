<?php

namespace Nimbbl\Api\Common;

/**
 * Error Messages Constants
 * 
 * Centralized error messages used throughout the SDK.
 * This ensures consistency and makes it easier to maintain and update error messages.
 */
class ErrorMessages
{
    // Token-related errors
    const TOKEN_REQUIRED = 'Token is required. Please provide a token parameter.';
    const ACCESS_KEY_SECRET_REQUIRED = 'access_key and access_secret are required for generating a token.';
    const REFRESH_TOKEN_REQUIRED = 'refresh_token is required for refreshing a token.';

    // Validation errors
    const PINCODE_REQUIRED = 'pincode is required for address eligibility check.';
    const ACTION_REQUIRED = 'action field is required. Possible values: send, cancel';
    const ACTION_INVALID = 'Invalid action value. Possible values: send, cancel';
    const IDENTIFIER_REQUIRED = 'Either invoice_id or payment_link_id must be provided in the attributes array.';

    // Unsupported operation errors
    const UNSUPPORTED_OPERATION = 'Unsupported operation.';
    const UNSUPPORTED_OPERATION_UPDATE = 'Unsupported operation. Use update() method instead.';
    const UNSUPPORTED_OPERATION_ORDER_MODIFY = 'Unsupported operation. Orders cannot be modified after creation.';
    const RETRIEVE_ONE_NOT_SUPPORTED = 'retrieveOne() is not part of the official API for this entity type.';


    // Error prefixes
    const ERROR_PREFIX_CREATION = 'creation error: ';
    const ERROR_PREFIX_UPDATE = 'update error: ';
    const ERROR_PREFIX_DELETION = 'deletion error: ';
    const ERROR_PREFIX_INITIATION = 'initiation error: ';
    const ERROR_PREFIX_COMPLETION = 'completion error: ';
    const ERROR_PREFIX_ENQUIRY = 'enquiry error: ';
    const ERROR_PREFIX_ACTIONS = 'actions error: ';
    const ERROR_PREFIX_GENERAL = 'error: ';

    // Webhook messages
    const WEBHOOK_VERIFICATION_FAILED_MISSING_PARAMS = 'Webhook verification failed: Missing payload, signature, or secret';
    const WEBHOOK_SIGNATURE_VERIFICATION_FAILED = 'Webhook signature verification failed';
    const WEBHOOK_SIGNATURE_VERIFICATION_SUCCESS = 'Webhook signature verification successful';
    const WEBHOOK_VERIFICATION_ERROR = 'Webhook verification ';
    const WEBHOOK_PARSE_ERROR_EMPTY_PAYLOAD = 'Webhook parse error: Empty payload';
    const WEBHOOK_PARSE_ERROR_JSON = 'Webhook parse error: ';
    const WEBHOOK_PARSED_SUCCESS = 'Webhook parsed successfully. Event: ';
    const WEBHOOK_PARSE_ERROR = 'Webhook parse ';
    const WEBHOOK_VERIFY_AND_PARSE_ERROR = 'Webhook verify and parse ';
    const WEBHOOK_GET_SIGNATURE_FROM_HEADERS_ERROR = 'Webhook get signature from headers ';
    const WEBHOOK_GET_PAYLOAD_FROM_INPUT_ERROR = 'Webhook get payload from input ';
    const WEBHOOK_GET_PAYLOAD_FROM_INPUT_FAILED = 'Webhook get payload from input: Failed to read from php://input';
    const WEBHOOK_PAYLOAD_READ_FAILED = 'Failed to read webhook payload from input stream';

    // Payment signature verification messages
    const SIGNATURE_VERIFICATION_FAILED_MISSING_PARAMS = 'Signature verification failed: Missing secret, transaction ID, signature, or amount';
    const SIGNATURE_VERIFICATION_FAILED = 'Signature verification failed';
    const SIGNATURE_VERIFICATION_SUCCESS = 'Signature verification successful';
    const SIGNATURE_VERIFICATION_ERROR = 'Signature verification ';

    // Legacy constants for backward compatibility
    const PAYMENT_SIGNATURE_VERIFICATION_FAILED_MISSING_PARAMS = 'Signature verification failed: Missing secret, transaction ID, signature, or amount';
    const PAYMENT_SIGNATURE_VERIFICATION_FAILED = 'Signature verification failed';
    const PAYMENT_SIGNATURE_VERIFICATION_SUCCESS = 'Signature verification successful';
    const PAYMENT_SIGNATURE_VERIFICATION_ERROR = 'Signature verification ';

    // Error codes
    const ERROR_CODE_SIGNATURE_VERIFICATION_MISSING_SECRET = 'SIGNATURE_VERIFICATION_MISSING_SECRET';
    const ERROR_CODE_SIGNATURE_VERIFICATION_MISSING_PARAMS = 'SIGNATURE_VERIFICATION_MISSING_PARAMS';
    const ERROR_CODE_SIGNATURE_VERIFICATION_FAILED = 'SIGNATURE_VERIFICATION_FAILED';
    const ERROR_CODE_SIGNATURE_VERIFICATION_ERROR = 'SIGNATURE_VERIFICATION_ERROR';
    const ERROR_CODE_DESERIALIZATION_ERROR = 'DESERIALIZATION_ERROR';
    const ERROR_CODE_SDK_EXCEPTION = 'SDK_EXCEPTION';
    const ERROR_CODE_UNSUPPORTED_OPERATION = 'UNSUPPORTED_OPERATION';
    const ERROR_CODE_AUTH_ERROR = 'AUTH_ERROR';

    // Error response keys
    const ERROR_KEY_ERROR_CODE = 'nimbbl_error_code';
    const ERROR_KEY_MERCHANT_MESSAGE = 'nimbbl_merchant_message';
    const RESPONSE_KEY_SUCCESS = 'success';
    const RESPONSE_KEY_ERROR = 'error';
    const RESPONSE_KEY_MESSAGE = 'message';
    const RESPONSE_KEY_RAW_BODY = 'raw_body';

    // Common messages
    const MESSAGE_OPERATION_COMPLETED_SUCCESSFULLY = 'Operation completed successfully';
    const MESSAGE_UNABLE_TO_PARSE_JSON = 'Unable to parse response body as JSON';
    const MESSAGE_API_REQUEST_FAILED = 'API request failed';
    const MESSAGE_API_REQUEST_SUCCESSFUL = 'API request successful';
    const MESSAGE_AUTHENTICATION_FAILED = 'Authentication failed';
    const MESSAGE_UNKNOWN_ERROR = 'Unknown error';

    // Encryption error messages (format strings)
    // {0} = payload type/name (e.g., "refund", "order", "transaction enquiry", "list banks", "list wallets")
    // {1} = exception error message
    const ENCRYPTION_ERROR_FORMAT = 'Failed to encrypt %s payload: %s';

    // Signature/Webhook Verification Messages
    const MESSAGE_SIGNATURE_VERIFICATION_SUCCESS = 'Signature verification succeeded';
    const MESSAGE_SIGNATURE_VERIFICATION_FAILED = 'Signature verification failed';
    const MESSAGE_SIGNATURE_VERIFICATION_MISSING_PARAMS = 'Signature verification failed - Missing parameters';
    const MESSAGE_WEBHOOK_VERIFICATION_FAILED = 'Webhook verification failed';
    const MESSAGE_WEBHOOK_PARSE_ERROR = 'Webhook parse error';
}

