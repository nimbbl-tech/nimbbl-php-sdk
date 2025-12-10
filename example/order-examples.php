<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Nimbbl PHP SDK - Order Examples (Create + Get)
 *
 * Docs:
 * - Create Order: https://nimbbl.biz/docs/api-reference/create-an-order-v-3/
 * - Get Order:    https://nimbbl.biz/docs/api-reference/get-order-v-3/
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';

use Nimbbl\Api\Api;
use Nimbbl\Api\Request;

$config = loadConfig();
$api = initApi($config);

function promptToken($label)
{
    $cachedToken = Request::getCachedToken();
    if ($cachedToken !== null) {
        echo "ℹ Cached token available. Provide $label token or press Enter to use cached.\n";
        echo "$label Token: ";
        $user = trim(fgets(STDIN));
        return $user === '' ? null : $user;
    }
    echo "$label Token: ";
    $user = trim(fgets(STDIN));
    return $user === '' ? null : $user;
}

echo "=== Order Examples (Create + Get) ===\n\n";

// ---- Create Order ----
echo "Create Order:\n";
$merchantToken = promptToken('Merchant');
if ($merchantToken === null) {
    echo "⚠️  Merchant token required to create order.\n";
} else {
    try {
        $orderData = [
            'amount_before_tax' => 2.00,
            'tax' => 0,
            'total_amount' => 1.0,
            'user' => [
                'email' => 'customer@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'country_code' => '+91',
                'mobile_number' => '9876543210',
            ],
            'currency' => 'INR',
            'invoice_id' => 'INV-' . time(),
        ];

        echo "Creating order...\n";
        $order = $api->orders()->createOrder($orderData, $merchantToken);
        if (isset($order['error'])) {
            echo "❌ Error: " . print_r($order['error'], true) . "\n";
        } else {
            $orderId = $order['order_id'] ?? $order['nimbbl_order_id'] ?? 'N/A';
            $invoiceId = $order['invoice_id'] ?? 'N/A';
            $token = $order['token'] ?? 'N/A';
            echo "✅ Order created\n";
            echo "   Order ID: {$orderId}\n";
            echo "   Invoice ID: {$invoiceId}\n";
            echo "   Token: {$token}\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// ---- Get Order by Invoice ID ----
echo "Get Order by Invoice ID:\n";
echo "Enter Invoice ID (or press Enter to skip): ";
$invoiceId = trim(fgets(STDIN));
if ($invoiceId === '') {
    echo "⚠️  Skipping get order (no invoice ID).\n";
} else {
    try {
        $orderToken = promptToken('Order');
        if ($orderToken === null) {
            echo "⚠️  Order token required to fetch order.\n";
        } else {
            $order = $api->orders()->getOrderByInvoiceId($invoiceId, $orderToken);
            if (isset($order['error'])) {
                echo "❌ Error: " . print_r($order['error'], true) . "\n";
            } else {
                $oid = $order['order_id'] ?? $order['nimbbl_order_id'] ?? 'N/A';
                $iid = $order['invoice_id'] ?? 'N/A';
                $stat = $order['status'] ?? 'N/A';
                $amt = $order['total_amount'] ?? 0;
                $curr = $order['currency'] ?? 'INR';
                echo "✅ Order retrieved\n";
                echo "   Order ID: {$oid}\n";
                echo "   Invoice ID: {$iid}\n";
                echo "   Status: {$stat}\n";
                echo "   Amount: {$amt} {$curr}\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Examples Complete ===\n";
