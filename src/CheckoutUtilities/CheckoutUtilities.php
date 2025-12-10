<?php

namespace Nimbbl\Api;

use Exception;

/**
 * Nimbbl Checkout Utilities API Client
 * 
 * Based on [Nimbbl Checkout Utilities API Reference](https://nimbbl.biz/docs/category/api-reference/checkout-utilities/)
 * 
 * API Documentation:
 * - [List of Payment Modes v3](https://nimbbl.biz/docs/api-reference/list-of-payment-modes-v-3/)
 * - [List of Banks v3](https://nimbbl.biz/docs/api-reference/list-of-banks-v-3/)
 * - [List of Wallets v3](https://nimbbl.biz/docs/api-reference/list-of-wallets-v-3/)
 * - [List of EMIs v3](https://nimbbl.biz/docs/api-reference/list-of-em-is-v-3/)
 * - [Validate UPI VPA v3](https://nimbbl.biz/docs/api-reference/validate-upi-vpa-v-3/)
 * - [Get Card BIN Data v3](https://nimbbl.biz/docs/api-reference/get-card-bin-data-v-3/)
 * - [Offers v3](https://nimbbl.biz/docs/api-reference/offers-v-3/)
 * - [Get UPI App Details v3](https://nimbbl.biz/docs/api-reference/get-upi-app-details-v-3/)
 * 
 * Key Details:
 * - All checkout utility APIs are available in both S2S and Client SDKs (read-only, use JWT token)
 * - Banks, Wallets, and EMIs APIs require order context (order_id, amount, etc.)
 * - Offers API returns available offers based on order and customer context
 * - Payment Modes API shows all payment modes enabled for the merchant, personalized for the customer
 */
#[AllowDynamicProperties]
class CheckoutUtilities
{
    /**
     * List payment modes
     * API: https://nimbbl.biz/docs/api-reference/list-of-payment-modes-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function listPaymentModes($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::CHECKOUT_PAYMENT_MODES, $attributes, $token, SdkConstants::COMPONENT_CHECKOUT_UTILITIES);
    }

    /**
     * List banks
     * API: https://nimbbl.biz/docs/api-reference/list-of-banks-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function listBanks($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::CHECKOUT_LIST_BANKS, $attributes, $token, SdkConstants::COMPONENT_CHECKOUT_UTILITIES);
    }

    /**
     * List wallets
     * API: https://nimbbl.biz/docs/api-reference/list-of-wallets-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function listWallets($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::CHECKOUT_LIST_WALLETS, $attributes, $token, SdkConstants::COMPONENT_CHECKOUT_UTILITIES);
    }

    /**
     * List EMIs
     * API: https://nimbbl.biz/docs/api-reference/list-of-em-is-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function listEMIs($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::CHECKOUT_LIST_EMIS, $attributes, $token, SdkConstants::COMPONENT_CHECKOUT_UTILITIES);
    }

    /**
     * Get offers
     * API: https://nimbbl.biz/docs/api-reference/offers-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function getOffers($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::CHECKOUT_OFFERS, $attributes, $token, SdkConstants::COMPONENT_CHECKOUT_UTILITIES);
    }

    /**
     * Get card BIN data
     * API: https://nimbbl.biz/docs/api-reference/get-card-bin-data-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function getCardBinData($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::CHECKOUT_GET_BIN_DATA, $attributes, $token, SdkConstants::COMPONENT_CHECKOUT_UTILITIES);
    }

    /**
     * Validate UPI VPA
     * API: https://nimbbl.biz/docs/api-reference/validate-upi-vpa-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function validateUpiVpa($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::CHECKOUT_VALIDATE_VPA, $attributes, $token, SdkConstants::COMPONENT_CHECKOUT_UTILITIES);
    }

    /**
     * Get UPI app details
     * API: https://nimbbl.biz/docs/api-reference/get-upi-app-details-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function getUpiAppDetails($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::CHECKOUT_GET_UPI_APP_DETAILS, $attributes, $token, SdkConstants::COMPONENT_CHECKOUT_UTILITIES);
    }
}

