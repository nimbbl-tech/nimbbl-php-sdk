<?php

namespace Nimbbl\Api\Services;

use Exception;
use Nimbbl\Api\RestClient\Request;
use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\ErrorMessages;
use Nimbbl\Api\Exception\NimbblException;

/**
 * Nimbbl Payments API Client
 * 
 * Based on [Nimbbl Payments API Reference](https://nimbbl.biz/docs/category/api-reference/payments/)
 * 
 * API Documentation:
 * - [Initiate a Payment v3](https://nimbbl.biz/docs/api-reference/initiate-a-payment-v-3/)
 * - [Complete a Payment v3](https://nimbbl.biz/docs/api-reference/complete-a-payment-v-3/)
 * - [Resend an OTP v3](https://nimbbl.biz/docs/api-reference/resend-an-otp-v-3/)
 * 
 * Key Details:
 * - Payment flow can be `otp` or `auto_debit`
 * - For `otp` flow, customer needs to enter OTP; for `auto_debit`, payment completes automatically
 * - OTP can be sent encrypted for security
 * - Complete Payment API is used for certain Pay Later providers where native OTP experience is required
 */
#[\AllowDynamicProperties]
class Payment
{
    /**
     * Initiate payment
     * API: https://nimbbl.biz/docs/api-reference/initiate-a-payment-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $attributes
     * @return array
     */
    public function initiatePayment($attributes, $token = null)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::PAYMENT_INITIATE, $attributes, $token, SdkConstants::COMPONENT_PAYMENT);
    }

    /**
     * Complete payment
     * API: https://nimbbl.biz/docs/api-reference/complete-a-payment-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $attributes
     * @return array
     */
    public function completePayment($attributes, $token = null)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::PAYMENT_COMPLETE, $attributes, $token, SdkConstants::COMPONENT_PAYMENT);
    }

    /**
     * Resend payment OTP
     * API: https://nimbbl.biz/docs/api-reference/resend-an-otp-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $attributes
     * @return array
     */
    public function resendPaymentOtp($attributes, $token = null)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::PAYMENT_RESEND_OTP, $attributes, $token, SdkConstants::COMPONENT_PAYMENT);
    }
}

