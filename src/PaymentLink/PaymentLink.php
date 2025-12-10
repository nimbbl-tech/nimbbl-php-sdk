<?php

namespace Nimbbl\Api;

use Exception;
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
#[AllowDynamicProperties]
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
        $hasInvoiceId = isset($attributes['invoice_id']) && trim($attributes['invoice_id']) !== '';
        $hasPaymentLinkId = isset($attributes['payment_link_id']) && trim($attributes['payment_link_id']) !== '';
        
        if (!$hasInvoiceId && !$hasPaymentLinkId) {
            throw new NimbblException(
                ErrorMessages::IDENTIFIER_REQUIRED,
                'IDENTIFIER_REQUIRED',
                400
            );
        }
    }

    /**
     * Create payment link
     * API: https://nimbbl.biz/docs/api-reference/create-a-payment-link-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function createPaymentLink($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::PAYMENT_LINK_CREATE, $attributes, $token, SdkConstants::COMPONENT_PAYMENT_LINK);
    }

    /**
     * Update payment link
     * API: https://nimbbl.biz/docs/api-reference/update-a-payment-link-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function updatePaymentLink($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        // Validate that either invoice_id or payment_link_id is provided
        $this->validatePaymentLinkIdentifier($attributes);
        
        $request = new Request();
        return $request->request(ApiConstants::HTTP_PATCH, ApiConstants::PAYMENT_LINK_UPDATE, $attributes, $token, SdkConstants::COMPONENT_PAYMENT_LINK);
    }

    /**
     * Payment link enquiry
     * API: https://nimbbl.biz/docs/api-reference/payment-link-enquiry-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function enquiryPaymentLink($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        // Validate that either invoice_id or payment_link_id is provided
        $this->validatePaymentLinkIdentifier($attributes);
        
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::PAYMENT_LINK_ENQUIRY, $attributes, $token, SdkConstants::COMPONENT_PAYMENT_LINK);
    }

    /**
     * Payment link actions
     * API: https://nimbbl.biz/docs/api-reference/payment-link-actions-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function performPaymentLinkActions($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        // Validate that either invoice_id or payment_link_id is provided
        $this->validatePaymentLinkIdentifier($attributes);
        
        // Validate that action is provided
        if (empty($attributes['action']) || trim($attributes['action']) === '') {
            throw new NimbblException(
                ErrorMessages::ACTION_REQUIRED,
                'ACTION_REQUIRED',
                400
            );
        }
        
        // Validate action value
        $action = trim($attributes['action']);
        if (!in_array($action, ['send', 'cancel'])) {
            throw new NimbblException(
                ErrorMessages::ACTION_INVALID,
                'INVALID_ACTION',
                400
            );
        }
        
        $endpoint = ApiConstants::PAYMENT_LINK_ACTIONS . '/actions';
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, $endpoint, $attributes, $token, SdkConstants::COMPONENT_PAYMENT_LINK);
    }
}

