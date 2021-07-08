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

        $transactionId = 'order_aKQvPpdLZbmMkv9z-20210707111956';
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

    public function testTransactionEnquiry(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');
        $order_data = array(
            'order_id' => 'order_aKQvPpdLZbmMkv9z',
            'payment_mode' => 'PayTM',
            'transaction_id' => 'order_aKQvPpdLZbmMkv9z-20210707111956'
        );
        $transaction = $api->transaction->transactionEnquiry($order_data);
        $this->assertEmpty($transaction->error);
        $this->assertEquals($transaction->nimbbl_transaction_id, $order_data['transaction_id']);
    }

    // public function testInitiateRefund(): void
    // {
    //     $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');
    //     $order_data = array(
    //         'order_id' => 'order_aQA3j4bxxeQKj72N',
    //         'transaction_id' => 'order_aQA3j4bxxeQKj72N-20210706145203'
    //     );
    //     $transaction = $api->transaction->initiateRefund($order_data);
    //     $this->assertEmpty($transaction->error);
    //     $this->assertNotEmpty($transaction->attributes['status']);
    // }

    public function testRetrieveTransactionByOrderId(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

        $orderId = 'order_amG06aE46A5a53Nj';
        $transactions = $api->transaction->retrieveTransactionByOrderId($orderId);
        
        $this->assertLessThan(sizeof($transactions['items']),0);
    }

    public function testRetrieveRefundById(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

        $refundId = 'order_amG06aE46A5a53Nj-20210707112243';
        $refund = $api->transaction->retrieveRefundById($refundId);
        $this->assertEmpty($refund->error);
        $this->assertEquals($refund->transaction_id, $refundId);
    }

    public function testRetrieveRefundByOrderId(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

        $orderId = 'order_amG06aE46A5a53Nj';
        $refunds = $api->transaction->retrieveRefundByOrderId($orderId);
        
        $this->assertLessThan(sizeof($refunds['items']),0);
    }

    public function testRetrieveRefundByTxnId(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

        $transactionId = 'order_aKQvPpdLZbmMkv9z-20210707111956';
        $refunds = $api->transaction->retrieveRefundByTxnId($transactionId);
        
        $this->assertLessThan(sizeof($refunds['items']),0);
    }
    
}
