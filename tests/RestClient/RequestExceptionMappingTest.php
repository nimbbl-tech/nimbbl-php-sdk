<?php

declare(strict_types=1);

namespace Nimbbl\Tests\RestClient;

use Nimbbl\Api\RestClient\Request;
use Nimbbl\Api\Exception\ApiException;
use Nimbbl\Api\Exception\AuthenticationException;
use Nimbbl\Api\Exception\BadRequestException;
use Nimbbl\Api\Exception\NotFoundException;
use Nimbbl\Api\Exception\RateLimitException;
use Nimbbl\Api\Exception\ServerException;
use PHPUnit\Framework\TestCase;

/**
 * Offline unit tests for the HTTP-status -> exception mapping in
 * Request::handleErrorResponse(). This is the real logic behind the thin
 * src/Exception/* classes; nothing else asserted it directly.
 *
 * The method is private, so it's invoked via reflection with a lightweight
 * response stub (only ->status_code is read).
 */
final class RequestExceptionMappingTest extends TestCase
{
    private function invokeHandle(int $statusCode): \ReflectionMethod
    {
        $m = new \ReflectionMethod(Request::class, 'handleErrorResponse');
        $m->setAccessible(true);
        return $m;
    }

    /**
     * @dataProvider statusToExceptionCases
     */
    public function testStatusMapsToExpectedException(int $statusCode, string $expectedClass): void
    {
        $request = new Request();
        $method = $this->invokeHandle($statusCode);
        $response = (object) ['status_code' => $statusCode];

        $this->expectException($expectedClass);
        $method->invoke($request, $response, 'req_1', 'ERR_CODE', 'boom', ['k' => 'v']);
    }

    public function statusToExceptionCases(): array
    {
        return [
            '401 Unauthorized'        => [401, AuthenticationException::class],
            '403 Forbidden -> generic'=> [403, ApiException::class],
            '400 Bad Request'         => [400, BadRequestException::class],
            '422 Unprocessable'       => [422, BadRequestException::class],
            '404 Not Found'           => [404, NotFoundException::class],
            '429 Too Many Requests'   => [429, RateLimitException::class],
            '500 Internal Error'      => [500, ServerException::class],
            '502 Bad Gateway'         => [502, ServerException::class],
            '503 Unavailable'         => [503, ServerException::class],
            '504 Gateway Timeout'     => [504, ServerException::class],
            '418 unmapped -> generic' => [418, ApiException::class],
        ];
    }

    /**
     * A non-server status propagates the caller-supplied message/code/requestId/data.
     * (Server 5xx deliberately overrides errorCode to SERVER_ERROR, so 400 is used here.)
     */
    public function testErrorDetailsArePropagated(): void
    {
        $request = new Request();
        $method = $this->invokeHandle(400);
        $response = (object) ['status_code' => 400];

        try {
            $method->invoke($request, $response, 'req_1', 'ERR_CODE', 'boom', ['k' => 'v']);
            $this->fail('Expected BadRequestException was not thrown.');
        } catch (BadRequestException $e) {
            $this->assertSame('boom', $e->getMessage());
            $this->assertSame(400, $e->getHttpStatusCode());
            $this->assertSame('ERR_CODE', $e->getErrorCode());
            $this->assertSame('req_1', $e->getRequestId());
            $this->assertSame(['k' => 'v'], $e->getErrorData());
        }
    }
}
