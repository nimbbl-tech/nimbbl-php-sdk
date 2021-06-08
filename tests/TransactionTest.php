<?php

declare(strict_types=1);

// require_once __DIR__ . '/../vendor/autoload.php';

use Nimbbl\Api\NimbblApi;
use PHPUnit\Framework\TestCase;
use Nimbbl\Api\NimbblOrder;

final class TransactionTest extends TestCase
{
    public function testRetrieveOne(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

        $transactionId = 'order_ZR7ldnrrrNRrb79p-20210608022935';
        $transaction = $api->transaction->retrieveOne($transactionId);

        $this->assertEmpty($transaction->error);
        $this->assertEquals($transaction->transaction_id, $transactionId);
    }

    public function testRetrieveMany(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');
        $manyTransactions = $api->order->retrieveMany();

        $this->assertEquals(sizeof($manyTransactions['items']), 20);
    }
}
