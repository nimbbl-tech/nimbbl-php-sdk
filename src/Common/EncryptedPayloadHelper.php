<?php

namespace Nimbbl\Api\Common;

use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\Log\Logger;
use Nimbbl\Api\Exception\NimbblException;

/**
 * Shared helper for preparing request payloads with optional encryption.
 * Used by Order, Refund, Transaction, and CheckoutUtilities to avoid duplicated logic.
 */
class EncryptedPayloadHelper
{
    /**
     * Prepare payload for API request: return attributes as-is or encrypted when enabled.
     *
     * @param array $attributes Request attributes to send
     * @param string $contextName Context name for log/error messages (e.g. "order", "refund", "list banks")
     * @return array Payload to send (either $attributes or [ 'encrypted_payload' => ... ])
     * @throws NimbblException When encryption is enabled but encryption fails
     */
    public static function preparePayload(array $attributes, string $contextName): array
    {
        if (!NimbblClient::isEncryptPayloadEnabled()) {
            return $attributes;
        }

        $logger = Logger::getInstance();
        $logger->debug("{$contextName} - Starting payload encryption");

        try {
            $encryption = new Encryption(NimbblClient::getSecret());
            $encryptedPayload = $encryption->encrypt($attributes);
            $logger->info("{$contextName} - Payload encrypted successfully");
            return [
                JsonKeys::ENCRYPTED_PAYLOAD => $encryptedPayload,
            ];
        } catch (\Exception $ex) {
            $message = sprintf(ErrorMessages::ENCRYPTION_ERROR_FORMAT, $contextName, $ex->getMessage());
            $logger->exception($message, $ex);
            throw new NimbblException(
                $message,
                ErrorCodes::ENCRYPTION_ERROR,
                null,
                HttpStatusCodes::INTERNAL_SERVER_ERROR,
                null,
                $ex
            );
        }
    }
}
