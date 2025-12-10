<?php

namespace Nimbbl\Api;

use Exception;

/**
 * Nimbbl Addresses API Client
 * 
 * Based on [Nimbbl Addresses API Reference](https://nimbbl.biz/docs/category/api-reference/addresses/)
 * 
 * API Documentation:
 * - [List Addresses v3](https://nimbbl.biz/docs/api-reference/list-addresses-v-3/)
 * - [Create an Address v3](https://nimbbl.biz/docs/api-reference/create-an-address-v-3/)
 * - [Update an Address v3](https://nimbbl.biz/docs/api-reference/update-an-address-v-3/)
 * - [Delete an Address v3](https://nimbbl.biz/docs/api-reference/delete-an-address-v-3/)
 * - [Import Addresses v3](https://nimbbl.biz/docs/api-reference/import-addresses-v-3/)
 * - [Check Address Eligibility v3](https://nimbbl.biz/docs/api-reference/check-address-eligibility-v-3/)
 * - [Link Address with Order v3](https://nimbbl.biz/docs/api-reference/link-address-with-order-v-3/)
 * 
 * Note: All Address endpoints are marked as **Beta** in the API documentation and may change.
 */
#[AllowDynamicProperties]
class Address
{

    /**
     * List addresses
     * API: https://nimbbl.biz/docs/api-reference/list-addresses-v-3/
     * @param array $options
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function listAddresses($options, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        // Pass options directly - Request will build query string automatically
        $request = new Request();
        return $request->request(ApiConstants::HTTP_GET, ApiConstants::ADDRESS_LIST, $options, $token, SdkConstants::COMPONENT_ADDRESS);
    }

    /**
     * Create address
     * API: https://nimbbl.biz/docs/api-reference/create-an-address-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function createAddress($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::ADDRESS_CREATE, $attributes, $token, SdkConstants::COMPONENT_ADDRESS);
    }

    /**
     * Update address
     * API: https://nimbbl.biz/docs/api-reference/update-an-address-v-3/
     * @param string $id
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function updateAddress($id, $attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        $endpoint = ApiConstants::ADDRESS_UPDATE . '/' . $id;
        $request = new Request();
        return $request->request(ApiConstants::HTTP_PATCH, $endpoint, $attributes, $token, SdkConstants::COMPONENT_ADDRESS);
    }

    /**
     * Delete address
     * API: https://nimbbl.biz/docs/api-reference/delete-an-address-v-3/
     * @param string $id
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function deleteAddress($id, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        // Use query parameter as per API documentation
        // Pass address_id as data array, Request will build query string
        $request = new Request();
        return $request->request(ApiConstants::HTTP_DELETE, ApiConstants::ADDRESS_DELETE, ['address_id' => $id], $token, SdkConstants::COMPONENT_ADDRESS);
    }

    /**
     * Import addresses
     * API: https://nimbbl.biz/docs/api-reference/import-addresses-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function importAddresses($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::ADDRESS_IMPORT, $attributes, $token, SdkConstants::COMPONENT_ADDRESS);
    }

    /**
     * Check address eligibility
     * API: https://nimbbl.biz/docs/api-reference/check-address-eligibility-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function checkAddressEligibility($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        // Validate required parameter
        if (empty($attributes['pincode'])) {
            throw new \Exception(ErrorMessages::PINCODE_REQUIRED);
        }
        
        // Use GET with query parameters as per API documentation
        $request = new Request();
        return $request->request(ApiConstants::HTTP_GET, ApiConstants::ADDRESS_CHECK_ELIGIBILITY, $attributes, $token, SdkConstants::COMPONENT_ADDRESS);
    }

    /**
     * Link address with order
     * API: https://nimbbl.biz/docs/api-reference/link-address-with-order-v-3/
     * @param array $attributes
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function linkAddressWithOrder($attributes, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::ADDRESS_LINK_ORDER, $attributes, $token, SdkConstants::COMPONENT_ADDRESS);
    }

    /**
     * Get address by ID
     * API: https://nimbbl.biz/docs/api-reference/get-an-address-v-3/
     * @param string $addressId
     * @param string $token
     * @param string $apiVersion
     * @return array
     */
    public function getAddressById($addressId, $token, $apiVersion = ApiConstants::API_VERSION)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_GET, ApiConstants::ADDRESS_GET . '/' . $addressId, [], $token, SdkConstants::COMPONENT_ADDRESS);
    }

}
