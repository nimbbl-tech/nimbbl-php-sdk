<?php

namespace Nimbbl\Tests\RestClient;

use Nimbbl\Api\RestClient\Request;
use PHPUnit\Framework\TestCase;

final class RequestLogContextSanitizationTest extends TestCase
{
    public function testExtractLogContextSanitizesJwtAndFallsBackToRequestBody()
    {
        $request = new Request();
        $method = new \ReflectionMethod(Request::class, 'extractLogContextFromJwtAndRequest');
        $method->setAccessible(true);

        $jwt = $this->buildJwt([
            'sub_merchant_id' => '1001241',
            'order_id' => "bad order id with spaces\n",
        ]);

        $ctx = $method->invoke($request, $jwt, [], json_encode(['order_id' => 'o_valid_123']));

        $this->assertSame('1001241', $ctx['sub_merchant_id'] ?? null);
        $this->assertSame('o_valid_123', $ctx['order_id'] ?? null);
    }

    public function testExtractLogContextRejectsInvalidSubMerchantId()
    {
        $request = new Request();
        $method = new \ReflectionMethod(Request::class, 'extractLogContextFromJwtAndRequest');
        $method->setAccessible(true);

        $jwt = $this->buildJwt([
            'sub_merchant_id' => 'merchant-abc',
            'order_id' => 'o_valid_456',
        ]);

        $ctx = $method->invoke($request, $jwt, [], null);

        $this->assertArrayNotHasKey('sub_merchant_id', $ctx);
        $this->assertSame('o_valid_456', $ctx['order_id'] ?? null);
    }

    public function testMergeContextWithResponseBodySanitizesOrderId()
    {
        $request = new Request();
        $method = new \ReflectionMethod(Request::class, 'mergeContextWithResponseBody');
        $method->setAccessible(true);

        $ctx = $method->invoke($request, [], json_encode(['order_id' => "o_bad value"]));
        $this->assertArrayNotHasKey('order_id', $ctx);

        $ctx = $method->invoke($request, [], json_encode(['order_id' => 'o_good_value-1']));
        $this->assertSame('o_good_value-1', $ctx['order_id'] ?? null);
    }

    private function buildJwt(array $payload): string
    {
        $header = ['alg' => 'none', 'typ' => 'JWT'];
        return $this->base64UrlEncode((string) json_encode($header))
            . '.'
            . $this->base64UrlEncode((string) json_encode($payload))
            . '.';
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
