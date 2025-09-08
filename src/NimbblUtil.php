<?php

declare(strict_types=1);

namespace Nimbbl\Api;

class NimbblUtil
{
    const SHA256 = 'sha256';

    public function verifyPaymentSignature(array $attributes, float $orderAmount): bool
    {
        NimbblLogger::getInstance()->log("verifyPaymentSignature START - orderAmount: {$orderAmount}", 'INFO', 'NimbblUtil');

        try {
            $actualSignature = $attributes['transaction']['signature'] ?? null;
            $nimbbl_transaction_id = $attributes['nimbbl_transaction_id'] ?? '';
            $invoice_id = $attributes['order']['invoice_id'] ?? '';

            NimbblLogger::getInstance()->log(
                "Input validation - actualSignature: " . ($actualSignature ? 'present' : 'missing') .
                ", nimbbl_transaction_id: {$nimbbl_transaction_id}, invoice_id: {$invoice_id}",
                'DEBUG',
                'NimbblUtil'
            );

            if (!$actualSignature) {
                NimbblLogger::getInstance()->log("ERROR: actualSignature is missing or empty", 'ERROR', 'NimbblUtil');
                return false;
            }

            $signature_string = '';

            if (!empty($attributes['transaction']['signature_version']) &&
                $attributes['transaction']['signature_version'] === 'v3') {
                $raw_transaction_amount = $attributes['transaction']['transaction_amount'] ?? null;
                $amount = $this->formatAmount($raw_transaction_amount);
                NimbblLogger::getInstance()->log('Formatted amount: ' . $amount . ' type: ' . gettype($amount), 'DEBUG', 'NimbblUtil');
                $invoice_id_str = (string) $invoice_id;
                $nimbbl_transaction_id_str = (string) $nimbbl_transaction_id;
                $amount_str = (string) $amount;
                $currency_str = (string) ($attributes['transaction']['transaction_currency'] ?? '');
                $status_str = (string) ($attributes['transaction']['status'] ?? '');
                $type_str = (string) ($attributes['transaction']['transaction_type'] ?? '');
                $signature_string = $invoice_id_str . '|' . $nimbbl_transaction_id_str . '|' . $amount_str . '|' . $currency_str . '|' . $status_str . '|' . $type_str;
                NimbblLogger::getInstance()->log('signature_string v3 generated: ' . $signature_string, 'DEBUG', 'NimbblUtil');
            } else {
                $raw_transaction_amount = $orderAmount;
                $amount = sprintf('%.2f', $raw_transaction_amount);
                NimbblLogger::getInstance()->log('Formatted amount: ' . $amount . ' type: ' . gettype($amount), 'DEBUG', 'NimbblUtil');
                $invoice_id_str = (string) $invoice_id;
                $nimbbl_transaction_id_str = (string) $nimbbl_transaction_id;
                $amount_str = (string) $amount;
                $currency_str = (string) ($attributes['transaction']['transaction_currency'] ?? '');
                $signature_string = $invoice_id_str . '|' . $nimbbl_transaction_id_str . '|' . $amount_str . '|' . $currency_str;
                NimbblLogger::getInstance()->log('signature_string v2 generated: ' . $signature_string, 'DEBUG', 'NimbblUtil');
            }

            NimbblLogger::getInstance()->log("Generated signature_string: {$signature_string}", 'DEBUG', 'NimbblUtil');

            $secret = NimbblApi::getSecret();
            if (!$secret) {
                NimbblLogger::getInstance()->log("ERROR: secret is empty or null", 'ERROR', 'NimbblUtil');
                return false;
            }

            NimbblLogger::getInstance()->log("Calling verifySignature()", 'DEBUG', 'NimbblUtil');
            $result = $this->verifySignature($signature_string, $actualSignature, $secret, $attributes);

            NimbblLogger::getInstance()->log("verifyPaymentSignature END - Result: " . ($result ? 'SUCCESS' : 'FAILED'), 'INFO', 'NimbblUtil');
            return $result;
        } catch (\Exception $e) {
            NimbblLogger::getInstance()->log("verifyPaymentSignature ERROR: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 'ERROR', 'NimbblUtil');
            throw $e;
        }
    }

    public function verifySignature(string $payload, string $actualSignature, string $secret, array $attributes): bool
    {
        NimbblLogger::getInstance()->log("verifySignature START", 'DEBUG', 'NimbblUtil');

        $expectedSignature = hash_hmac(self::SHA256, $payload, $secret);

        $verified = function_exists('hash_equals')
            ? hash_equals($expectedSignature, $actualSignature)
            : $this->hashEquals($expectedSignature, $actualSignature);

        NimbblLogger::getInstance()->log(
            "verifySignature DEBUG: " .
            "payload=[" . $payload . "] | " .
            "attributes=" . str_replace(["\n", "\r"], '', print_r($attributes, true)),
            'DEBUG',
            'NimbblUtil'
        );

        NimbblLogger::getInstance()->log("verifySignature END - Result: " . ($verified ? 'SUCCESS' : 'FAILED'), 'DEBUG', 'NimbblUtil');
        return $verified;
    }

    public function formatAmount($amount): string
    {
        NimbblLogger::getInstance()->log(__METHOD__ . ' START - Raw value: ' . var_export($amount, true) . ' (type: ' . gettype($amount) . ')', 'DEBUG', 'NimbblUtil');
        // Remove commas and cast to float, then format to four decimals
        $numeric = (float) str_replace(',', '', (string) $amount);
        $totalAmount = number_format($numeric, 2, '.', '');
        NimbblLogger::getInstance()->log(__METHOD__ . ' END - Result: ' . $totalAmount, 'DEBUG', 'NimbblUtil');
        return $totalAmount;
    }

    private function hashEquals(string $expectedSignature, string $actualSignature): bool
    {
        NimbblLogger::getInstance()->log("hashEquals START", 'DEBUG', 'NimbblUtil');

        if (strlen($expectedSignature) !== strlen($actualSignature)) {
            NimbblLogger::getInstance()->log("hashEquals FAILED - Length mismatch: expected=" . strlen($expectedSignature) . ", actual=" . strlen($actualSignature), 'DEBUG', 'NimbblUtil');
            return false;
        }

        $res = $expectedSignature ^ $actualSignature;
        $result = 0;
        for ($i = strlen($res) - 1; $i >= 0; $i--) {
            $result |= ord($res[$i]);
        }

        NimbblLogger::getInstance()->log("hashEquals END - Result: " . ($result === 0 ? 'SUCCESS' : 'FAILED'), 'DEBUG', 'NimbblUtil');
        return $result === 0;
    }
}
