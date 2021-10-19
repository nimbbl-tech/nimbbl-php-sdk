<?php

declare(strict_types=1);

// require_once __DIR__ . '/../vendor/autoload.php';

use Nimbbl\Api\NimbblApi;
use PHPUnit\Framework\TestCase;
use Nimbbl\Api\NimbblOrder;

final class TransactionTest extends TestCase
{
    // public function testRetrieveOne(): void
    // {
    //     $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

    //     $transactionId = 'order_aKQvPpdLZbmMkv9z-20210707111956';
    //     $transaction = $api->transaction->retrieveOne($transactionId);
    //     $this->assertEmpty($transaction->error);
    //     $this->assertEquals($transaction->transaction_id, $transactionId);
    // }

    // public function testRetrieveMany(): void
    // {
    //     $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');
    //     $manyTransactions = $api->order->retrieveMany();

    //     $this->assertEquals(sizeof($manyTransactions['items']), 20);
    // }

    // public function testTransactionEnquiry(): void
    // {
    //     $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');
    //     $order_data = array(
    //         'order_id' => 'order_aKQvPpdLZbmMkv9z',
    //         'payment_mode' => 'PayTM',
    //         'transaction_id' => 'order_aKQvPpdLZbmMkv9z-20210707111956'
    //     );
    //     $transaction = $api->transaction->transactionEnquiry($order_data);
    //     $this->assertEmpty($transaction->error);
    //     $this->assertEquals($transaction->nimbbl_transaction_id, $order_data['transaction_id']);
    // }

    public function testRetrieveTransactionByOrderId(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

        $orderId = 'order_amG06aE46A5a53Nj';
        $transactions = $api->transaction->retrieveTransactionByOrderId($orderId);
        $this->assertEmpty($transaction->error);
        $this->assertLessThan(sizeof($transactions['items']),0);
    }

    // public function testSignature(){
    //     $api = new NimbblApi('access_key_rQv9VOyaVwkrD3zg', 'access_secret_NYP0EYDMe9p1Z0GD');
    //     $data = array ( 
    //         "status" => "success",
    //         "nimbbl_signature" => "4ebc9f512df7d0b938c866d04240bc13c76f4f676f484872d39659ba25e7f5d5",
    //         "nimbbl_transaction_id" => "order_6EAvqPa1JrppP7PD-20210715141921",
    //         "merchant_order_id" => 95 );
            
    //     $transactions = $api->util->verifyPaymentSignature($data);

    //     $this->assertNotEmpty($transactions);
    // }
}
