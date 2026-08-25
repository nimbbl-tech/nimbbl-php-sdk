<?php

declare(strict_types=1);

namespace Nimbbl\Tests\Common;

use Nimbbl\Api\Common\EncryptedPayloadHelper;
use Nimbbl\Api\Common\Encryption;
use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\ApiConstants;
use Nimbbl\Api\RestClient\NimbblClient;
use PHPUnit\Framework\TestCase;

/**
 * Offline unit tests for EncryptedPayloadHelper — the on/off contract that
 * capture/void/order/refund/transaction all depend on.
 *
 *  - Encryption disabled (default): payload is returned unchanged.
 *  - Encryption enabled: payload is replaced by { encrypted_payload: <hex> } and
 *    round-trips back to the original via Encryption::decrypt().
 *
 * NimbblClient config is static, so state is reset via reflection around each test.
 */
final class EncryptedPayloadHelperTest extends TestCase
{
    private const SECRET = 'access_secret_unit_test_key_0123456789';

    protected function setUp(): void
    {
        $this->resetClientState();
    }

    protected function tearDown(): void
    {
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
        $this->setStatic('baseUrl', ApiConstants::BASE_URL);
        $this->setStatic('apiVersion', ApiConstants::API_VERSION);
        $this->setStatic('encryptPayload', false);
        $this->setStatic('initLogEmitted', false);
    }

    public function testReturnsAttributesUnchangedWhenEncryptionDisabled(): void
    {
        $this->assertFalse(NimbblClient::isEncryptPayloadEnabled());

        $in = ['transaction_id' => 'o_abc-123', 'comment' => 'Goods dispatched'];
        $out = EncryptedPayloadHelper::preparePayload($in, 'capture');

        $this->assertSame($in, $out, 'Payload must pass through untouched when encryption is off.');
    }

    public function testReturnsEncryptedEnvelopeWhenEnabledAndRoundTrips(): void
    {
        new NimbblClient('access_key_x', self::SECRET, null, null, true);
        $this->assertTrue(NimbblClient::isEncryptPayloadEnabled());

        $in = ['transaction_id' => 'o_abc-123', 'comment' => 'Goods dispatched'];
        $out = EncryptedPayloadHelper::preparePayload($in, 'capture');

        // Only the encrypted envelope survives — the plaintext fields are gone.
        $this->assertArrayHasKey(JsonKeys::ENCRYPTED_PAYLOAD, $out);
        $this->assertArrayNotHasKey('transaction_id', $out);
        $this->assertArrayNotHasKey('comment', $out);
        $this->assertCount(1, $out);

        $cipherHex = $out[JsonKeys::ENCRYPTED_PAYLOAD];
        $this->assertIsString($cipherHex);
        $this->assertTrue(ctype_xdigit($cipherHex), 'encrypted_payload must be a hex string.');

        // It must decrypt back to exactly the original attributes.
        $decrypted = (new Encryption(self::SECRET))->decrypt($cipherHex, true);
        $this->assertSame($in, $decrypted);
    }
}
