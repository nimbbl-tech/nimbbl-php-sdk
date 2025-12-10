<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Refund Example
 * 
 * This example demonstrates how to process refunds using the Nimbbl PHP SDK
 * 
 * API Documentation: https://nimbbl.biz/docs/api-reference/refund-a-payment-v-3/
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';

use Nimbbl\Api\Api;
use Nimbbl\Api\Exception\NimbblException;
use Nimbbl\Api\Exception\NotFoundException;
use Nimbbl\Api\Exception\BadRequestException;

// Load configuration
$config = loadConfig();

// Initialize Nimbbl API
$api = initApi($config);

echo "=== Refund Example ===\n\n";

// Get token from user input
echo "Enter Token: ";
$token = trim(fgets(STDIN));
if (empty($token)) {
    echo "✗ Error: Token is required\n";
    exit(1);
}

// Get transaction_id or invoice_id
echo "Enter Transaction ID (or press Enter to use Invoice ID): ";
$transactionId = trim(fgets(STDIN));
echo "Enter Invoice ID (or press Enter to use Transaction ID): ";
$invoiceId = trim(fgets(STDIN));

if (empty($transactionId) && empty($invoiceId)) {
    echo "✗ Error: Either Transaction ID or Invoice ID is required\n";
    exit(1);
}

// Get refund amount (optional - for partial refund)
echo "Enter Refund Amount (or press Enter for full refund): ";
$refundAmount = trim(fgets(STDIN));

// Get comment (optional)
echo "Enter Refund Comment (optional): ";
$comment = trim(fgets(STDIN));

// Build refund data
$refundData = [];
if ($transactionId) {
    $refundData['transaction_id'] = $transactionId;
}
if ($invoiceId) {
    $refundData['invoice_id'] = $invoiceId;
}
if ($refundAmount) {
    $refundData['refund_amount'] = floatval($refundAmount);
}
if ($comment) {
    $refundData['comment'] = $comment;
}

try {
    echo "\nInitiating refund...\n";
    $refund = $api->refunds()->initiateRefund($refundData, $token);
    
    echo "✓ Refund initiated successfully!\n";
    echo "  Refund ID: " . ($refund['refund_id'] ?? $refund['nimbbl_refund_id'] ?? 'N/A') . "\n";
    echo "  Transaction ID: " . ($refund['transaction_id'] ?? $refund['nimbbl_transaction_id'] ?? 'N/A') . "\n";
    echo "  Refund Amount: " . formatAmount($refund['refund_amount'] ?? $refund['amount'] ?? 0, $refund['currency'] ?? 'INR') . "\n";
    echo "  Status: " . ($refund['status'] ?? 'N/A') . "\n";
    if (isset($refund['comment'])) {
        echo "  Comment: " . $refund['comment'] . "\n";
    }
} catch (\Nimbbl\Api\Exception\NotFoundException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "  Error Code: " . $e->getErrorCode() . "\n";
    echo "  Note: Invalid transaction_id or invoice_id. Please verify the ID and try again.\n";
} catch (\Nimbbl\Api\Exception\BadRequestException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "  Error Code: " . $e->getErrorCode() . "\n";
} catch (\Nimbbl\Api\Exception\NimbblException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "  Error Code: " . $e->getErrorCode() . "\n";
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Example Complete ===\n";
echo "\nImportant Notes:\n";
echo "  • Refund transactions once requested cannot be rolled back\n";
echo "  • Be very sure of the amount before initiating a refund\n";
echo "  • Use refund_request_id to avoid duplicate refund requests\n";
echo "  • Use Transaction Status API to check refund status\n";
echo "\nFor more details, see: https://nimbbl.biz/docs/api-reference/refund-a-payment-v-3/\n";

