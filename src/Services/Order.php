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

}
