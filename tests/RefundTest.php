<?php

declare(strict_types=1);

// require_once __DIR__ . '/../vendor/autoload.php';

use Nimbbl\Api\NimbblApi;
use PHPUnit\Framework\TestCase;
use Nimbbl\Api\NimbblOrder;

final class RefundTest extends TestCase
{
    public function testRetrieveOne(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

        $refundId = 'order_RoQ7Zyy2zagPA0rg-20211007085901';
        $refund = $api->refund->retrieveOne($refundId);
        $this->assertEmpty($refund->error);
        $this->assertEquals($refund->transaction_id, $refundId);
    }

    public function testRetrieveMany(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');
        $manyrefunds = $api->order->retrieveMany();

        $this->assertEquals(sizeof($manyrefunds['items']), 20);
    }

    public function testInitiateRefund(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');
        $order_data = array(
            'order_id' => 'order_aQA3j4bxxeQKj72N',
            'refund_id' => 'order_aQA3j4bxxeQKj72N-20210706145203'
        );
        $refund = $api->refund->initiateRefund($order_data);
        $this->assertEmpty($refund->error);
        $this->assertNotEmpty($refund->attributes['status']);
    }


    public function testRetrieveRefundByOrderId(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

        $orderId = 'order_RoQ7Zyy2zagPA0rg';
        $refunds = $api->refund->retrieveRefundByOrderId($orderId);
        
        $this->assertLessThan(sizeof($refunds['items']),0);
    }

    public function testRetrieveRefundByTxnId(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

        $refundId = 'order_aKQvPpdLZbmMkv9z-20210707111956';
        $refunds = $api->refund->retrieveRefundByTxnId($refundId);
        
        $this->assertLessThan(sizeof($refunds['items']),0);
    }
    
}
