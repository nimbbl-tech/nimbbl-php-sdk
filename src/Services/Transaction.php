<?php

namespace Nimbbl\Api\Services;

use Nimbbl\Api\RestClient\Request;
use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\EncryptedPayloadHelper;
use Nimbbl\Api\Log\Logger;

class Transaction
{

    /**
     * Transaction enquiry
     * API: https://nimbbl.biz/docs/api-reference/transaction-enquiry-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $attributes
     * @return array
     */
    public function transactionEnquiry($attributes = array(), $token = null)
    {
        $logger = Logger::getInstance();
        $logger->debug("TransactionEnquiry - Encryption enabled: " . (NimbblClient::isEncryptPayloadEnabled() ? "True" : "False"));
        $attributes = EncryptedPayloadHelper::preparePayload($attributes, 'transaction enquiry');
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::TRANSACTION_ENQUIRY, $attributes, $token, SdkConstants::COMPONENT_TRANSACTION);
    }

}
