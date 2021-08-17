<?php

declare(strict_types=1);

// require_once __DIR__ . '/../vendor/autoload.php';

use Nimbbl\Api\NimbblApi;
use PHPUnit\Framework\TestCase;
use Nimbbl\Api\NimbblOrder;

final class UserTest extends TestCase
{
    public function testRetrieveOneById(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

        $userId = 138;
        $user = $api->user->retrieveOne($userId);

        $this->assertEmpty($user->error);
        $this->assertEquals($user->id, $userId);
    }

    public function testRetrieveOneByUserId(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');

        $userId = 'user_RoQ7Z5QXg6zqy0rg';
        $user = $api->user->retrieveOne($userId);

        $this->assertEmpty($user->error);
        $this->assertEquals($user->user_id, $userId);
    }

    public function testRetrieveMany(): void
    {
        $api = new NimbblApi('access_key_1MwvMkKkweorz0ry', 'access_secret_81x7ByYkRpB4g05N');
        $manyUsers = $api->user->retrieveMany();
        $this->assertEquals(sizeof($manyUsers['items']), 20);
    }
}
