<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Webhook Handler Example
 * 
 * This example demonstrates how to handle and verify Nimbbl webhooks
 * 
 * Documentation: https://nimbbl.biz/docs/standard-checkout/completing-integration/keeping-system-updated/
 * 
 * Setup:
 * 1. Deploy this file to your web server (HTTPS required)
 * 2. Ensure the URL accepts POST requests and returns 200 within 15 seconds
 * 3. Configure webhook URL in Nimbbl Dashboard or contact support@nimbbl.tech
 * 4. Update access_secret in config.php (used for webhook verification)
 * 
 * Important:
 * - URL must be HTTPS and publicly accessible
 * - Must return 200 response within 15 seconds (or webhook will be retried)
 * - Handle idempotency (same webhook may be received multiple times)
 * - Webhook order is not guaranteed
 * 
 * Supported Events:
 * - payment_success, payment_failed, payment_reversing
 * - payment_reversal_failed, payment_reversed
 * - refund_success, refund_failed, refund_pending
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';
require_once __DIR__ . '/utils/cli_output.php';

use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\Common\SignatureVerifier;
use Nimbbl\Api\Common\PayloadHelperUtils;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Log\Logger;
use Nimbbl\Api\Common\SdkConstants;

/**
 * Display Webhook Information - Function to be called from cli.php or standalone
 */
function displayWebhookInfo()
{
    printInfo("Webhook handling is designed for HTTP requests, not CLI.\n");
    printInfo("To set up webhooks:\n");
    printInfo("1. Deploy webhook-handler.php to your web server (HTTPS required)\n");
    printInfo("2. Ensure the URL accepts POST requests and returns 200 within 15 seconds\n");
    printInfo("3. Configure webhook URL in Nimbbl Dashboard or contact support@nimbbl.tech\n");
    printInfo("4. Webhooks will be sent to your configured URL\n");
    printInfo("\nImportant:\n");
    printInfo("- URL must be HTTPS and publicly accessible\n");
    printInfo("- Must return 200 response within 15 seconds\n");
    printInfo("- Handle idempotency (same webhook may be received multiple times)\n");
    printInfo("- Webhook order is not guaranteed\n");
    printInfo("\nSupported Events:\n");
    printInfo("- payment_success, payment_failed, payment_reversing\n");
    printInfo("- payment_reversal_failed, payment_reversed\n");
    printInfo("- refund_success, refund_failed, refund_pending\n");
    printInfo("\nFor implementation details, check example/webhook-handler.php\n");
}

// Only run if this file is executed directly (not included) and not as a webhook handler
// Check if it's a CLI execution (not a POST request)
if (php_sapi_name() === 'cli' || (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST')) {
    // This is being run from CLI or GET request, not as a webhook handler
    displayWebhookInfo();
    exit(0);
}

// Load configuration
$config = loadConfig();
$logger = Logger::getInstance($config["log_file"] ?? null);

// Get webhook payload from input stream
$payload = file_get_contents('php://input');

if (empty($payload)) {
    $logger->error("Webhook payload is empty");
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Webhook payload is empty'], JSON_PRETTY_PRINT);
    exit;
}

// Log incoming webhook
$logger->info("Webhook received. Payload length: " . strlen($payload));

// Use access_secret for webhook signature verification
$secret = $config['access_secret'] ?? NimbblClient::getSecret();
if (empty($secret)) {
    $logger->error("Webhook secret not configured");
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Webhook secret not configured'], JSON_PRETTY_PRINT);
    exit;
}

// Parse and unwrap the payload using PayloadHelperUtils (handles encryption, unwrapping, etc.)
try {
    $eventData = PayloadHelperUtils::parseResponse($payload, $secret);
} catch (Exception $e) {
    $logger->error("Webhook parse error: " . $e->getMessage());
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Webhook parse error: ' . $e->getMessage()], JSON_PRETTY_PRINT);
    exit;
}

// Verify webhook signature
$verifier = new SignatureVerifier();
$result = $verifier->verifySignature($eventData, $secret);

if (!$result['success']) {
    $logger->error("Webhook signature verification failed: " . ($result['message'] ?? 'Unknown error'));
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Webhook signature verification failed'], JSON_PRETTY_PRINT);
    exit;
}

// Get event type from payload (event_type field in webhook payload per documentation)
$eventType = $eventData[JsonKeys::EVENT_TYPE] ?? null;

// Extract IDs from payload (nimbbl_order_id, nimbbl_transaction_id per documentation)
$nimbblOrderId = getOrderId($eventData);
$nimbblTransactionId = getTransactionId($eventData);

// Log webhook event
$logger->info("Webhook event_type: " . ($eventType ?? 'N/A'));
$logger->info("Nimbbl Order ID: " . ($nimbblOrderId ?? 'N/A'));
$logger->info("Nimbbl Transaction ID: " . ($nimbblTransactionId ?? 'N/A'));
$logger->debug("Webhook payload: " . json_encode($eventData, JSON_PRETTY_PRINT));

// Process webhook event using WebhookEvent model
// IMPORTANT: Handle idempotency - same webhook may be received multiple times
// IMPORTANT: Must return 200 within 15 seconds or webhook will be retried
// IMPORTANT: Webhook order is not guaranteed - handle events independently
try {
    // Use event_type from payload (already extracted above)

    // Handle webhook events based on documentation:
    // https://nimbbl.biz/docs/standard-checkout/completing-integration/keeping-system-updated/
    switch ($eventType) {
        case 'payment_success':
            handlePaymentSuccess($eventData);
            break;
        case 'payment_failed':
            handlePaymentFailed($eventData);
            break;
        case 'payment_reversing':
            handlePaymentReversing($eventData);
            break;
        case 'payment_reversal_failed':
            handlePaymentReversalFailed($eventData);
            break;
        case 'payment_reversed':
            handlePaymentReversed($eventData);
            break;
        case 'refund_success':
            handleRefundSuccess($eventData);
            break;
        case 'refund_failed':
            handleRefundFailed($eventData);
            break;
        case 'refund_pending':
            handleRefundPending($eventData);
            break;
        default:
            Logger::getInstance()->warning("Unknown event type: " . ($eventType ?? 'N/A'));
            break;
    }

    // Always return 200 to acknowledge receipt (required within 15 seconds)
    // If 200 is not returned, Nimbbl will retry the webhook up to 5 times
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([JsonKeys::STATUS => JsonKeys::SUCCESS, JsonKeys::MESSAGE => 'Webhook processed'], JSON_PRETTY_PRINT);
    exit;

} catch (Exception $e) {
    Logger::getInstance()->error("Error processing webhook: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Processing error'], JSON_PRETTY_PRINT);
    exit;
}

/**
 * Handle order.created event
 * 
 * @param array $event Webhook event payload
 */
function handleOrderCreated(array $event)
{
    Logger::getInstance()->info("Order created: " . (getOrderId($event) ?? 'N/A'));

    // Get order data from event
    $orderData = getOrderData($event);

    // Your business logic here
    // Example: Update your database, send notification, etc.

    $orderId = getOrderId($event);
    if ($orderId) {
        // Process order creation
        // Example: Update order status in your database
        Logger::getInstance()->info("Processing order creation for order_id: {$orderId}");
    }
}

/**
 * Handle order.updated event
 * 
 * @param array $event Webhook event payload
 */
function handleOrderUpdated(array $event)
{
    Logger::getInstance()->info("Order updated: " . (getOrderId($event) ?? 'N/A'));

    // Get order data from event
    $orderData = getOrderData($event);

    // Your business logic here
    // Example: Sync order status with your system
}

/**
 * Handle payment.success event
 * 
 * @param array $event Webhook event payload
 */
function handlePaymentSuccess(array $event)
{
    Logger::getInstance()->info("Payment successful: " . (getTransactionId($event) ?? 'N/A'));

    // Get transaction data from event
    $transactionData = getTransactionData($event);
    $orderData = getOrderData($event);

    // Your business logic here
    // Example: 
    // - Update order status to 'paid'
    // - Send confirmation email
    // - Fulfill order
    // - Update inventory

    $transactionId = getTransactionId($event);
    $orderId = getOrderId($event);

    if ($transactionId && $orderId) {
        Logger::getInstance()->info("Processing successful payment for transaction: {$transactionId}, order: {$orderId}");

            // Verify payment signature before processing
            if ($transactionData && $orderData) {
                $verifier = new SignatureVerifier();

                // Build attributes array for signature verification
                $attributes = [
                    'transaction' => $transactionData,
                    'order' => $orderData,
                ];

                $result = $verifier->verifyPaymentSignature($attributes, NimbblClient::getSecret());
                $isValid = $result['success'];

                if ($isValid) {
                    Logger::getInstance()->info("Payment signature verified successfully");
                    // Process payment - update database, send emails, etc.
                } else {
                    Logger::getInstance()->error("Payment signature verification failed");
                }
            }
    }
}

/**
 * Handle payment.failed event
 * 
 * @param array $event Webhook event payload
 */
function handlePaymentFailed(array $event)
{
    Logger::getInstance()->info("Payment failed: " . (getTransactionId($event) ?? 'N/A'));

    // Get transaction data from event
    $transactionData = getTransactionData($event);

    // Your business logic here
    // Example:
    // - Update order status to 'payment_failed'
    // - Send notification to customer
    // - Log failure reason
}

/**
 * Handle refund.created event
 * 
 * @param array $event Webhook event payload
 */
function handleRefundCreated(array $event)
{
    Logger::getInstance()->info("Refund created: " . (getRefundId($event) ?? 'N/A'));

    // Get refund data from event
    $refundData = getRefundData($event);

    // Your business logic here
    // Example: Update refund status in your system
}

/**
 * Handle refund.processed event
 * 
 * @param array $event Webhook event payload
 */
function handleRefundProcessed(array $event)
{
    Logger::getInstance()->info("Refund processed: " . (getRefundId($event) ?? 'N/A'));

    // Get refund data from event
    $refundData = getRefundData($event);

    // Your business logic here
    // Example:
    // - Update order status
    // - Send refund confirmation
    // - Update accounting records
}

/**
 * Handle payment.reversing event
 * 
 * @param array $event Webhook event payload
 */
function handlePaymentReversing(array $event)
{
    Logger::getInstance()->info("Payment reversing: " . (getTransactionId($event) ?? 'N/A'));

    // Get transaction data from event
    $transactionData = getTransactionData($event);

    // Your business logic here
    // Example: Update transaction status to 'reversing'
}

/**
 * Handle payment.reversal_failed event
 * 
 * @param array $event Webhook event payload
 */
function handlePaymentReversalFailed(array $event)
{
    Logger::getInstance()->info("Payment reversal failed: " . (getTransactionId($event) ?? 'N/A'));

    // Get transaction data from event
    $transactionData = getTransactionData($event);

    // Your business logic here
    // Example: Log reversal failure, notify admin
}

/**
 * Handle payment.reversed event
 * 
 * @param array $event Webhook event payload
 */
function handlePaymentReversed(array $event)
{
    Logger::getInstance()->info("Payment reversed: " . (getTransactionId($event) ?? 'N/A'));

    // Get transaction data from event
    $transactionData = getTransactionData($event);

    // Your business logic here
    // Example:
    // - Update order status
    // - Reverse inventory changes
    // - Update accounting records
}

/**
 * Handle refund.success event
 * 
 * @param array $event Webhook event payload
 */
function handleRefundSuccess(array $event)
{
    Logger::getInstance()->info("Refund successful: " . (getRefundId($event) ?? 'N/A'));

    // Get refund data from event
    $refundData = getRefundData($event);

    // Your business logic here
    // Example:
    // - Update order status
    // - Send refund confirmation
    // - Update accounting records
}

/**
 * Handle refund.failed event
 * 
 * @param array $event Webhook event payload
 */
function handleRefundFailed(array $event)
{
    Logger::getInstance()->info("Refund failed: " . (getRefundId($event) ?? 'N/A'));

    // Get refund data from event
    $refundData = getRefundData($event);

    // Your business logic here
    // Example: Log failure, notify admin, update refund status
}

/**
 * Handle refund.pending event
 * 
 * @param array $event Webhook event payload
 */
function handleRefundPending(array $event)
{
    Logger::getInstance()->info("Refund pending: " . (getRefundId($event) ?? 'N/A'));

    // Get refund data from event
    $refundData = getRefundData($event);

    // Your business logic here
    // Example: Update refund status to 'pending', notify customer
}

/**
 * Helper: Extract order ID from event payload
 */
function getOrderId(array $event): ?string
{
    return $event[JsonKeys::NIMBBL_ORDER_ID]
        ?? $event[JsonKeys::ORDER_ID]
        ?? ($event[JsonKeys::ORDER][JsonKeys::ORDER_ID] ?? null);
}

/**
 * Helper: Extract transaction ID from event payload
 */
function getTransactionId(array $event): ?string
{
    return $event[JsonKeys::NIMBBL_TRANSACTION_ID]
        ?? $event[JsonKeys::TRANSACTION_ID]
        ?? ($event[JsonKeys::TRANSACTION][JsonKeys::TRANSACTION_ID] ?? null);
}

/**
 * Helper: Extract refund ID from event payload
 */
function getRefundId(array $event): ?string
{
    return $event['refund_id']
        ?? ($event['refund']['refund_id'] ?? null);
}

/**
 * Helper: Extract order data
 */
function getOrderData(array $event): ?array
{
    return $event[JsonKeys::ORDER] ?? null;
}

/**
 * Helper: Extract transaction data
 */
function getTransactionData(array $event): ?array
{
    return $event[JsonKeys::TRANSACTION] ?? null;
}

/**
 * Helper: Extract refund data
 */
function getRefundData(array $event): ?array
{
    return $event['refund'] ?? null;
}

