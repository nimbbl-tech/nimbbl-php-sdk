<?php

namespace Nimbbl\Tests\Common;

use Nimbbl\Api\Common\CentralMasker;
use Nimbbl\Api\Common\JsonKeys;
use PHPUnit\Framework\TestCase;

final class CentralMaskerTest extends TestCase
{
    public function testMaskHeaders()
    {
        $headers = [
            'Authorization' => 'Bearer secret_token_12345',
            'Content-Type' => 'application/json',
            'X-Custom' => 'public_value'
        ];

        $masked = CentralMasker::maskHeaders($headers);

        $this->assertEquals('Bear***2345', $masked['Authorization']);
        $this->assertEquals('application/json', $masked['Content-Type']);
        $this->assertEquals('public_value', $masked['X-Custom']);
    }

    public function testMaskBodyJson()
    {
        $data = [
            JsonKeys::ACCESS_KEY => 'access_key_1234567890',
            JsonKeys::TOKEN => 'eyJhbGabcdefghijklmng59w',
            JsonKeys::FIRST_NAME => 'John',
            JsonKeys::MOBILE_NUMBER => '9876543210',
            JsonKeys::EMAIL => 'john.doe@example.com',
            JsonKeys::CARD_NO => '4111111111111111',
            JsonKeys::CVV => '123',
            'public_field' => 'visible'
        ];

        $json = json_encode($data);
        $maskedJson = CentralMasker::maskBody($json);
        $maskedTrace = json_decode($maskedJson, true);

        $this->assertEquals('acce****7890', $maskedTrace[JsonKeys::ACCESS_KEY]);
        $this->assertEquals('eyJhb***********lmng59w', $maskedTrace[JsonKeys::TOKEN]);
        $this->assertEquals('J***', $maskedTrace[JsonKeys::FIRST_NAME]);
        $this->assertEquals('******3210', $maskedTrace[JsonKeys::MOBILE_NUMBER]);
        $this->assertEquals('jo****oe@example.com', $maskedTrace[JsonKeys::EMAIL]);
        $this->assertEquals('**** **** **** 1111', $maskedTrace[JsonKeys::CARD_NO]);
        $this->assertEquals('***', $maskedTrace[JsonKeys::CVV]);
        $this->assertEquals('visible', $maskedTrace['public_field']);
    }

    public function testMaskBodyNestedJson()
    {
        $data = [
            'user' => [
                JsonKeys::FIRST_NAME => 'Alice',
                'address' => [
                    JsonKeys::STREET => '123 Main St',
                    JsonKeys::CITY => 'Metropolis'
                ]
            ]
        ];

        $json = json_encode($data);
        $maskedJson = CentralMasker::maskBody($json);
        $maskedTrace = json_decode($maskedJson, true);

        $this->assertEquals('A****', $maskedTrace['user'][JsonKeys::FIRST_NAME]);
        $this->assertEquals('1** M*** S*', $maskedTrace['user']['address'][JsonKeys::STREET]);
        $this->assertEquals('Me********', $maskedTrace['user']['address'][JsonKeys::CITY]);
    }

    /**
     * Webhook/callback RESPONSE payloads use short PII field names (name/mobile/card_holder),
     * which must be masked just like the request-side names.
     */
    public function testMaskBodyResponseSidePiiKeys()
    {
        $data = [
            'user' => [
                'name' => 'John Doe',
                'mobile' => '9876543210',
                'email' => 'customer@example.com',
            ],
            'transaction' => [
                'sub_payment_mode' => ['card_holder' => 'sandeepkumar'],
                'state' => 'Maharashtra',
            ],
        ];

        $masked = json_decode(CentralMasker::maskBody(json_encode($data)), true);

        $this->assertNotEquals('John Doe', $masked['user']['name']);
        $this->assertNotEquals('9876543210', $masked['user']['mobile']);
        $this->assertNotEquals('customer@example.com', $masked['user']['email']);
        $this->assertNotEquals('sandeepkumar', $masked['transaction']['sub_payment_mode']['card_holder']);
        $this->assertNotEquals('Maharashtra', $masked['transaction']['state']);
        // sanity: still valid JSON structure and phone keeps last 4
        $this->assertStringEndsWith('3210', $masked['user']['mobile']);
    }
}
