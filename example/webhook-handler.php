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

use Nimbbl\Api\Api;
use Nimbbl\Api\Webhook;
use Nimbbl\Api\Logger;
use Nimbbl\Api\SdkConstants;
use Nimbbl\Api\Model\WebhookEvent;

// Load configuration
$config = loadConfig();
$logger = Logger::getInstance($config["log_file"] ?? null);

// Initialize API and get webhook handler
$api = new Api($config['access_key'] ?? '', $config['access_secret'] ?? '');
$webhook = $api->webhook();

// Get webhook payload
$payload = $webhook->getPayloadFromInput();

// Get signature from header
$headers = function_exists('getallheaders') ? getallheaders() : [];
$signature = $webhook->getSignatureFromHeaders($headers) ?? '';

// Log incoming webhook
$logger->log("Webhook received. Signature: {$signature}", SdkConstants::LOG_INFO, SdkConstants::COMPONENT_WEBHOOK);

// Verify webhook signature using Webhook class
// Use access_secret for webhook signature verification
$secret = $config['access_secret'] ?? Api::getSecret();
if (empty($secret)) {
    $logger->log("Webhook secret not configured", SdkConstants::LOG_ERROR, SdkConstants::COMPONENT_WEBHOOK);
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Webhook secret not configured'], JSON_PRETTY_PRINT);
    exit;
}

$isValid = $webhook->verifyWebhook($payload, $signature, $secret);

if (!$isValid) {
    $logger->log("Invalid webhook signature", SdkConstants::LOG_ERROR, SdkConstants::COMPONENT_WEBHOOK);
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid signature'], JSON_PRETTY_PRINT);
    exit;
}

// Parse webhook event using Webhook class
$eventData = $webhook->parseWebhookEvent($payload);

if ($eventData === null) {
    $logger->log("Invalid JSON payload", SdkConstants::LOG_ERROR, SdkConstants::COMPONENT_WEBHOOK);
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid JSON'], JSON_PRETTY_PRINT);
    exit;
}

// Create WebhookEvent model
$webhookEvent = new WebhookEvent($eventData);

// Get event type from payload (event_type field in webhook payload per documentation)
$eventType = $eventData['event_type'] ?? $webhookEvent->event ?? null;

// Extract IDs from payload (nimbbl_order_id, nimbbl_transaction_id per documentation)
$nimbblOrderId = $eventData['nimbbl_order_id'] ?? $webhookEvent->order_id ?? null;
$nimbblTransactionId = $eventData['nimbbl_transaction_id'] ?? $webhookEvent->transaction_id ?? null;

// Log webhook event
logMessage("Webhook event_type: " . ($eventType ?? 'N/A'), 'INFO');
logMessage("Nimbbl Order ID: " . ($nimbblOrderId ?? 'N/A'), 'INFO');
logMessage("Nimbbl Transaction ID: " . ($nimbblTransactionId ?? 'N/A'), 'INFO');
logMessage("Webhook payload: " . json_encode($eventData, JSON_PRETTY_PRINT), 'DEBUG');

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
            handlePaymentSuccess($webhookEvent);
            break;
        case 'payment_failed':
            handlePaymentFailed($webhookEvent);
            break;
        case 'payment_reversing':
            handlePaymentReversing($webhookEvent);
            break;
        case 'payment_reversal_failed':
            handlePaymentReversalFailed($webhookEvent);
            break;
        case 'payment_reversed':
            handlePaymentReversed($webhookEvent);
            break;
        case 'refund_success':
            handleRefundSuccess($webhookEvent);
            break;
        case 'refund_failed':
            handleRefundFailed($webhookEvent);
            break;
        case 'refund_pending':
            handleRefundPending($webhookEvent);
            break;
        default:
            logMessage("Unknown event type: " . ($eventType ?? 'N/A'), 'WARNING');
            break;
    }
    
    // Always return 200 to acknowledge receipt (required within 15 seconds)
    // If 200 is not returned, Nimbbl will retry the webhook up to 5 times
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Webhook processed'], JSON_PRETTY_PRINT);
    exit;
    
} catch (Exception $e) {
    logMessage("Error processing webhook: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Processing error'], JSON_PRETTY_PRINT);
    exit;
}

/**
 * Handle order.created event
 * 
 * @param WebhookEvent $event Webhook event object
 */
function handleOrderCreated(WebhookEvent $event)
{
    logMessage("Order created: " . ($event->order_id ?? 'N/A'), 'INFO');
    
    // Get order data from event
    $orderData = $event->getOrderData();
    
    // Your business logic here
    // Example: Update your database, send notification, etc.
    
    if ($event->order_id) {
        // Process order creation
        // Example: Update order status in your database
        $logger->log("Processing order creation for order_id: {$event->order_id}", SdkConstants::LOG_INFO, SdkConstants::COMPONENT_WEBHOOK);
    }
}

/**
 * Handle order.updated event
 * 
 * @param WebhookEvent $event Webhook event object
 */
function handleOrderUpdated(WebhookEvent $event)
{
    logMessage("Order updated: " . ($event->order_id ?? 'N/A'), 'INFO');
    
    // Get order data from event
    $orderData = $event->getOrderData();
    
    // Your business logic here
    // Example: Sync order status with your system
}

/**
 * Handle payment.success event
 * 
 * @param WebhookEvent $event Webhook event object
 */
function handlePaymentSuccess(WebhookEvent $event)
{
    logMessage("Payment successful: " . ($event->transaction_id ?? 'N/A'), 'INFO');
    
    // Get transaction data from event
    $transactionData = $event->getTransactionData();
    $orderData = $event->getOrderData();
    
    // Your business logic here
    // Example: 
    // - Update order status to 'paid'
    // - Send confirmation email
    // - Fulfill order
    // - Update inventory
    
    if ($event->transaction_id && $event->order_id) {
        $logger->log("Processing successful payment for transaction: {$event->transaction_id}, order: {$event->order_id}", SdkConstants::LOG_INFO, SdkConstants::COMPONENT_WEBHOOK);
        
        // Verify payment signature before processing
        if ($transactionData && $orderData) {
            $util = new \Nimbbl\Api\Util();
            $orderAmount = $orderData['total_amount'] ?? $orderData['amount'] ?? 0;
            
            // Build attributes array for signature verification
            $attributes = [
                'transaction' => $transactionData,
                'order' => $orderData,
                'nimbbl_transaction_id' => $event->transaction_id,
            ];
            
            $isValid = $util->verifyPaymentSignature($attributes, (float)$orderAmount);
            
            if ($isValid) {
                $logger->log("Payment signature verified successfully", SdkConstants::LOG_INFO, SdkConstants::COMPONENT_WEBHOOK);
                // Process payment - update database, send emails, etc.
            } else {
                $logger->log("Payment signature verification failed", SdkConstants::LOG_ERROR, SdkConstants::COMPONENT_WEBHOOK);
            }
        }
    }
}

/**
 * Handle payment.failed event
 * 
 * @param WebhookEvent $event Webhook event object
 */
function handlePaymentFailed(WebhookEvent $event)
{
    logMessage("Payment failed: " . ($event->transaction_id ?? 'N/A'), 'INFO');
    
    // Get transaction data from event
    $transactionData = $event->getTransactionData();
    
    // Your business logic here
    // Example:
    // - Update order status to 'payment_failed'
    // - Send notification to customer
    // - Log failure reason
}

/**
 * Handle refund.created event
 * 
 * @param WebhookEvent $event Webhook event object
 */
function handleRefundCreated(WebhookEvent $event)
{
    logMessage("Refund created: " . ($event->refund_id ?? 'N/A'), 'INFO');
    
    // Get refund data from event
    $refundData = $event->getRefundData();
    
    // Your business logic here
    // Example: Update refund status in your system
}

/**
 * Handle refund.processed event
 * 
 * @param WebhookEvent $event Webhook event object
 */
function handleRefundProcessed(WebhookEvent $event)
{
    logMessage("Refund processed: " . ($event->refund_id ?? 'N/A'), 'INFO');
    
    // Get refund data from event
    $refundData = $event->getRefundData();
    
    // Your business logic here
    // Example:
    // - Update order status
    // - Send refund confirmation
    // - Update accounting records
}

/**
 * Handle payment.reversing event
 * 
 * @param WebhookEvent $event Webhook event object
 */
function handlePaymentReversing(WebhookEvent $event)
{
    logMessage("Payment reversing: " . ($event->transaction_id ?? 'N/A'), 'INFO');
    
    // Get transaction data from event
    $transactionData = $event->getTransactionData();
    
    // Your business logic here
    // Example: Update transaction status to 'reversing'
}

/**
 * Handle payment.reversal_failed event
 * 
 * @param WebhookEvent $event Webhook event object
 */
function handlePaymentReversalFailed(WebhookEvent $event)
{
    logMessage("Payment reversal failed: " . ($event->transaction_id ?? 'N/A'), 'INFO');
    
    // Get transaction data from event
    $transactionData = $event->getTransactionData();
    
    // Your business logic here
    // Example: Log reversal failure, notify admin
}

/**
 * Handle payment.reversed event
 * 
 * @param WebhookEvent $event Webhook event object
 */
function handlePaymentReversed(WebhookEvent $event)
{
    logMessage("Payment reversed: " . ($event->transaction_id ?? 'N/A'), 'INFO');
    
    // Get transaction data from event
    $transactionData = $event->getTransactionData();
    
    // Your business logic here
    // Example:
    // - Update order status
    // - Reverse inventory changes
    // - Update accounting records
}

/**
 * Handle refund.success event
 * 
 * @param WebhookEvent $event Webhook event object
 */
function handleRefundSuccess(WebhookEvent $event)
{
    logMessage("Refund successful: " . ($event->refund_id ?? 'N/A'), 'INFO');
    
    // Get refund data from event
    $refundData = $event->getRefundData();
    
    // Your business logic here
    // Example:
    // - Update order status
    // - Send refund confirmation
    // - Update accounting records
}

/**
 * Handle refund.failed event
 * 
 * @param WebhookEvent $event Webhook event object
 */
function handleRefundFailed(WebhookEvent $event)
{
    logMessage("Refund failed: " . ($event->refund_id ?? 'N/A'), 'INFO');
    
    // Get refund data from event
    $refundData = $event->getRefundData();
    
    // Your business logic here
    // Example: Log failure, notify admin, update refund status
}

/**
 * Handle refund.pending event
 * 
 * @param WebhookEvent $event Webhook event object
 */
function handleRefundPending(WebhookEvent $event)
{
    logMessage("Refund pending: " . ($event->refund_id ?? 'N/A'), 'INFO');
    
    // Get refund data from event
    $refundData = $event->getRefundData();
    
    // Your business logic here
    // Example: Update refund status to 'pending', notify customer
}

