<?php

declare(strict_types=1);

// require_once __DIR__ . '/../vendor/autoload.php';

use Nimbbl\Api\NimbblApi;
use PHPUnit\Framework\TestCase;
use Nimbbl\Api\NimbblOrder;

final class OrderTest extends TestCase
{
    public function testRetrieveOne(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

        $orderId = 'order_x47oddEGREZ8ZvLa';
        $order = $api->order->retrieveOne($orderId);
        $this->assertEmpty($order->error);
        $this->assertEquals($order->order_id, $orderId);
    }

    public function testRetrieveMany(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');
        $manyOrders = $api->order->retrieveMany();

        $this->assertEquals(sizeof($manyOrders['items']), 20);
    }

    public function testCreateOne(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

        // Create a new order. 
        $order_data = array(
            'referrer_platform' => 'woocommerce',
            'merchant_shopfront_domain' => 'http://example.com',
            'invoice_id' => 'merchant-order-id',
            'order_date' => date('Y-m-d H:i:s'),
            'currency' => 'INR',
            'amount_before_tax' => 100,
            'tax' => 18,
            'total_amount' => 118,
            "user" => [
                "mobile_number" => '9987027067',
                "email" => 'harish.rk.patel@gmail.com',
                "first_name" => 'Harish',
                "last_name" => 'Patel',
            ],
            'shipping_address' => [
                'area' => 'Goregaon East',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400063',
                'address_type' => 'home'
            ],
            "order_line_items" => [
                [
                    "title" => "Awesome Product",
                    "quantity" => 1,
                    'uom' => '',
                    'image_url' => 'https://cdn.pixabay.com/photo/2015/12/09/01/02/mandalas-1084082_960_720.jpg',
                    'description' => 'Convert your dreary device into a bright happy place with this wallpaper.',
                    'sku_id' => 'P1',
                    'amount_before_tax' => 100,
                    'tax' => 18,
                    "total_amount" => 118,
                ]
            ],
            'description' => 'This is a test order...',
        );
        $newOrder = $api->order->create($order_data);
        $this->assertEmpty($newOrder->error);
    }
}
