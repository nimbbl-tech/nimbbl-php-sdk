<?php

declare(strict_types=1);

namespace Nimbbl\Tests\RestClient;

use Nimbbl\Api\RestClient\Request;
use PHPUnit\Framework\TestCase;

final class RequestTokenCacheTest extends TestCase
{
    protected function setUp(): void
    {
        Request::clearTokenCache();
    }

    public function testCachedTokenWithMissingExpiryIsCleared(): void
    {
        Request::cacheToken('token_1', null);

        $this->assertNull(Request::getCachedToken());
    }

    public function testCachedTokenExpiresWithinThresholdIsCleared(): void
    {
        // threshold is 60s; give token 30 seconds remaining
        Request::cacheToken('token_2', date('c', time() + 30));

        $this->assertNull(Request::getCachedToken());
    }

    public function testCachedTokenBeyondThresholdIsReturned(): void
    {
        // threshold is 60s; give token 120 seconds remaining
        Request::cacheToken('token_3', date('c', time() + 120));

        $this->assertSame('token_3', Request::getCachedToken());
    }
}

