<?php
/**
 * Live payment-mode coverage — discovers every payment_mode_code available for the configured
 * sub-merchant (via the payment-modes API) and exercises each one against:
 *   - offers            (POST /v3/offers)
 *   - initiate-payment  (POST /v3/initiate-payment)
 *
 * Bank/wallet sub-codes are fetched live (list-of-banks / list-of-wallets). Card and EMI modes
 * need RSA-encrypted card data / an EMI plan, so their initiate step is marked SKIP.
 *
 * Requires an s2s_only sub-merchant (orders auto user-verified). Usage:
 *   php tests/live-payment-modes.php
 */

error_reporting(E_ALL & ~E_DEPRECATED);
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../example/utils/helpers.php';

use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\RestClient\Request;

$config = loadConfig();
$api = new NimbblClient($config['access_key'], $config['access_secret'], $config['api_endpoint']);
\Nimbbl\Api\Log\Logger::disableDebugLogging();

$CALLBACK = 'https://example.com/cb';

function short(\Throwable $e): string
{
    $m = $e->getMessage();
    return substr(preg_replace('/\s+/', ' ', $m), 0, 70);
}
function call(callable $fn): array
{
    try {
        $r = $fn();
        if (is_array($r) && isset($r['error'])) {
            return ['FAIL', $r['error']['nimbbl_error_code'] ?? json_encode($r['error'])];
        }
        return ['PASS', 'ok'];
    } catch (\Throwable $e) {
        return ['FAIL', short($e)];
    }
}

// ---- Bootstrap: token + order + sub-codes -------------------------------
$mtok = (new Request())->generateToken()['token'];
$order = $api->orders()->createOrder([
    'invoice_id' => 'PMALL_' . time(), 'amount_before_tax' => 1000, 'tax' => 0, 'total_amount' => 1000, 'currency' => 'INR',
    'user' => ['email' => 'live@example.com', 'first_name' => 'Live', 'last_name' => 'Test', 'mobile_number' => '9876543210', 'country_code' => '+91'],
], $mtok);
$orderId = $order['order_id'];
$orderToken = $order['token'];

// available payment modes for THIS sub-merchant
$pm = $api->checkoutUtilities()->listPaymentModes(['order_id' => $orderId], $orderToken);
$codes = [];
foreach ($pm as $tray) {
    if (is_array($tray) && !empty($tray['items'])) {
        foreach ($tray['items'] as $it) {
            $code = $it['payment_mode_code'] ?? null;
            if ($code && !in_array($code, $codes, true)) $codes[] = $code;
        }
    }
}

// first available bank_code / wallet_code (for netbanking / wallet initiate)
$bankCode = null; $walletCode = null;
try { $b = $api->checkoutUtilities()->listBanks(['order_id' => $orderId], $orderToken); $bankCode = $b['bank_list'][0]['code'] ?? null; } catch (\Throwable $e) {}
try { $w = $api->checkoutUtilities()->listWallets(['order_id' => $orderId], $orderToken); $walletCode = $w['wallet_list'][0]['code'] ?? null; } catch (\Throwable $e) {}

echo "Sub-merchant available payment_mode_codes: " . implode(', ', $codes) . "\n";
echo "Using bank_code=" . ($bankCode ?? 'n/a') . ", wallet_code=" . ($walletCode ?? 'n/a') . "\n\n";

// offers uses tray-level codes; map granular -> offers code
$offersCodeFor = function (string $code): string {
    if (str_contains($code, 'card') && !str_contains($code, 'cardless')) return 'card';
    if ($code === 'cardless_emi') return 'card';
    return $code; // net_banking, upi, wallet
};

// initiate-payment params per mode (fresh order each time so state is clean)
$initiateParamsFor = function (string $code, string $oid) use ($CALLBACK, $bankCode, $walletCode): ?array {
    $base = ['order_id' => $oid, 'payment_mode_code' => $code, 'callback_url' => $CALLBACK];
    switch ($code) {
        case 'wallet':      return $walletCode ? $base + ['wallet_code' => $walletCode] : null;
        case 'net_banking': return $bankCode ? $base + ['bank_code' => $bankCode] : null;
        case 'upi':         return $base + ['payment_flow' => 'intent', 'upi_app_code' => 'gpay'];
        // Card & EMI modes need RSA-encrypted card details / EMI plan -> not exercised server-side
        default:            return null;
    }
};

$rows = [];
foreach ($codes as $code) {
    // offers — UPI offers also need a payment_flow to avoid a generic backend error
    $oc = $offersCodeFor($code);
    $offersParams = ['order_id' => $orderId, 'payment_mode_code' => $oc];
    if ($oc === 'upi') $offersParams['payment_flow'] = 'intent';
    [$oS, $oN] = call(fn() => $api->checkoutUtilities()->getOffers($offersParams, $orderToken));

    // initiate-payment on a FRESH order (initiate mutates order state)
    $params = $initiateParamsFor($code, '');
    if ($params === null) {
        $iS = 'SKIP'; $iN = 'needs RSA card / EMI plan / missing sub-code';
    } else {
        $o2 = $api->orders()->createOrder([
            'invoice_id' => 'PMINIT_' . $code . '_' . time() . rand(10, 99), 'amount_before_tax' => 1000, 'tax' => 0, 'total_amount' => 1000, 'currency' => 'INR',
            'user' => ['email' => 'live@example.com', 'first_name' => 'Live', 'last_name' => 'Test', 'mobile_number' => '9876543210', 'country_code' => '+91'],
        ], $mtok);
        $params = $initiateParamsFor($code, $o2['order_id']);
        [$iS, $iN] = call(fn() => $api->payments()->initiatePayment($params, $o2['token']));
    }

    $rows[] = [$code, $offersCodeFor($code), $oS, $oN, $iS, $iN];
}

// ---- Report -------------------------------------------------------------
printf("%-16s %-12s %-6s %-16s %-6s %s\n", 'payment_mode', 'offers_code', 'OFF', 'off_note', 'INIT', 'init_note');
echo str_repeat('-', 110) . "\n";
foreach ($rows as [$code, $oc, $oS, $oN, $iS, $iN]) {
    printf("%-16s %-12s %-6s %-16s %-6s %s\n", $code, $oc, $oS, $oN, $iS, $iN);
}
