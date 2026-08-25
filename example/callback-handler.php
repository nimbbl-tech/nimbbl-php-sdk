<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Callback Handler Example
 *
 * Handles and verifies the Nimbbl payment/checkout callback on your server.
 *
 * There are two callbacks (both may be pointed at this endpoint):
 *   - Payment callback  (server-side)  -> delivered to your callback_url
 *   - Checkout callback (client-side)  -> fired in the browser and forwarded to your server
 *
 * SignatureVerifier::verifyCallback() reads the `version` field and automatically
 * chooses the correct handling:
 *   - version == "v4" -> new signed envelope (HMAC over the whole payload; encrypted => decrypt authenticates)
 *   - version absent / v1 / v2 / v3 -> legacy per-field signature handling
 *
 * IMPORTANT
 * - Never fulfil an order on the client-side callback alone. Always verify server-side
 *   (this file) and confirm the final status via webhook or the Transaction Enquiry API.
 * - For pre-authorization, checkout_status="success" with reason="payment_authorized"
 *   means the funds are only HELD — capture before fulfilling.
 *
 * Documentation: https://nimbbl.biz/docs/guides/integration/callback-payloads/
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';
require_once __DIR__ . '/utils/cli_output.php';

use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\Common\SignatureVerifier;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Log\Logger;

/**
 * Display info when run from CLI / GET (not an actual callback POST).
 */
function displayCallbackInfo()
{
    printInfo("Callback handling is designed for HTTP requests, not CLI.\n");
    printInfo("Deploy callback-handler.php to your web server (HTTPS) and set it as your callback URL.\n");
    printInfo("It verifies both v4 (signed envelope) and legacy (v1/v2/v3) callbacks automatically.\n");
    printInfo("\nFor pre-auth: reason='payment_authorized' means funds are HELD — capture before fulfilling.\n");
}

// Run info mode when executed from CLI or via a non-POST request.
if (php_sapi_name() === 'cli' || (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST')) {
    displayCallbackInfo();
    if (php_sapi_name() === 'cli') {
        exit(0);
    }
}

$config = loadConfig();
$logger = Logger::getInstance($config['log_file'] ?? null);

// Read the raw callback body. Redirect integrations may send it as a base64 `response`
// parameter; POST/popup integrations send raw JSON in the request body.
$rawBody = $_POST['response']
    ?? $_GET['response']
    ?? file_get_contents('php://input');

if (empty($rawBody)) {
    $logger->error("Callback body is empty");
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Callback body is empty']);
    exit;
}

$secret = $config['access_secret'] ?? NimbblClient::getSecret();
if (empty($secret)) {
    $logger->error("Callback secret not configured");
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Callback secret not configured']);
    exit;
}

// Verify + parse (version-aware; supports v4 envelope and legacy formats).
$verifier = new SignatureVerifier();
$result = $verifier->verifyCallback($rawBody, $secret);

if (!$result['success']) {
    $logger->error("Callback verification failed: " . ($result['message'] ?? 'Unknown error'));
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Callback verification failed']);
    exit;
}

$payload = $result['payload'] ?? [];
$logger->info("Callback verified. version=" . ($result['version'] ?? 'legacy'));

// Prefer the checkout callback's machine-readable fields; fall back to transaction.status
// for the payment callback / legacy payloads.
$checkoutStatus = $payload[JsonKeys::CHECKOUT_STATUS] ?? null;
$reason = $payload[JsonKeys::REASON] ?? null;
$txnStatus = $payload[JsonKeys::TRANSACTION][JsonKeys::STATUS] ?? ($payload[JsonKeys::STATUS] ?? null);

$logger->info("Callback: checkout_status={$checkoutStatus} reason={$reason} txn_status={$txnStatus}");

// Decide next action. Switch on `reason` (checkout callback) — it is a fixed, machine-readable set.
switch ($reason) {
    case 'payment_authorized':
        // PRE-AUTH: funds held, NOT collected. Do not fulfil. Capture first, then verify via webhook/enquiry.
        $logger->info("Pre-auth authorized — do NOT fulfil. Capture the authorization, then confirm.");
        break;
    case 'payment_captured':
    case 'order_completed':
        // Verify final status via webhook/Transaction Enquiry, then fulfil.
        $logger->info("Payment captured/completed — verify server-side, then fulfil.");
        break;
    case 'payment_failed':
        $logger->info("Payment failed. retry=" . var_export($payload[JsonKeys::RETRY] ?? null, true));
        break;
    case 'user_cancelled':
    case 'max_retries_exhausted':
    case 'no_payment_methods_configured':
    case 'timed_out':
    case 'invalid_session':
        $logger->info("Checkout ended without payment: {$reason}");
        break;
    default:
        // Payment callback / legacy: fall back to the transaction status.
        if ($txnStatus === 'authorized') {
            $logger->info("Transaction authorized (pre-auth) — do NOT fulfil; capture first.");
        } elseif (in_array($txnStatus, ['succeeded', 'success'], true)) {
            $logger->info("Transaction succeeded — verify server-side, then fulfil.");
        } else {
            $logger->info("Callback status: " . ($txnStatus ?? 'unknown'));
        }
        break;
}

http_response_code(200);
header('Content-Type: application/json');
echo json_encode([JsonKeys::STATUS => JsonKeys::SUCCESS, JsonKeys::MESSAGE => 'Callback processed']);
exit;
