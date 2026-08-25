<?php

namespace Nimbbl\Api\Services;

use Nimbbl\Api\RestClient\Request;
use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Common\EncryptedPayloadHelper;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Log\Logger;

/**
 * Nimbbl Orders API Client
 * 
 * Implements OrderInterface for order-related operations
 * 
 * API Documentation: https://nimbbl.biz/docs/category/api-reference/orders/
 */
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
        $logger->debug("CreateOrder - Encryption enabled: " . (NimbblClient::isEncryptPayloadEnabled() ? "True" : "False"));

        // Stamped BEFORE preparePayload so both fields land inside the ciphertext when
        // payload encryption is enabled.
        $attributes = $this->applyOrderSource($attributes);
        $attributes = EncryptedPayloadHelper::preparePayload($attributes, 'order');
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
     * Stamp the order_source pair on a create-order payload.
     *
     * order_source identifies the INTEGRATION that created the order. The Magento,
     * WooCommerce and OpenCart plugins all bundle this same SDK, so each one sets its own
     * value; the SDK only fills in its own name when the caller has not set a usable one.
     *
     * order_source_version is always SDK-controlled (anti-spoof): a caller cannot
     * misreport which SDK build made the call.
     *
     * @param array $attributes
     * @return array
     */
    private function applyOrderSource($attributes)
    {
        if (!isset($attributes[JsonKeys::ORDER_SOURCE])
            || !is_string($attributes[JsonKeys::ORDER_SOURCE])
            || trim($attributes[JsonKeys::ORDER_SOURCE]) === '') {
            $attributes[JsonKeys::ORDER_SOURCE] = SdkConstants::ORDER_SOURCE;
        }

        $attributes[JsonKeys::ORDER_SOURCE_VERSION] = SdkConstants::SDK_VERSION;

        return $attributes;
    }

}
