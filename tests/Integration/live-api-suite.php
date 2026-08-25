<?php
/**
 * Live API suite — exercises every SDK API one-by-one against the configured environment
 * (example/config.php). Prints a per-API PASS / SKIP / FAIL table with reasons.
 *
 * Usage:  php tests/live-api-suite.php
 *
 * Classification:
 *   PASS       - call succeeded (2xx, no error envelope)
 *   REACHABLE  - endpoint responded with a controlled/expected error (e.g. dummy id -> not found),
 *                proving the wiring works; needs real data for a green result
 *   USER-TOKEN - endpoint requires a user-verified Bearer token (resolve-user + OTP), which a pure
 *                server-side call can't produce
 *   SKIP       - needs prerequisite data not available server-side (paid/authorized txn, OTP, card)
 *   FAIL       - unexpected failure
 */

error_reporting(E_ALL & ~E_DEPRECATED);
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../example/utils/helpers.php';

use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\RestClient\Request;

$config = loadConfig();
$api = new NimbblClient($config['access_key'], $config['access_secret'], $config['api_endpoint']);
\Nimbbl\Api\Log\Logger::disableDebugLogging();

$rows = [];
$n = 0;

function classify(\Throwable $e): array
{
    $msg = $e->getMessage();
    if (stripos($msg, 'user token') !== false || stripos($msg, 'LOGIN_SESSION') !== false || stripos($msg, "doesn't authorize") !== false) {
        return ['USER-TOKEN', 'needs user-verified Bearer token (resolve-user + OTP)'];
    }
    if (stripos($msg, 'does not exist') !== false || stripos($msg, 'not found') !== false || stripos($msg, 'INVALID_TRANSACTION') !== false) {
        return ['REACHABLE', 'endpoint OK; dummy id -> not found (needs real data)'];
    }
    return ['FAIL', $msg];
}

function run(string $api_name, ?callable $fn, ?string $forceStatus = null, ?string $forceNote = null): void
{
    global $rows, $n;
    $n++;
    if ($fn === null) {
        $rows[] = [$n, $api_name, $forceStatus ?? 'SKIP', $forceNote ?? ''];
        return;
    }
    try {
        $res = $fn();
        if (is_array($res) && isset($res['error'])) {
            $rows[] = [$n, $api_name, 'FAIL', json_encode($res['error'])];
        } else {
            $rows[] = [$n, $api_name, 'PASS', 'ok'];
        }
    } catch (\Throwable $e) {
        [$status, $note] = classify($e);
        $rows[] = [$n, $api_name, $status, $note];
    }
}

// shared state
$mtok = null; $orderId = null; $invoiceId = 'LIVE_' . time(); $orderToken = null; $refreshToken = null;
$plId = null; $plInvoice = null; $userId = null;

// ---- Auth: generate token ----
run('POST /v3/generate-token', function () use (&$mtok) {
    $r = (new Request())->generateToken();
    $mtok = $r['token'] ?? null;
    if (empty($mtok)) throw new \RuntimeException('no token');
    return $r;
});

// ---- Orders (S2S, merchant token) ----
run('POST /v3/create-order', function () use ($api, &$mtok, &$orderId, &$orderToken, &$refreshToken, &$userId, $invoiceId) {
    $o = $api->orders()->createOrder([
        'invoice_id' => $invoiceId, 'amount_before_tax' => 100, 'tax' => 0, 'total_amount' => 100, 'currency' => 'INR',
        'user' => ['email' => 'live@example.com', 'first_name' => 'Live', 'last_name' => 'Test', 'mobile_number' => '9876543210', 'country_code' => '+91'],
    ], $mtok);
    $orderId = $o['order_id'] ?? null;
    $orderToken = $o['token'] ?? null;
    $refreshToken = $o['refresh_token'] ?? null;
    $userId = $o['user']['user_id'] ?? null;
    return $o;
});
run('GET  /v3/order (by order_id)', function () use ($api, &$mtok, &$orderId) {
    return $api->orders()->getOrderById($orderId, $mtok);
});
run('GET  /v3/order (by invoice_id)', function () use ($api, &$mtok, $invoiceId) {
    return $api->orders()->getOrderByInvoiceId($invoiceId, $mtok);
});

// ---- Auth: refresh token (uses the order's refresh_token) ----
run('POST /v3/refresh-token', function () use ($api, &$mtok, &$refreshToken) {
    if (empty($refreshToken)) throw new \RuntimeException('no refresh_token from create-order');
    return $api->auth()->refreshToken($refreshToken, $mtok);
});

// ---- Transactions ----
run('POST /v3/transaction-enquiry (order_id)', function () use ($api, &$mtok, &$orderId) {
    return $api->transactions()->transactionEnquiry(['order_id' => $orderId], $mtok);
});
run('POST /v3/transaction-enquiry (invoice_id)', function () use ($api, &$mtok, $invoiceId) {
    return $api->transactions()->transactionEnquiry(['invoice_id' => $invoiceId], $mtok);
});

// ---- Payment Links (S2S, merchant token) ----
run('POST /v3/payment-link (create)', function () use ($api, &$mtok, &$plId, &$plInvoice) {
    $plInvoice = 'PL_' . time() . '_' . rand(1000, 9999);
    $pl = $api->paymentLinks()->createPaymentLink([
        'invoice_id' => $plInvoice, 'total_amount' => 100, 'currency' => 'INR',
        'expires_at' => date('Y-m-d H:i:s', time() + 86400),
        'user' => ['email' => 'live@example.com', 'first_name' => 'Live', 'last_name' => 'Test', 'mobile_number' => '9876543210', 'country_code' => '+91'],
    ], $mtok);
    $plId = $pl['payment_link_id'] ?? null;
    return $pl;
});
run('POST /v3/payment-link/enquiry', function () use ($api, &$mtok, &$plInvoice) {
    if (empty($plInvoice)) throw new \RuntimeException('no payment link invoice from create');
    return $api->paymentLinks()->enquiryPaymentLink(['invoice_id' => $plInvoice], $mtok);
});
run('PATCH /v3/payment-link (update)', function () use ($api, &$mtok, &$plId) {
    if (empty($plId)) throw new \RuntimeException('no payment_link_id from create');
    return $api->paymentLinks()->updatePaymentLink(['payment_link_id' => $plId, 'total_amount' => 120], $mtok);
});
run('POST /v3/payment-link/actions (cancel)', function () use ($api, &$mtok, &$plId) {
    if (empty($plId)) throw new \RuntimeException('no payment_link_id from create');
    return $api->paymentLinks()->performPaymentLinkActions(['payment_link_id' => $plId, 'action' => 'cancel'], $mtok);
});

// ---- Checkout Utilities (order-scoped; order token) ----
run('POST /v3/list-of-banks', function () use ($api, &$orderToken, &$orderId) {
    return $api->checkoutUtilities()->listBanks(['order_id' => $orderId], $orderToken);
});
run('POST /v3/list-of-wallets', function () use ($api, &$orderToken, &$orderId) {
    return $api->checkoutUtilities()->listWallets(['order_id' => $orderId], $orderToken);
});
run('POST /v3/get-bin-data', function () use ($api, &$orderToken, &$orderId) {
    return $api->checkoutUtilities()->getCardBinData(['card_bin' => '411111', 'order_id' => $orderId], $orderToken);
});
run('POST /v3/validate-vpa', function () use ($api, &$orderToken) {
    return $api->checkoutUtilities()->validateUpiVpa(['upi_id' => 'test@paytm'], $orderToken);
});
run('POST /v3/cards (get-card-details)', null, 'SKIP', 'needs a real card number (PCI) — not exercised server-side');
run('POST /v3/get-upi-app-details', function () use ($api, &$mtok) {
    // Required param is `platform` (ios/android), not order_id (confirmed via the .NET SDK).
    return $api->checkoutUtilities()->getUpiAppDetails(['platform' => 'android'], $mtok);
});
run('POST /v3/payment-modes', function () use ($api, &$orderToken, &$orderId) {
    return $api->checkoutUtilities()->listPaymentModes(['order_id' => $orderId], $orderToken);
});
run('POST /v3/emis', function () use ($api, &$orderToken, &$orderId) {
    return $api->checkoutUtilities()->listEMIs(['order_id' => $orderId], $orderToken);
});
run('POST /v3/offers', function () use ($api, &$orderToken, &$orderId) {
    return $api->checkoutUtilities()->getOffers(['order_id' => $orderId, 'payment_mode_code' => 'card'], $orderToken);
});

// ---- Payments (consumer flow — need user-verified token / OTP) ----
run('POST /v3/initiate-payment', function () use ($api, &$orderToken, &$orderId) {
    return $api->payments()->initiatePayment(['order_id' => $orderId, 'payment_mode_code' => 'wallet', 'wallet_code' => 'JIO_MONEY', 'callback_url' => 'https://example.com/cb'], $orderToken);
});
run('POST /v3/payment (complete)', null, 'SKIP', 'needs an initiated payment + OTP (consumer flow)');
run('POST /v3/resend-otp', null, 'SKIP', 'needs an active OTP session (consumer flow)');

// ---- Addresses (user-scoped; need user-verified token) ----
run('GET  /v3/addresses (list)', function () use ($api, &$mtok, &$userId) {
    // Merchant-token API (confirmed via .NET SDK); needs a real user_id.
    return $api->addresses()->listAddresses(['user_id' => $userId, 'amount' => 100, 'currency' => 'INR'], $mtok);
});
run('POST /v3/addresses (create/update/delete/import/eligibility/link)', null, 'SKIP', 'merchant-token APIs; need address data / address_id to exercise (mutating)');

// ---- Refunds / Pre-auth (need real transactions) ----
run('POST /v3/refund', function () use ($api, &$mtok) {
    return $api->refunds()->initiateRefund(['transaction_id' => 'o_dummy-000000000000', 'comment' => 'probe'], $mtok);
});
run('POST /v3/capture', function () use ($api, &$mtok) {
    return $api->payments()->capture(['transaction_id' => 'o_dummy-000000000000', 'comment' => 'probe'], $mtok);
});
run('POST /v3/void', function () use ($api, &$mtok) {
    return $api->payments()->void(['transaction_id' => 'o_dummy-000000000000', 'comment' => 'probe'], $mtok);
});

// ---- Report ----
$counts = ['PASS' => 0, 'REACHABLE' => 0, 'USER-TOKEN' => 0, 'SKIP' => 0, 'FAIL' => 0];
echo "\n";
printf("%-3s %-56s %-10s %s\n", '#', 'API', 'RESULT', 'NOTE');
echo str_repeat('-', 120) . "\n";
foreach ($rows as [$i, $name, $status, $note]) {
    $counts[$status] = ($counts[$status] ?? 0) + 1;
    printf("%-3d %-56s %-10s %s\n", $i, $name, $status, $note);
}
echo str_repeat('-', 120) . "\n";
echo "Summary: ";
foreach ($counts as $k => $v) echo "$k=$v  ";
echo "\n";
