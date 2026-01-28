<?php

declare(strict_types=1);

// require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../example/utils/helpers.php';

use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\RestClient\Request;
use PHPUnit\Framework\TestCase;
use Nimbbl\Api\NimbblOrder;

final class OrderTest extends TestCase
{
    private $config;

    protected function setUp(): void
    {
        $this->config = loadConfig();
    }

    public function testRetrieveOne(): void
    {
        $api = new NimbblClient(
            $this->config['access_key'],
            $this->config['access_secret'],
            $this->config['api_url'],
            $this->config['api_version']
        );

        // Generate merchant token first
        $request = new Request();
        $merchantToken = $request->generateToken()['token'];

        // Create order to get order token
        $orderData = [
            'invoice_id' => 'TEST_' . time(),
            'amount_before_tax' => 100,
            'tax' => 18,
            'total_amount' => 118,
            'currency' => 'INR',
            'user' => [
                'email' => 'test@example.com',
                'first_name' => 'Test',
                'last_name' => 'User',
                'mobile_number' => '9876543210',
                'country_code' => '+91'
            ]
        ];
        $order = $api->orders()->createOrder($orderData, $merchantToken);
        $orderToken = $order['token'] ?? null;

        if (!$orderToken) {
            $this->markTestSkipped('Order token not available');
            return;
        }

        $orderId = $order['nimbbl_order_id'] ?? $order['order_id'] ?? null;
        if (!$orderId) {
            $this->markTestSkipped('Order ID not available');
            return;
        }

        $retrievedOrder = $api->orders()->getOrderById($orderId, $orderToken);
        $this->assertArrayNotHasKey('error', $retrievedOrder);
        $this->assertEquals($retrievedOrder['nimbbl_order_id'] ?? $retrievedOrder['order_id'], $orderId);
    }

    public function testCreateOne(): void
    {
        $api = new NimbblClient(
            $this->config['access_key'],
            $this->config['access_secret'],
            $this->config['api_url'],
            $this->config['api_version']
        );

        // Generate merchant token
        $request = new Request();
        $merchantToken = $request->generateToken()['token'];

        // Create a new order
        $order_data = array(
            'invoice_id' => 'merchant-order-id-' . time(),
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
                    'image_url' => 'https://cdn.pixabay.com/photo/2015/12/09/01/02/mandalas-1084082_960_720.jpg',
                    'description' => 'Convert your dreary device into a bright happy place with this wallpaper.',
                    'sku_id' => 'P1',
                    'amount_before_tax' => 100,
                    'tax' => 18,
                    "total_amount" => 118,
                ]
            ],
        );
        $newOrder = $api->orders()->createOrder($order_data, $merchantToken);
        $this->assertArrayNotHasKey('error', $newOrder);
    }
}
