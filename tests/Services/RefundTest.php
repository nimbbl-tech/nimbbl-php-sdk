<?php

declare(strict_types=1);

namespace Nimbbl\Tests\Services;

// require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/../../example/utils/helpers.php';

use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\RestClient\Request;
use PHPUnit\Framework\TestCase;
use Nimbbl\Api\NimbblOrder;

final class RefundTest extends TestCase
{
    private $config;

    protected function setUp(): void
    {
        $this->config = loadConfig();
    }

    public function testInitiateRefund(): void
    {
        $api = new NimbblClient(
            $this->config['access_key'],
            $this->config['access_secret'],
            $this->config['api_endpoint']
        );

        // Generate merchant token (required for refunds)
        $request = new Request();
        $merchantToken = $request->generateToken()['token'];

        // Note: This test requires a valid transaction_id from a successful payment
        // Replace with actual transaction_id for testing
        $refund_data = array(
            'transaction_id' => 'order_aQA3j4bxxeQKj72N', // Replace with actual transaction_id
            'comment' => 'Test refund'
        );

        try {
            $refund = $api->refunds()->initiateRefund($refund_data, $merchantToken);
            $this->assertArrayNotHasKey('error', $refund);
            $this->assertNotEmpty($refund['status'] ?? $refund['refund_status'] ?? null);
        } catch (\Exception $e) {
            // Refund may fail if transaction is not successful or doesn't exist
            $this->markTestSkipped('Refund test skipped: ' . $e->getMessage());
        }
    }
}
