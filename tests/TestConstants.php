<?php
/**
 * Test Constants - API Endpoints
 * 
 * Contains all official Nimbbl API v3 endpoint URLs used in tests
 */

namespace Nimbbl\Tests;

class TestConstants
{
    // Orders API
    const ORDER_CREATE = 'POST /api/v3/create-order';
    const ORDER_GET = 'GET /api/v3/order';
    
    // Addresses API
    const ADDRESS_LIST = 'GET /api/v3/addresses';
    const ADDRESS_CREATE = 'POST /api/v3/addresses';
    const ADDRESS_GET = 'GET /api/v3/addresses';
    const ADDRESS_UPDATE = 'PATCH /api/v3/addresses';
    const ADDRESS_DELETE = 'DELETE /api/v3/addresses';
    const ADDRESS_IMPORT = 'POST /api/v3/addresses/import';
    const ADDRESS_CHECK_ELIGIBILITY = 'GET /api/v3/addresses/eligibility';
    const ADDRESS_LINK_ORDER = 'POST /api/v3/addresses/link';
    
    // Payments API
    const PAYMENT_INITIATE = 'POST /api/v3/initiate-payment';
    const PAYMENT_COMPLETE = 'POST /api/v3/payment';
    const PAYMENT_RESEND_OTP = 'POST /api/v3/resend-otp';
    
    // Payment Links API
    const PAYMENT_LINK_CREATE = 'POST /api/v3/payment-link';
    const PAYMENT_LINK_ENQUIRY = 'POST /api/v3/payment-link/enquiry';
    const PAYMENT_LINK_UPDATE = 'PATCH /api/v3/payment-link';
    const PAYMENT_LINK_ACTIONS = 'POST /api/v3/payment-link/actions';
    
    // Checkout Utilities API
    const CHECKOUT_PAYMENT_MODES = 'POST /api/v3/payment-modes';
    const CHECKOUT_LIST_BANKS = 'POST /api/v3/list-of-banks';
    const CHECKOUT_LIST_WALLETS = 'POST /api/v3/list-of-wallets';
    const CHECKOUT_LIST_EMIS = 'POST /api/v3/emis';
    const CHECKOUT_OFFERS = 'POST /api/v3/offers';
    const CHECKOUT_GET_BIN_DATA = 'POST /api/v3/get-bin-data';
    const CHECKOUT_VALIDATE_VPA = 'POST /api/v3/validate-vpa';
    const CHECKOUT_GET_UPI_APP_DETAILS = 'POST /api/v3/get-upi-app-details';
    
    // Transaction Status API
    const TRANSACTION_ENQUIRY = 'POST /api/v3/transaction-enquiry';
    // Note: TRANSACTION_CANCEL is NOT an official public API - it's an internal API
    // const TRANSACTION_CANCEL = 'POST /api/internal/checkout/cancel';
    
    // Refunds API
    const REFUND_INITIATE = 'POST /api/v3/refund';
    
    // Authorization API
    const AUTH_GENERATE_TOKEN = 'POST /api/v3/generate-token';
    const AUTH_REFRESH_TOKEN = 'POST /api/v3/refresh-token';
    
    // Helper method to build URL with ID
    public static function buildUrl($baseUrl, $id = null)
    {
        if ($id === null) {
            return $baseUrl;
        }
        
        // Extract method and path from base URL
        if (preg_match('/^(GET|POST|PATCH|PUT|DELETE)\s+(.+)$/', $baseUrl, $matches)) {
            $method = $matches[1];
            $path = $matches[2];
            
            // Replace {id} placeholder or append ID
            if (strpos($path, '{id}') !== false) {
                $path = str_replace('{id}', $id, $path);
            } else {
                $path = rtrim($path, '/') . '/' . $id;
            }
            
            return $method . ' ' . $path;
        }
        
        return $baseUrl;
    }
}

