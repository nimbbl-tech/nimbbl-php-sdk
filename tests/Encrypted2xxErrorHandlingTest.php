<?php

declare(strict_types=1);

namespace Nimbbl\Tests;

use Nimbbl\Api\Common\Encryption;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\Exception\BadRequestException;
use Nimbbl\Api\RestClient\Request;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class Encrypted2xxErrorHandlingTest extends TestCase
{
    public function testEncryptedErrorEnvelopeIn2xxThrowsAfterDecryption(): void
    {
        // Ensure NimbblClient static secret is set (Request decryption uses it statically).
        new NimbblClient('access_key_test', 'access_secret_test', 'https://example.com/api/v3');

        $request = new Request();

        $encryption = new Encryption('access_secret_test');

        $errorObj = [
            JsonKeys::ERROR_MERCHANT_MESSAGE => 'Bad request (encrypted)',
            JsonKeys::ERROR_CODE => 'E_ENC_1',
        ];

        $plaintextErrorEnvelope = json_encode(
            [JsonKeys::ERROR => $errorObj],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        $encryptedResponseValue = $encryption->encrypt($plaintextErrorEnvelope);

        $encryptedBody = json_encode(
            [JsonKeys::ENCRYPTED_RESPONSE => $encryptedResponseValue],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        // 1) Decrypt encrypted_response -> should yield a JSON error envelope string.
        $decryptMethod = new ReflectionMethod(Request::class, 'decryptEncryptedResponseBodyIfPresent');
        $decryptMethod->setAccessible(true);
        $decryptedBody = $decryptMethod->invoke($request, $encryptedBody);

        $this->assertIsString($decryptedBody);

        // 2) After decryption, error envelope should be detectable and throw.
        $throwMethod = new ReflectionMethod(Request::class, 'throwIfErrorEnvelope');
        $throwMethod->setAccessible(true);

        $this->expectException(BadRequestException::class);
        $throwMethod->invoke($request, $decryptedBody, null);
    }
}

