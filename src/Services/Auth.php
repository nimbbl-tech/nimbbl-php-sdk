<?php

namespace Nimbbl\Api\Services;

use Nimbbl\Api\RestClient\Request;
use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\Common\HttpStatusCodes;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\ErrorCodes;
use Nimbbl\Api\Common\ErrorMessages;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Exception\NimbblException;

/**
 * Nimbbl Authentication API Client
 * 
 * Based on [Nimbbl Authorization API Reference](https://nimbbl.biz/docs/category/api-reference/authorization/)
 * 
 * API Documentation:
 * - [Generate Token v3](https://nimbbl.biz/docs/api-reference/generate-token-v-3/)
 * - [Refresh Token v3](https://nimbbl.biz/docs/api-reference/refresh-token-v-3/)
 * 
 * Key Details:
 * - Generate Token: Uses access_key and access_secret to generate a new authentication token
 * - Refresh Token: Uses a refresh_token (from order creation) to generate a new token
 * - Refresh Token API requires Bearer authentication with a regular token
 */
class Auth
{
    /**
     * Generate token
     * API: https://nimbbl.biz/docs/api-reference/generate-token-v-3/
     * @param array|null $attributes
     * @return array
     */
    public function generateToken($attributes = null)
    {
        // Delegate to Request::generateToken() which uses NimbblClient::getKey() and getSecret()
        // This avoids code duplication and maintains consistency
        $request = new Request();
        return $request->generateToken();
    }

    /**
     * Refresh token
     * API: https://nimbbl.biz/docs/api-reference/refresh-token-v-3/
     * @param string $refreshToken
     * @param string $token
     * @return array
     */
    public function refreshToken($refreshToken, $token)
    {
        if (empty($refreshToken)) {
            throw new NimbblException(
                ErrorMessages::REFRESH_TOKEN_REQUIRED,
                ErrorCodes::REFRESH_TOKEN_REQUIRED,
                null,
                HttpStatusCodes::BAD_REQUEST
            );
        }

        if (empty($token)) {
            throw new NimbblException(
                ErrorMessages::TOKEN_REQUIRED,
                ErrorCodes::TOKEN_REQUIRED,
                null,
                HttpStatusCodes::UNAUTHORIZED
            );
        }

        $attributes = [
            JsonKeys::REFRESH_TOKEN => $refreshToken
        ];

        $request = new Request();
        return $request->request(
            ApiConstants::HTTP_POST,
            ApiConstants::AUTH_REFRESH_TOKEN,
            $attributes,
            $token, // Bearer token required for refresh token API
            SdkConstants::COMPONENT_AUTH
        );
    }

}
