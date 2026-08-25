<?php

declare(strict_types=1);

namespace Nimbbl\Tests\RestClient;

use Nimbbl\Api\RestClient\NimbblClient;
use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\Common\SdkConstants;
use PHPUnit\Framework\TestCase;

/**
 * Offline unit tests for NimbblClient — no network, no credentials.
 *
 * Covers the pure, deterministic logic that nothing else exercised:
 *  - URL resolution: getFullUrl() / getTokenEndpoint() for the default base URL,
 *    the combined "host/api/v3" endpoint (what loadConfig() builds), and a custom
 *    host+path base — guarding against the double-version bug (…/v3/v3/…).
 *  - The static-config conflict guard (a second client with different credentials
 *    must throw, identical config must not).
 *  - The encrypt-payload flag and the VERSION constant.
 *
 * NimbblClient keeps configuration in static properties, so each test resets that
 * state via reflection in setUp()/tearDown() to stay isolated from the rest of the suite.
 */
final class NimbblClientTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetClientState();
    }

    protected function tearDown(): void
    {
        // Leave global state pristine so other suites that construct a client aren't
        // tripped by the config-conflict guard.
        $this->resetClientState();
    }

    private function setStatic(string $prop, $value): void
    {
        $rp = new \ReflectionProperty(NimbblClient::class, $prop);
        $rp->setAccessible(true);
        $rp->setValue(null, $value);
    }

    private function resetClientState(): void
    {
        $this->setStatic('key', null);
        $this->setStatic('secret', null);
        $this->setStatic('merchantId', null);
        $this->setStatic('logFile', null);
        $this->setStatic('baseUrl', ApiConstants::BASE_URL);
        $this->setStatic('apiVersion', ApiConstants::API_VERSION);
        $this->setStatic('encryptPayload', false);
        $this->setStatic('overrideLogFilename', false);
        $this->setStatic('initLogEmitted', false);
    }

    // ---- URL resolution ----------------------------------------------------

    public function testDefaultBaseUrlResolvesToApiV3(): void
    {
        $this->assertSame(ApiConstants::BASE_URL, NimbblClient::getBaseUrl());
        $this->assertSame('v3', NimbblClient::getAPIVersion());

        $this->assertSame(
            'https://api.nimbbl.tech/api/v3/create-order',
            NimbblClient::getFullUrl(ApiConstants::ORDER_CREATE)
        );
        $this->assertSame(
            'https://api.nimbbl.tech/api/v3/capture',
            NimbblClient::getFullUrl(ApiConstants::CAPTURE)
        );
        $this->assertSame(
            'https://api.nimbbl.tech/api/v3/generate-token',
            NimbblClient::getTokenEndpoint()
        );
    }

    /**
     * The combined "…/api/v3" endpoint is exactly what loadConfig() builds
     * (rtrim(api_host) . '/api/v3'). getFullUrl() must NOT double the version.
     */
    public function testCombinedApiV3EndpointDoesNotDoubleVersion(): void
    {
        new NimbblClient('k', 's', 'https://apipp.nimbbl.tech/api/v3');

        $this->assertSame('https://apipp.nimbbl.tech/api/v3', NimbblClient::getBaseUrl());
        $this->assertSame('', NimbblClient::getAPIVersion());

        // Relative URLs from ApiConstants carry a leading "v3/" — it must be stripped, not appended.
        $this->assertSame(
            'https://apipp.nimbbl.tech/api/v3/create-order',
            NimbblClient::getFullUrl(ApiConstants::ORDER_CREATE)
        );
        $this->assertSame(
            'https://apipp.nimbbl.tech/api/v3/void',
            NimbblClient::getFullUrl(ApiConstants::VOID)
        );
        $this->assertSame(
            'https://apipp.nimbbl.tech/api/v3/generate-token',
            NimbblClient::getTokenEndpoint()
        );
    }

    public function testCombinedEndpointTrailingSlashIsNormalized(): void
    {
        new NimbblClient('k', 's', 'https://apipp.nimbbl.tech/api/v3/');

        $this->assertSame('https://apipp.nimbbl.tech/api/v3', NimbblClient::getBaseUrl());
        $this->assertSame(
            'https://apipp.nimbbl.tech/api/v3/refund',
            NimbblClient::getFullUrl(ApiConstants::REFUND_INITIATE)
        );
    }

    /**
     * A custom base that does not end in /vN keeps the default version and appends it.
     */
    public function testCustomHostWithoutVersionAppendsVersion(): void
    {
        new NimbblClient('k', 's', 'https://gateway.example.com/pay');

        $this->assertSame('https://gateway.example.com/pay', NimbblClient::getBaseUrl());
        $this->assertSame('v3', NimbblClient::getAPIVersion());
        $this->assertSame(
            'https://gateway.example.com/pay/v3/create-order',
            NimbblClient::getFullUrl(ApiConstants::ORDER_CREATE)
        );
        $this->assertSame(
            'https://gateway.example.com/pay/v3/generate-token',
            NimbblClient::getTokenEndpoint()
        );
    }

    // ---- Static-config conflict guard -------------------------------------

    public function testSecondClientWithDifferentCredentialsThrows(): void
    {
        new NimbblClient('key_1', 'secret_1');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('NimbblClient configuration is global/static');
        new NimbblClient('key_2', 'secret_2');
    }

    public function testSecondClientWithDifferentBaseUrlThrows(): void
    {
        new NimbblClient('key_1', 'secret_1', 'https://api.nimbbl.tech/api/v3');

        $this->expectException(\RuntimeException::class);
        new NimbblClient('key_1', 'secret_1', 'https://apipp.nimbbl.tech/api/v3');
    }

    public function testIdenticalConfigDoesNotThrow(): void
    {
        new NimbblClient('key_1', 'secret_1');
        new NimbblClient('key_1', 'secret_1');

        $this->assertSame('key_1', NimbblClient::getKey());
        $this->assertSame('secret_1', NimbblClient::getSecret());
    }

    // ---- Flags / constants -------------------------------------------------

    public function testEncryptPayloadFlagReflectsConstructor(): void
    {
        $this->assertFalse(NimbblClient::isEncryptPayloadEnabled());

        new NimbblClient('k', 's', null, null, true);
        $this->assertTrue(NimbblClient::isEncryptPayloadEnabled());
    }

    public function testVersionConstantMatchesSdkVersion(): void
    {
        $this->assertSame(SdkConstants::SDK_VERSION, NimbblClient::VERSION);
    }
}
