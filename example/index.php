<?php
/**
 * Nimbbl PHP SDK - Examples Index
 * 
 * This file provides a quick overview and links to dedicated example files.
 * Each functionality has its own dedicated example file for better organization.
 */

// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
error_reporting(E_ALL & ~E_DEPRECATED);

echo "=== Nimbbl PHP SDK Examples ===\n\n";
echo "This directory contains dedicated example files for each SDK functionality.\n";
echo "Each example is self-contained and demonstrates best practices.\n\n";

echo "Available Examples:\n";
echo str_repeat('=', 50) . "\n\n";

echo "📦 Core Operations:\n";
echo "  • create-order.php          - Create orders with detailed examples\n";
echo "  • get-order.php             - Retrieve orders by ID or invoice ID\n";
echo "  • generate-token.php        - Generate authentication tokens\n\n";

echo "💳 Payment Operations:\n";
echo "  • payments-examples.php     - Initiate, complete payments, resend OTP\n";
echo "  • payment-links-examples.php - Create and manage payment links\n";
echo "  • refund-examples.php       - Process refunds (full and partial)\n";
echo "  • transaction-status.php   - Enquire transaction status\n\n";

echo "📍 Address Management:\n";
echo "  • addresses-examples.php    - List, create, update, delete addresses\n\n";

echo "🛠️  Utilities:\n";
echo "  • checkout-utilities-examples.php - Payment modes, banks, wallets, EMIs, offers\n";
echo "  • encryption-examples.php   - Data encryption examples\n";
echo "  • exception-handling-examples.php - Error handling patterns\n";
echo "  • logging-example.php      - Logging configuration and usage\n\n";

echo "🔔 Webhooks:\n";
echo "  • webhook-handler.php      - Webhook signature verification and processing\n\n";

echo "🖥️  Tools:\n";
echo "  • cli.php                  - Interactive CLI for testing all APIs\n\n";

echo str_repeat('=', 50) . "\n\n";
echo "To run a specific example:\n";
echo "  php example/create-order.php\n";
echo "  php example/payments-examples.php\n";
echo "  php example/webhook-handler.php\n\n";

echo "For more information, see:\n";
echo "  • README.md - Detailed documentation\n";
echo "  • HOW_TO_RUN.md - Setup and usage instructions\n\n";

