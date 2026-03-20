<?php

namespace Nimbbl\Api\Services;

use Nimbbl\Api\RestClient\Request;
use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\EncryptedPayloadHelper;
use Nimbbl\Api\Log\Logger;

class Refund
{

    /**
     * Initiate refund
     * API: https://nimbbl.biz/docs/api-reference/refund-a-payment-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $attributes
     * @return array
     */
    public function initiateRefund($attributes = array(), $token = null)
    {
        $logger = Logger::getInstance();
        $logger->debug("InitiateRefund - Encryption enabled: " . (NimbblClient::isEncryptPayloadEnabled() ? "True" : "False"));
        $attributes = EncryptedPayloadHelper::preparePayload($attributes, 'refund');
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::REFUND_INITIATE, $attributes, $token, SdkConstants::COMPONENT_REFUND);
    }

}
