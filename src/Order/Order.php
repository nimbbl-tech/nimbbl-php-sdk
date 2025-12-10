<?php

namespace Nimbbl\Api;

use Exception;

/**
 * Nimbbl Orders API Client
 * 
 * Implements OrderInterface for order-related operations
 * 
 * API Documentation: https://nimbbl.biz/docs/category/api-reference/orders/
 */
#[AllowDynamicProperties]
class Order
{
    /**
     * Create order
     * API: https://nimbbl.biz/docs/api-reference/create-an-order-v-3/
     * @param array $attributes
     * @param string $token
     * @return array
     */
    public function createOrder($attributes, $token)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::ORDER_CREATE, $attributes, $token, SdkConstants::COMPONENT_ORDER);
    }

    /**
     * Get order by order ID
     * API: https://nimbbl.biz/docs/api-reference/get-order-v-3/
     * @param string $orderId
     * @param string $token
     * @return array
     */
    public function getOrderById($orderId, $token)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_GET, ApiConstants::ORDER_GET, ['order_id' => $orderId], $token, SdkConstants::COMPONENT_ORDER);
    }

    /**
     * Get order by invoice ID
     * API: https://nimbbl.biz/docs/api-reference/get-order-v-3/
     * @param string $invoiceId
     * @param string $token
     * @return array
     */
    public function getOrderByInvoiceId($invoiceId, $token)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_GET, ApiConstants::ORDER_GET, ['invoice_id' => $invoiceId], $token, SdkConstants::COMPONENT_ORDER);
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
            'error' => [
                'nimbbl_error_code' => 'UNSUPPORTED_OPERATION',
                'nimbbl_merchant_message' => ErrorMessages::UNSUPPORTED_OPERATION_ORDER_MODIFY
            ]
        ];
    }

}
