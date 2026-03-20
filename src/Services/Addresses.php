<?php

namespace Nimbbl\Api\Services;

use Nimbbl\Api\RestClient\Request;
use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\ErrorMessages;
use Nimbbl\Api\Common\HttpStatusCodes;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\ErrorCodes;

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
class Addresses
{

    /**
     * List addresses
     * API: https://nimbbl.biz/docs/api-reference/list-addresses-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $options
     * @return array
     */
    public function listAddresses($options = array(), $token = null)
    {
        // Pass options directly - Request will build query string automatically
        $request = new Request();
        return $request->request(ApiConstants::HTTP_GET, ApiConstants::ADDRESS_LIST, $options, $token, SdkConstants::COMPONENT_ADDRESS);
    }

    /**
     * Create address
     * API: https://nimbbl.biz/docs/api-reference/create-an-address-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $request
     * @return array
     */
    public function createAddress($request, $token = null)
    {
        $apiHttp = new Request();
        return $apiHttp->request(ApiConstants::HTTP_POST, ApiConstants::ADDRESS_CREATE, $request, $token, SdkConstants::COMPONENT_ADDRESS);
    }

    /**
     * Update address
     * API: https://nimbbl.biz/docs/api-reference/update-an-address-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param string $id
     * @param array $request
     * @return array
     */
    public function updateAddress($id, $request, $token = null)
    {
        // Per docs, Update Address expects an `address` object in the request body, including `address_id`.
        // Ref: https://nimbbl.biz/docs/api-reference/update-an-address-v-3/
        // Use request as-is - it should already have the correct structure with address_id inside

        // Endpoint is `v3/addresses` (no /{id})
        $apiHttp = new Request();
        return $apiHttp->request(ApiConstants::HTTP_PATCH, ApiConstants::ADDRESS_UPDATE, $request, $token, SdkConstants::COMPONENT_ADDRESS);
    }

    /**
     * Delete address
     * API: https://nimbbl.biz/docs/api-reference/delete-an-address-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param string $id
     * @return array
     */
    public function deleteAddress($id, $token = null)
    {
        // Use query parameter as per API documentation
        $request = [JsonKeys::ADDRESS_ID => $id];
        $apiHttp = new Request();
        return $apiHttp->request(ApiConstants::HTTP_DELETE, ApiConstants::ADDRESS_DELETE, $request, $token, SdkConstants::COMPONENT_ADDRESS);
    }

    /**
     * Import addresses
     * API: https://nimbbl.biz/docs/api-reference/import-addresses-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $request
     * @return array
     */
    public function importAddresses($request, $token = null)
    {
        $apiHttp = new Request();
        return $apiHttp->request(ApiConstants::HTTP_POST, ApiConstants::ADDRESS_IMPORT, $request, $token, SdkConstants::COMPONENT_ADDRESS);
    }

    /**
     * Check address eligibility
     * API: https://nimbbl.biz/docs/api-reference/check-address-eligibility-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $request
     * @return array
     */
    public function checkAddressEligibility($request, $token = null)
    {
        // Validate required parameter
        if (empty($request[JsonKeys::PINCODE])) {
            throw new \Nimbbl\Api\Exception\NimbblException(
                ErrorMessages::PINCODE_REQUIRED,
                ErrorCodes::PINCODE_REQUIRED,
                null,
                HttpStatusCodes::BAD_REQUEST,
                [JsonKeys::PINCODE => $request[JsonKeys::PINCODE] ?? null]
            );
        }

        // Use GET with query parameters as per API documentation
        $apiHttp = new Request();
        return $apiHttp->request(ApiConstants::HTTP_GET, ApiConstants::ADDRESS_CHECK_ELIGIBILITY, $request, $token, SdkConstants::COMPONENT_ADDRESS);
    }

    /**
     * Link address with order
     * API: https://nimbbl.biz/docs/api-reference/link-address-with-order-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $request
     * @return array
     */
    public function linkAddressWithOrder($request, $token = null)
    {
        $apiHttp = new Request();
        return $apiHttp->request(ApiConstants::HTTP_POST, ApiConstants::ADDRESS_LINK_ORDER, $request, $token, SdkConstants::COMPONENT_ADDRESS);
    }

}
