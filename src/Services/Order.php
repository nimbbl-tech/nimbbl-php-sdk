<?php

namespace Nimbbl\Api\Services;

use Exception;
use Nimbbl\Api\RestClient\Request;
use Nimbbl\Api\RestClient\NimbblClient;

use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\ErrorMessages;
use Nimbbl\Api\Common\HttpStatusCodes;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\ErrorCodes;
use Nimbbl\Api\Log\Logger;
use Nimbbl\Api\Encryption;
use Nimbbl\Api\Exception\NimbblException;

/**
 * Nimbbl Orders API Client
 * 
 * Implements OrderInterface for order-related operations
 * 
 * API Documentation: https://nimbbl.biz/docs/category/api-reference/orders/
 */
#[\AllowDynamicProperties]
class Order
{
    /**
     * Create order
     * API: https://nimbbl.biz/docs/api-reference/create-an-order-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param array $attributes
     * @return array
     */
    public function createOrder($attributes, $token = null)
    {
        $logger = Logger::getInstance();
        $isEncryptEnabled = NimbblClient::isEncryptPayloadEnabled();
        $logger->debug("CreateOrder - Encryption enabled: " . ($isEncryptEnabled ? "True" : "False"));

        // Encrypt payload if encryption is enabled
        if ($isEncryptEnabled) {
            try {
                $logger->debug("CreateOrder - Starting payload encryption");
                $encryption = new Encryption(NimbblClient::getSecret());
                $encryptedPayload = $encryption->encrypt($attributes);

                // Wrap encrypted payload in the format expected by API
                // According to API docs: https://nimbbl.biz/docs/api-reference/create-an-order-v-3/
                // The API accepts either a regular request or an encrypted payload
                $attributes = [
                    JsonKeys::ENCRYPTED_PAYLOAD => $encryptedPayload
                ];

                $logger->info("Order request payload encrypted successfully");
            } catch (\Exception $ex) {
                $logger->exception(sprintf(ErrorMessages::ENCRYPTION_ERROR_FORMAT, "order", $ex->getMessage()), $ex);
                throw new NimbblException(sprintf(ErrorMessages::ENCRYPTION_ERROR_FORMAT, "order", $ex->getMessage()), HttpStatusCodes::INTERNAL_SERVER_ERROR, ErrorCodes::ENCRYPTION_ERROR);
            }
        } else {
            $logger->debug("CreateOrder - Encryption disabled, sending plain payload");
        }

        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::ORDER_CREATE, $attributes, $token, SdkConstants::COMPONENT_ORDER);
    }

    /**
     * Get order by order ID
     * API: https://nimbbl.biz/docs/api-reference/get-order-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param string $orderId
     * @return array
     */
    public function getOrderById($orderId, $token = null)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_GET, ApiConstants::ORDER_GET, [JsonKeys::ORDER_ID => $orderId], $token, SdkConstants::COMPONENT_ORDER);
    }

    /**
     * Get order by invoice ID
     * API: https://nimbbl.biz/docs/api-reference/get-order-v-3/
     * SDK automatically generates and uses merchant token for authentication
     * @param string $invoiceId
     * @return array
     */
    public function getOrderByInvoiceId($invoiceId, $token = null)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_GET, ApiConstants::ORDER_GET, [JsonKeys::INVOICE_ID => $invoiceId], $token, SdkConstants::COMPONENT_ORDER);
    }

    /**
     * Update order (not supported by Nimbbl API)
     * Orders cannot be modified after creation
     * 
     * @param array|null $attributes
     * @return array Structured error response indicating unsupported operation
     */
    public function updateOrder($attributes = null)
    {
        return [
            JsonKeys::ERROR => [
                JsonKeys::ERROR_CODE => ErrorMessages::ERROR_CODE_UNSUPPORTED_OPERATION,
                JsonKeys::ERROR_MERCHANT_MESSAGE => ErrorMessages::UNSUPPORTED_OPERATION_ORDER_MODIFY
            ]
        ];
    }

}
