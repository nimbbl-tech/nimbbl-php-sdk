<?php

declare(strict_types=1);

namespace Nimbbl\Tests\Services;

use Nimbbl\Api\Common\JsonKeys;
use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\Services\Order;
use PHPUnit\Framework\TestCase;

/**
 * Offline unit tests for the order_source pair stamped on create-order.
 *
 * The Magento, WooCommerce and OpenCart plugins each bundle a copy of this SDK, so
 * order_source must stay the CALLER's value when one is supplied — otherwise every PHP
 * integration reports the same label and the storefront attribution is lost.
 * order_source_version stays SDK-controlled so a caller cannot misreport the SDK build.
 */
final class OrderSourceTest extends TestCase
{
    private function stamp(array $attributes): array
    {
        $m = new \ReflectionMethod(Order::class, 'applyOrderSource');
        $m->setAccessible(true);
        return $m->invoke(new Order(), $attributes);
    }

    /**
     * @dataProvider callerSuppliedSources
     */
    public function testCallerSuppliedOrderSourceIsPreserved(string $source): void
    {
        $result = $this->stamp([JsonKeys::ORDER_SOURCE => $source, 'invoice_id' => 'inv_1']);

        $this->assertSame($source, $result[JsonKeys::ORDER_SOURCE]);
    }

    public function callerSuppliedSources(): array
    {
        return [
            'magento' => ['magento'],
            'woocommerce' => ['woocommerce'],
            'opencart' => ['opencart'],
            'custom integration' => ['acme-storefront'],
        ];
    }

    /**
     * @dataProvider unusableSources
     */
    public function testUnusableOrderSourceFallsBackToSdkDefault(array $attributes): void
    {
        $result = $this->stamp($attributes);

        $this->assertSame(SdkConstants::ORDER_SOURCE, $result[JsonKeys::ORDER_SOURCE]);
    }

    public function unusableSources(): array
    {
        return [
            'absent' => [[]],
            'empty string' => [[JsonKeys::ORDER_SOURCE => '']],
            'whitespace only' => [[JsonKeys::ORDER_SOURCE => '   ']],
            'null' => [[JsonKeys::ORDER_SOURCE => null]],
            'array' => [[JsonKeys::ORDER_SOURCE => ['magento']]],
            'int' => [[JsonKeys::ORDER_SOURCE => 42]],
        ];
    }

    public function testOrderSourceVersionIsAlwaysSdkControlled(): void
    {
        $spoofed = $this->stamp([
            JsonKeys::ORDER_SOURCE => 'magento',
            JsonKeys::ORDER_SOURCE_VERSION => '0.0.1-spoofed',
        ]);

        $this->assertSame(SdkConstants::SDK_VERSION, $spoofed[JsonKeys::ORDER_SOURCE_VERSION]);
    }

    public function testOtherAttributesAreLeftUntouched(): void
    {
        $result = $this->stamp([
            'invoice_id' => 'inv_1',
            'total_amount' => 500,
            'order_line_items' => [['name' => 'widget']],
        ]);

        $this->assertSame('inv_1', $result['invoice_id']);
        $this->assertSame(500, $result['total_amount']);
        $this->assertSame([['name' => 'widget']], $result['order_line_items']);
    }

    public function testCallerArrayIsNotMutated(): void
    {
        $attributes = ['invoice_id' => 'inv_1'];
        $this->stamp($attributes);

        $this->assertArrayNotHasKey(JsonKeys::ORDER_SOURCE, $attributes);
        $this->assertArrayNotHasKey(JsonKeys::ORDER_SOURCE_VERSION, $attributes);
    }
}
