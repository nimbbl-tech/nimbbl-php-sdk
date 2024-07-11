<?php

require __DIR__.'/../Nimbbl.php';

use Nimbbl\Api\NimbblApi;
use Nimbbl\Api\NimbblOrder;
use Nimbbl\Api\NimbblRefund;

$accessKey = 'access_key_aQA3jkkdnYzG402N';
$secretKey = 'access_secret_WKO7dKY9DxQP60dl';
$url = 'https://apipp.nimbbl.tech/api/';

$api = new NimbblApi($accessKey, $secretKey, $url);

$order_data = [
    'amount_before_tax' => 1,
    'tax' => 0.0,
    'total_amount' => 1,
    'currency' => 'INR',
    'invoice_id'=> 'shopify-s193',
    'user'=> [
        'email'=>'avdhoot@nimbbl.biz',
        'first_name'=> 'Avdhoot',
        // 'country_code'=> '+91',
        'mobile_number'=>'9876543212'
    ]
];

$refund_data = [
    'transaction_id' => 'o_QVzRMzP6k2o6D5jn-240711123924'
    // 'amount' => 1
];

$createOrderRes = $api->order->create($order_data);

$getOrderByOrderIdRes = $api->order->getOrderByOrderId('o_BXyGDl4epOb2ezLX');
$getOrderByInvoiceIdRes = $api->order->getOrderByInvoiceId('shopify-s191');

$refundRes = $api->refund->initiateRefund($refund_data);

print_r($createOrderRes);
print_r($getOrderByOrderIdRes);
print_r($getOrderByInvoiceIdRes);
print_r($refundRes);