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
