<?php

namespace Nimbbl\Api\Common;

use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\Log\Logger;
use Nimbbl\Api\Exception\NimbblException;

/**
 * Shared helper for preparing request payloads with optional encryption.
 *
 * Used by the services whose backend endpoints accept an encrypted payload:
 * Order (create), Refund (initiate), Transaction (enquiry), CheckoutUtilities
 * (list-banks / list-wallets), and Payment (capture / void).
 *
 * NOTE: encryption is intentionally applied ONLY on those endpoints. Other endpoints
 * (e.g. get-card-details, validate-vpa, payment-link, initiate/complete-payment,
 * addresses) do not route through this helper because the backend does not accept an
 * `encrypted_payload` for them — sending one would be rejected. Add a service to the
 * list above only once its backend endpoint supports encrypted payloads.
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
