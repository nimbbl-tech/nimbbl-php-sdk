<?php

namespace Nimbbl\Tests;

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
        $this->assertEquals('J***', $maskedTrace[JsonKeys::FIRST_NAME]);
        $this->assertEquals('******3210', $maskedTrace[JsonKeys::MOBILE_NUMBER]);
        $this->assertEquals('jo******@example.com', $maskedTrace[JsonKeys::EMAIL]);
        $this->assertEquals('XXXX XXXX XXXX 1111', $maskedTrace[JsonKeys::CARD_NO]);
        $this->assertEquals('XXX', $maskedTrace[JsonKeys::CVV]);
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
        $this->assertEquals('M*********', $maskedTrace['user']['address'][JsonKeys::CITY]);
    }
}
