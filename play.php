<?php

require_once __DIR__ . '/vendor/autoload.php';

use Nimbbl\Api\NimbblOrder;
use Nimbbl\Api\NimbblUser;

// // User endpoints. 
// $user = new NimbblUser();

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

// Order endpoints 
$order = new NimbblOrder();

echo 'Fetch order with id' . PHP_EOL;
echo json_encode($order->retrieveOne('order_x47oddEGREZ8ZvLa')) . PHP_EOL;
if (!$order->error) {
    echo $order->order_id . PHP_EOL;
} else {
    echo 'Error: ' . json_encode($order->error) . PHP_EOL;
}

$manyOrders = $order->retrieveMany();
echo json_encode($manyOrders);

foreach ($manyOrders['items'] as $idx => $oneOrder) {
    echo 'Fetched order with id: ' . $oneOrder->order_id . PHP_EOL;
}
