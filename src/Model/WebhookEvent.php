<?php

namespace Nimbbl\Api\Model;

/**
 * Webhook Event Model
 * 
 * Represents a parsed webhook event from Nimbbl
 */
class WebhookEvent
{
    public $event;
    public $payload;
    public $timestamp;
    public $order_id;
    public $transaction_id;
    public $refund_id;
    public $user_id;
    public $data;

    /**
     * Constructor
     * 
     * @param array $data Webhook event data
     */
    public function __construct($data)
    {
        $this->event = $data['event'] ?? null;
        $this->payload = $data['payload'] ?? $data;
        $this->data = $data['data'] ?? $data['payload'] ?? [];
        $this->timestamp = $data['timestamp'] ?? $data['data']['timestamp'] ?? null;
        
        // Extract IDs from various possible locations
        $this->order_id = $data['order_id'] 
            ?? $data['data']['order_id'] 
            ?? $data['payload']['order']['order_id'] 
            ?? $data['payload']['order_id'] 
            ?? null;
            
        $this->transaction_id = $data['transaction_id'] 
            ?? $data['data']['transaction_id'] 
            ?? $data['payload']['transaction']['transaction_id'] 
            ?? $data['payload']['transaction_id'] 
            ?? null;
            
        $this->refund_id = $data['refund_id'] 
            ?? $data['data']['refund_id'] 
            ?? $data['payload']['refund']['refund_id'] 
            ?? $data['payload']['refund_id'] 
            ?? null;
            
        $this->user_id = $data['user_id'] 
            ?? $data['data']['user_id'] 
            ?? $data['payload']['user']['user_id'] 
            ?? $data['payload']['user_id'] 
            ?? null;
    }

    /**
     * Check if event is payment captured
     * 
     * @return bool
     */
    public function isPaymentCaptured()
    {
        return $this->event === 'payment.captured' || $this->event === 'payment.success';
    }

    /**
     * Check if event is payment failed
     * 
     * @return bool
     */
    public function isPaymentFailed()
    {
        return $this->event === 'payment.failed';
    }

    /**
     * Check if event is refund initiated
     * 
     * @return bool
     */
    public function isRefundInitiated()
    {
        return $this->event === 'refund.initiated' || $this->event === 'refund.created';
    }

    /**
     * Check if event is refund completed
     * 
     * @return bool
     */
    public function isRefundCompleted()
    {
        return $this->event === 'refund.completed' || $this->event === 'refund.processed';
    }

    /**
     * Check if event is order created
     * 
     * @return bool
     */
    public function isOrderCreated()
    {
        return $this->event === 'order.created';
    }

    /**
     * Check if event is order updated
     * 
     * @return bool
     */
    public function isOrderUpdated()
    {
        return $this->event === 'order.updated';
    }

    /**
     * Get order data from payload
     * 
     * @return array|null
     */
    public function getOrderData()
    {
        return $this->data['order'] 
            ?? $this->payload['order'] 
            ?? $this->data 
            ?? null;
    }

    /**
     * Get transaction data from payload
     * 
     * @return array|null
     */
    public function getTransactionData()
    {
        return $this->data['transaction'] 
            ?? $this->payload['transaction'] 
            ?? null;
    }

    /**
     * Get refund data from payload
     * 
     * @return array|null
     */
    public function getRefundData()
    {
        return $this->data['refund'] 
            ?? $this->payload['refund'] 
            ?? null;
    }

    /**
     * Get user data from payload
     * 
     * @return array|null
     */
    public function getUserData()
    {
        return $this->data['user'] 
            ?? $this->payload['user'] 
            ?? null;
    }
}

