<?php

namespace Nimbbl\Api;

use Exception;
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
#[AllowDynamicProperties]
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
        // Delegate to Request::generateToken() which uses Api::getKey() and getSecret()
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
                'REFRESH_TOKEN_REQUIRED',
                400
            );
        }
        
        if (empty($token)) {
            throw new NimbblException(
                ErrorMessages::TOKEN_REQUIRED,
                'TOKEN_REQUIRED',
                401
            );
        }
        
        $attributes = [
            'refresh_token' => $refreshToken
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

