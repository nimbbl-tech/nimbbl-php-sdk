<?php

namespace Nimbbl\Api;

class NimbblUtil
{
    const SHA256 = 'sha256';

    public function verifyPaymentSignature($attributes, $orderAmount)
    {
        $actualSignature = $attributes['transaction']['signature'];
        $nimbbl_transaction_id = $attributes['nimbbl_transaction_id'];
        $orderId = $attributes['order']['invoice_id'];
        $signature_string = "";

        if ($attributes['transaction']['signature_version'] != null && $attributes['transaction']['signature_version'] === 'v3') {
            $amount = $this->formatAmount($attributes['transaction']['transaction_amount']);
            $signature_string = $orderId . '|' . $nimbbl_transaction_id . '|' . $amount . '|' . $attributes['transaction']['transaction_currency'] . '|' . $attributes['transaction']['status'] . '|' . $attributes['transaction']['transaction_type'];
        } else {
            $amount = sprintf("%.2f", $orderAmount);
            $signature_string = $orderId . '|' . $nimbbl_transaction_id . '|' . $amount . '|' . $attributes['transaction']['transaction_currency'];
        }
        $secret = NimbblApi::getSecret();

        return $this->verifySignature($signature_string, $actualSignature, $secret, $attributes);
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
        return $verified;
    }

    public function formatAmount($amount){
        $totalAmount = "";
        $inp = (string)$amount;
        $inp = str_replace(',','', $inp);
        $array = explode('.', $inp);
        $totalAmount = $totalAmount.$array[0];
        if(sizeof($array) == 1){
            $totalAmount = $totalAmount.".00";
        }
        else{
            $secondHalf = $array[1];
            $counter = 0;
            $totalAmount .=".";
            foreach(str_split($secondHalf) as $char){
                $counter++;
                $totalAmount .= $char;
                if($counter == 2){
                    break;
                }
            }
            if(strlen($secondHalf) == 1){
                $totalAmount .="0";
            }
        }
        return $totalAmount;
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
