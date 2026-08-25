<?php
/**
 * Fetch captured requests from webhook.site and verify each with the SDK.
 *
 * Flow for a REAL backend webhook:
 *   1. Register your webhook.site URL as the sub-merchant's webhook (or an order's callback_url):
 *        https://webhook.site/<token>
 *   2. Trigger an event (complete a payment / capture / void) so Nimbbl POSTs the signed payload there.
 *   3. Run this script — it pulls the captured request(s) via webhook.site's API and runs the RAW body
 *      through verifyWebhook() (and verifyCallback() as a fallback), proving the real backend signature
 *      verifies with your access_secret.
 *
 * Usage:  php tests/verify-webhooksite.php [token]
 */

error_reporting(E_ALL & ~E_DEPRECATED);
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../example/utils/helpers.php';

use Nimbbl\Api\RestClient\NimbblClient;

$token = $argv[1] ?? '912ea81e-4dcf-44ea-9384-323457662529';
$config = loadConfig();
$secret = $config['access_secret'];
$api = new NimbblClient($config['access_key'], $config['access_secret'], $config['api_endpoint']);
\Nimbbl\Api\Log\Logger::disableDebugLogging();
$verifier = $api->signatureVerifier();

$url = "https://webhook.site/token/{$token}/requests?sorting=newest";
$ctx = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true, 'header' => "Accept: application/json\r\n"]]);
$raw = @file_get_contents($url, false, $ctx);
if ($raw === false) { fwrite(STDERR, "Could not reach webhook.site API\n"); exit(1); }
$data = json_decode($raw, true);
$reqs = $data['data'] ?? [];

echo "Token: {$token}   captured requests: " . ($data['total'] ?? count($reqs)) . "\n";
if (empty($reqs)) {
    echo "No requests captured yet. Point Nimbbl's webhook/callback at https://webhook.site/{$token}, trigger an event, then re-run.\n";
    exit(0);
}

printf("\n%-22s %-7s %-9s %-9s %-22s %s\n", 'captured_at', 'method', 'VERIFY', 'version', 'event_type', 'note');
echo str_repeat('-', 110) . "\n";
foreach ($reqs as $r) {
    $body = $r['content'] ?? '';
    // Try webhook verification; if it's a checkout-callback envelope, fall back to verifyCallback.
    $res = $verifier->verifyWebhook($body, $secret);
    $mode = 'webhook';
    if (empty($res['success']) && (strpos($body, 'globalCloseCheckoutModal') !== false || strpos($body, 'globalHandleCheckoutResponse') !== false)) {
        $res = $verifier->verifyCallback($body, $secret);
        $mode = 'callback';
    }
    printf(
        "%-22s %-7s %-9s %-9s %-22s %s\n",
        substr($r['created_at'] ?? '', 0, 19),
        $r['method'] ?? '?',
        (!empty($res['success']) ? 'VALID' : 'INVALID'),
        $res['version'] ?? '-',
        $res['event_type'] ?? '-',
        $mode . (empty($res['success']) ? ' | ' . ($res['message'] ?? '') : '')
    );
}
