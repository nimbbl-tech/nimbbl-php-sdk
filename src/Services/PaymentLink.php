<?php

namespace Nimbbl\Api\Services;

use Nimbbl\Api\RestClient\Request;
use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\Common\ErrorCodes;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\HttpStatusCodes;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\ErrorMessages;
use Nimbbl\Api\Exception\NimbblException;

/**
 * Nimbbl Payment Link API Client
 * 
 * Based on [Nimbbl Payment Link API Reference](https://nimbbl.biz/docs/category/api-reference/payment-link/)
 * 
 * API Documentation:
 * - [Create a Payment Link v3](https://nimbbl.biz/docs/api-reference/create-a-payment-link-v-3/)
 * - [Update a Payment Link v3](https://nimbbl.biz/docs/api-reference/update-a-payment-link-v-3/)
 * - [Payment Link Actions v3](https://nimbbl.biz/docs/api-reference/payment-link-actions-v-3/)
 * - [Payment Link Enquiry v3](https://nimbbl.biz/docs/api-reference/payment-link-enquiry-v-3/)
 */
class PaymentLink
{
    /**
     * Validate that either invoice_id or payment_link_id is provided
     * 
     * @param array $attributes Attributes array to validate
     * @throws NimbblException If neither identifier is provided
     */
    private function validatePaymentLinkIdentifier($attributes)
    {
        $hasInvoiceId = isset($attributes[JsonKeys::INVOICE_ID]) && trim($attributes[JsonKeys::INVOICE_ID]) !== '';
        $hasPaymentLinkId = isset($attributes[JsonKeys::PAYMENT_LINK_ID]) && trim($attributes[JsonKeys::PAYMENT_LINK_ID]) !== '';

        if (!$hasInvoiceId && !$hasPaymentLinkId) {
            throw new NimbblException(
                ErrorMessages::IDENTIFIER_REQUIRED,
                ErrorCodes::IDENTIFIER_REQUIRED,
                null,
                HttpStatusCodes::BAD_REQUEST
            );
        }
    }

    /**
     * Create payment link
     * API: https://nimbbl.biz/docs/api-reference/create-a-payment-link-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $attributes
     * @return array
     */
    public function createPaymentLink($attributes, $token = null)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::PAYMENT_LINK_CREATE, $attributes, $token, SdkConstants::COMPONENT_PAYMENT_LINK);
    }

    /**
     * Update payment link
     * API: https://nimbbl.biz/docs/api-reference/update-a-payment-link-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $attributes
     * @return array
     */
    public function updatePaymentLink($attributes, $token = null)
    {
        // Validate that either invoice_id or payment_link_id is provided
        $this->validatePaymentLinkIdentifier($attributes);

        $request = new Request();
        return $request->request(ApiConstants::HTTP_PATCH, ApiConstants::PAYMENT_LINK_UPDATE, $attributes, $token, SdkConstants::COMPONENT_PAYMENT_LINK);
    }

    /**
     * Payment link enquiry
     * API: https://nimbbl.biz/docs/api-reference/payment-link-enquiry-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $attributes
     * @return array
     */
    public function enquiryPaymentLink($attributes, $token = null)
    {
        // Validate that either invoice_id or payment_link_id is provided
        $this->validatePaymentLinkIdentifier($attributes);

        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::PAYMENT_LINK_ENQUIRY, $attributes, $token, SdkConstants::COMPONENT_PAYMENT_LINK);
    }

    /**
     * Payment link actions
     * API: https://nimbbl.biz/docs/api-reference/payment-link-actions-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $attributes
     * @return array
     */
    public function performPaymentLinkActions($attributes, $token = null)
    {
        // Validate that either invoice_id or payment_link_id is provided
        $this->validatePaymentLinkIdentifier($attributes);

        // Validate that action is provided
        if (empty($attributes[JsonKeys::ACTION]) || trim($attributes[JsonKeys::ACTION]) === '') {
            throw new NimbblException(
                ErrorMessages::ACTION_REQUIRED,
                ErrorCodes::ACTION_REQUIRED,
                null,
                HttpStatusCodes::BAD_REQUEST
            );
        }

        // Validate action value
        $action = trim($attributes[JsonKeys::ACTION]);
        if (!in_array($action, ['send', 'cancel'])) {
            throw new NimbblException(
                ErrorMessages::ACTION_INVALID,
                ErrorCodes::INVALID_ACTION,
                null,
                HttpStatusCodes::BAD_REQUEST
            );
        }

        $endpoint = ApiConstants::PAYMENT_LINK_ACTIONS;
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, $endpoint, $attributes, $token, SdkConstants::COMPONENT_PAYMENT_LINK);
    }
}

