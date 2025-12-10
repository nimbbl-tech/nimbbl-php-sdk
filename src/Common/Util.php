<?php

namespace Nimbbl\Api;

/**
 * Utility helpers (payment signature verification)
 */
class Util
{
    /**
     * Verify payment signature for Standard Checkout response.
     *
     * Expects attributes to contain transaction/order data and a signature field.
     *
     * @param array      $attributes   Response attributes (transaction/order data)
     * @param float|null $amount       Amount to validate (order total)
     * @param string|null $secret      Optional secret; defaults to Api::getSecret()
     * @return bool
     */
    public function verifyPaymentSignature($attributes, $amount = null, $secret = null)
    {
        $secret = $secret ?? Api::getSecret();
        if (empty($secret) || !is_string($secret)) {
            return false;
        }

        // Extract values
        $txn = $attributes['transaction'] ?? [];
        $order = $attributes['order'] ?? [];

        $transactionId = $attributes['nimbbl_transaction_id']
            ?? ($txn['transaction_id'] ?? null);

        $signature = $attributes['nimbbl_signature']
            ?? ($txn['nimbbl_signature'] ?? ($txn['signature'] ?? ($order['nimbbl_signature'] ?? null)));

        $orderAmount = $amount ?? ($order['total_amount'] ?? $order['amount_before_tax'] ?? $order['amount'] ?? null);

        if (empty($transactionId) || empty($signature) || $orderAmount === null) {
            return false;
        }

        // Normalise amount to 2 decimals as string
        $amountStr = number_format((float)$orderAmount, 2, '.', '');

        // Compute expected signature: transaction_id|amount
        $payload = $transactionId . '|' . $amountStr;
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
