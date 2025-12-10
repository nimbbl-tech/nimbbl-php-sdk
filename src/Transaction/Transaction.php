<?php

namespace Nimbbl\Api;

use Exception;

#[AllowDynamicProperties]
class Transaction
{

    /**
     * Transaction enquiry
     * API: https://nimbbl.biz/docs/api-reference/transaction-enquiry-v-3/
     * @param array $attributes
     * @param string|null $token
     * @return array
     */
    public function transactionEnquiry($attributes = array(), $token = null)
    {
        $request = new Request();
        return $request->request(ApiConstants::HTTP_POST, ApiConstants::TRANSACTION_ENQUIRY, $attributes, $token, SdkConstants::COMPONENT_TRANSACTION);
    }
    
}
