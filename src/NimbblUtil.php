<?php

namespace Nimbbl\Api;

class NimbblUtil
{
    const SHA256 = 'sha256';

    public function verifyPaymentSignature($attributes)
    {
        $actualSignature = $attributes['nimbbl_signature'];
        $transactionId = $attributes['nimbbl_transaction_id'];
        $order_amount = $attributes['order_amount'];
        $order_currency = $attributes['order_currency'];

        if (isset($attributes['merchant_order_id']) === true) {
            $orderId = $attributes['merchant_order_id'];
            $payload = $orderId . '|' . $transactionId . '|' . number_format($order_amount, 2) . '|' . $order_currency;
        } else {
            throw new NimbblError('merchant_order_id must be present.', NimbblErrorCode::SERVER_ERROR, 500);
        }
        $secret = NimbblApi::getSecret();

        return $this->verifySignature($payload, $actualSignature, $secret, $attributes);
    }

    public function verifySignature($payload, $actualSignature, $secret, $attributes)
    {
        $nimbblSegment = new NimbblSegment();
        $expectedSignature = hash_hmac(self::SHA256, $payload, $secret);
        if (function_exists('hash_equals')) {
            $verified = hash_equals($expectedSignature, $actualSignature);
        } else {
            $verified = $this->hashEquals($expectedSignature, $actualSignature);
        }

        if($verified){
            $nimbblSegment->track(array(
                "userId" => NimbblApi::getKey(),
                "event" => "Response Validated",
                "properties" => [
                  "order_id" => $attributes['merchant_order_id'],
                  "transaction_id" => $attributes['nimbbl_transaction_id'],
                  "status" => "match",
                  "merchant_id" => NimbblApi::getMerchantId(),
                  "signature_status" => "match",
                  "kit_name" => 'psp-sdk',
                  'kit_version' => 1
                ],
            ));
        }else{
            $nimbblSegment->track(array(
                "userId" => NimbblApi::getKey(),
                "event" => "Response Validated",
                "properties" => [
                    "order_id" => $attributes['merchant_order_id'],
                    "transaction_id" => $attributes['nimbbl_transaction_id'],
                    "status" => "mismatch",
                    "merchant_id" => NimbblApi::getMerchantId(),
                    "signature_status" => "mismatch",
                    "kit_name" => 'psp-sdk',
                    'kit_version' => 1
                ],
            ));
        }
        
        return $verified;
    }

    private function hashEquals($expectedSignature, $actualSignature)
    {
        if (strlen($expectedSignature) === strlen($actualSignature)) {
            $res = $expectedSignature ^ $actualSignature;
            $return = 0;
            for ($i = strlen($res) - 1; $i >= 0; $i--) {
                $return |= ord($res[$i]);
            }
            return ($return === 0);
        }
        return false;
    }
}


// $nimbbl_signature = $webhook_data['nimbbl_signature'];
// $generated_signature = hash_hmac('sha256', $webhook_data['order']['invoice_id'] . '|' . $webhook_data['nimbbl_transaction_id'], $this->private_key);
// if ($generated_signature == $nimbbl_signature) {
//     $order->add_order_note('Nimbbl webhook signature verified.');
// } else {
//     $order->add_order_note('Nimbbl webhook signature verification failed.');
// }
