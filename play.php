<?php

require_once __DIR__ . '/vendor/autoload.php';

use Nimbbl\Api\NimbblApi;
use Nimbbl\Api\NimbblOrder;
use Nimbbl\Api\NimbblUser;

$api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

// - - - - - - - - - - - - - - - - - - 
// User endpoints. 
// - - - - - - - - - - - - - - - - - - 
// $user = $api->user;

// echo 'Fetch user with id' . PHP_EOL;
// echo json_encode($user->retrieveOne(138)) . PHP_EOL;
// echo $user->id . PHP_EOL;

// echo 'Fetch user with user_id' . PHP_EOL;
// echo json_encode($user->retrieveOne('user_RoQ7Z5QXg6zqy0rg')) . PHP_EOL;

// $manyUsers = $user->retrieveMany();
// echo json_encode($manyUsers);

// foreach ($manyUsers['items'] as $idx => $oneUser) {
//     echo 'Fetched user with id: ' . $oneUser->id . PHP_EOL;
// }

// - - - - - - - - - - - - - - - - - - 
// Order endpoints 
// - - - - - - - - - - - - - - - - - - 
// $order = $api->order;

// echo 'Fetch order with id' . PHP_EOL;
// echo json_encode($order->retrieveOne('order_x47oddEGREZ8ZvLa')) . PHP_EOL;
// if (!$order->error) {
//     echo $order->order_id . PHP_EOL;
// } else {
//     echo 'Error: ' . json_encode($order->error) . PHP_EOL;
// }

// $manyOrders = $order->retrieveMany();
// echo json_encode($manyOrders);

// foreach ($manyOrders['items'] as $idx => $oneOrder) {
//     echo 'Fetched order with id: ' . $oneOrder->order_id . PHP_EOL;
// }

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
echo 'Newly created order is: ' . $newOrder->order_id . PHP_EOL;

// - - - - - - - - - - - - - - - - - - 
// Transaction endpoints 
// - - - - - - - - - - - - - - - - - - 
$oneTransaction = $api->transaction->retrieveOne('order_ZR7ldnrrrNRrb79p-20210608022935');
echo 'Fetch transaction with id' . PHP_EOL;
echo json_encode($oneTransaction) . PHP_EOL;
if (!$oneTransaction->error) {
    echo $oneTransaction->transaction_id . PHP_EOL;
} else {
    echo 'Error: ' . json_encode($oneTransaction->error) . PHP_EOL;
}
