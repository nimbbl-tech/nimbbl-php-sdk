<?php

namespace Nimbbl\Api\Services;

use Nimbbl\Api\RestClient\Request;
use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\ErrorMessages;
use Nimbbl\Api\Common\HttpStatusCodes;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\ErrorCodes;
use Nimbbl\Api\Log\Logger;
use Nimbbl\Api\Common\Encryption;
use Nimbbl\Api\Exception\NimbblException;

#[\AllowDynamicProperties]
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
        $isEncryptEnabled = NimbblClient::isEncryptPayloadEnabled();
        $logger->debug("InitiateRefund - Encryption enabled: " . ($isEncryptEnabled ? "True" : "False"));

        // Encrypt payload if encryption is enabled
        if ($isEncryptEnabled) {
            try {
                $logger->debug("InitiateRefund - Starting payload encryption");
                $encryption = new Encryption(NimbblClient::getSecret());
                $encryptedPayload = $encryption->encrypt($attributes);

                // Wrap encrypted payload in the format expected by API
                // The API accepts either a regular request or an encrypted payload
                $attributes = [
                    JsonKeys::ENCRYPTED_PAYLOAD => $encryptedPayload
                ];

                $logger->info("Refund request payload encrypted successfully");
            } catch (\Exception $ex) {
                $logger->exception(sprintf(ErrorMessages::ENCRYPTION_ERROR_FORMAT, "refund", $ex->getMessage()), $ex);
                throw new NimbblException(sprintf(ErrorMessages::ENCRYPTION_ERROR_FORMAT, "refund", $ex->getMessage()), HttpStatusCodes::INTERNAL_SERVER_ERROR, ErrorCodes::ENCRYPTION_ERROR);
            }
        } else {
            $logger->debug("InitiateRefund - Encryption disabled, sending plain payload");
        }

        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::REFUND_INITIATE, $attributes, $token, SdkConstants::COMPONENT_REFUND);
    }

}
