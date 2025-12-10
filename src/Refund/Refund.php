<?php

namespace Nimbbl\Api;

use Exception;

#[AllowDynamicProperties]
class Refund
{

    /**
     * Initiate refund
     * API: https://nimbbl.biz/docs/api-reference/refund-a-payment-v-3/
     * @param array $attributes
     * @param string|null $token
     * @return array
     */
    public function initiateRefund($attributes = array(), $token = null)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::REFUND_INITIATE, $attributes, $token, SdkConstants::COMPONENT_REFUND);
    }

}
