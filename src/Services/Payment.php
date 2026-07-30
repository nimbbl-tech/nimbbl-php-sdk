<?php

namespace Nimbbl\Api\Services;

use Nimbbl\Api\RestClient\Request;
use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\EncryptedPayloadHelper;
use Nimbbl\Api\Log\Logger;

/**
 * Nimbbl Payments API Client
 *
 * Based on [Nimbbl Payments API Reference](https://nimbbl.biz/docs/category/api-reference/payments/)
 *
 * API Documentation:
 * - [Initiate a Payment v3](https://nimbbl.biz/docs/api-reference/initiate-a-payment-v-3/)
 * - [Complete a Payment v3](https://nimbbl.biz/docs/api-reference/complete-a-payment-v-3/)
 * - [Resend an OTP v3](https://nimbbl.biz/docs/api-reference/resend-an-otp-v-3/)
 * - [Capture a Payment v3](https://nimbbl.biz/docs/api-reference/capture-a-payment-v-3/)  (pre-auth)
 * - [Void a Payment v3](https://nimbbl.biz/docs/api-reference/void-a-payment-v-3/)          (pre-auth)
 *
 * Key Details:
 * - Payment flow can be `otp` or `auto_debit`
 * - For `otp` flow, customer needs to enter OTP; for `auto_debit`, payment completes automatically
 * - OTP can be sent encrypted for security
 * - Complete Payment API is used for certain Pay Later providers where native OTP experience is required
 * - Capture / Void act on a pre-authorized (capture_mode=manual) payment; server-to-server, full amount
 */
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

    /**
     * Capture a pre-authorized payment (pre-auth). Full amount only; capture_mode=manual sub-merchant.
     * API: https://nimbbl.biz/docs/api-reference/capture-a-payment-v-3/
     * SDK automatically generates and uses the merchant token for authentication.
     * @param array $attributes Expects ['transaction_id' => <authorized txn>], optional ['comment' => ...]
     * @param string|null $token
     * @return array
     */
    public function capture($attributes = array(), $token = null)
    {
        Logger::getInstance()->debug("Capture - Encryption enabled: " . (NimbblClient::isEncryptPayloadEnabled() ? "True" : "False"));
        $attributes = EncryptedPayloadHelper::preparePayload($attributes, 'capture');
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::CAPTURE, $attributes, $token, SdkConstants::COMPONENT_CAPTURE);
    }

    /**
     * Void (cancel) a pre-authorized payment (pre-auth), releasing the held funds.
     * API: https://nimbbl.biz/docs/api-reference/void-a-payment-v-3/
     * SDK automatically generates and uses the merchant token for authentication.
     * @param array $attributes Expects ['transaction_id' => <authorized txn>], optional ['comment' => ...]
     * @param string|null $token
     * @return array
     */
    public function void($attributes = array(), $token = null)
    {
        Logger::getInstance()->debug("Void - Encryption enabled: " . (NimbblClient::isEncryptPayloadEnabled() ? "True" : "False"));
        $attributes = EncryptedPayloadHelper::preparePayload($attributes, 'void');
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::VOID, $attributes, $token, SdkConstants::COMPONENT_VOID);
    }
}

