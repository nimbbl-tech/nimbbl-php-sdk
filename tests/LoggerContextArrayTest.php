<?php

namespace Nimbbl\Tests;

use Nimbbl\Api\Log\Logger;
use PHPUnit\Framework\TestCase;

final class LoggerContextArrayTest extends TestCase
{
    private $logFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logFile = sys_get_temp_dir() . '/nimbbl_logger_context_array_' . uniqid('', true) . '.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            @unlink($this->logFile);
        }
        parent::tearDown();
    }

    public function testInfoAcceptsContextArrayAsThirdArgument()
    {
        $logger = Logger::getInstance($this->logFile, true);
        $logger->info('Context array test', null, [
            'subMerchantId' => '1001241',
            'orderId' => 'o_test_123',
            'transactionId' => 't_test_123',
            'apiVersion' => 'v3',
            'apiTag' => 'Order.php',
            'uri' => '/api/v3/create-order',
            'statusCode' => 201,
        ]);

        $content = is_file($this->logFile) ? (string) file_get_contents($this->logFile) : '';

        $this->assertStringContainsString('[APIVersion:v3]', $content);
        $this->assertStringContainsString('[APITag:Order]', $content);
        $this->assertStringContainsString('[URI:/api/v3/create-order]', $content);
        $this->assertStringContainsString('[StatusCode:201]', $content);
        $this->assertStringContainsString('[SubMerchantID:1001241]', $content);
        $this->assertStringContainsString('[OrderID:o_test_123]', $content);
        $this->assertStringContainsString('[TransactionID:t_test_123]', $content);
    }
}
