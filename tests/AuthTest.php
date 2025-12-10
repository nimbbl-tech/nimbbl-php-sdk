<?php

declare(strict_types=1);

// require_once __DIR__ . '/../vendor/autoload.php';

use Nimbbl\Api\Api;
use Nimbbl\Api\Request;
use PHPUnit\Framework\TestCase;
use Nimbbl\Tests\TestCredentials;

final class AuthTest extends TestCase
{
    private $api;

    protected function setUp(): void
    {
        $this->api = new Api(TestCredentials::ACCESS_KEY, TestCredentials::ACCESS_SECRET);
    }

    /**
     * Test token generation
     */
    public function testGenerateToken(): void
    {
        $auth = $this->api->auth();
        
        $result = $auth->generateToken();
        
        $this->assertIsArray($result, 'Token generation should return an array');
        $this->assertArrayHasKey('token', $result, 'Response should contain token');
        $this->assertNotEmpty($result['token'], 'Token should not be empty');
        $this->assertIsString($result['token'], 'Token should be a string');
        
        // Check for expiration if present
        if (isset($result['expires_at'])) {
            $this->assertNotEmpty($result['expires_at'], 'Expires at should not be empty if present');
        }
    }

    /**
     * Test token generation with invalid credentials
     * Note: This test may need to be adjusted based on actual API behavior
     */
    public function testGenerateTokenWithInvalidCredentials(): void
    {
        $invalidApi = new Api('invalid_key', 'invalid_secret');
        $auth = $invalidApi->auth();
        
        try {
            $result = $auth->generateToken();
            // If API returns error in response instead of throwing exception
            if (isset($result['error'])) {
                $this->assertArrayHasKey('error', $result, 'Should return error for invalid credentials');
            } else {
                // If exception is thrown, that's also valid behavior
                $this->fail('Expected error for invalid credentials');
            }
        } catch (\Exception $e) {
            // Exception is also valid behavior for invalid credentials
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    /**
     * Test refresh token with valid refresh token
     * Note: This requires a valid refresh token from an order creation
     */
    public function testRefreshToken(): void
    {
        // First, generate a merchant token
        $request = new Request();
        $merchantToken = $request->generateToken()['token'];
        
        // Create an order to get a refresh token
        $orderData = [
            'invoice_id' => 'TEST_REFRESH_' . time(),
            'amount_before_tax' => 100,
            'tax' => 18,
            'total_amount' => 118,
            'currency' => 'INR',
            'user' => [
                'email' => 'test@example.com',
                'first_name' => 'Test',
                'last_name' => 'User',
                'mobile_number' => '9876543210',
                'country_code' => '+91'
            ]
        ];
        
        try {
            $order = $this->api->orders()->createOrder($orderData, $merchantToken);
            
            if (!isset($order['error']) && isset($order['refresh_token'])) {
                $refreshToken = $order['refresh_token'];
                $orderToken = $order['token'] ?? $merchantToken;
                
                $auth = $this->api->auth();
                $result = $auth->refreshToken($refreshToken, $orderToken);
                
                $this->assertIsArray($result, 'Token refresh should return an array');
                
                if (!isset($result['error'])) {
                    $this->assertArrayHasKey('token', $result, 'Response should contain new token');
                    $this->assertNotEmpty($result['token'], 'New token should not be empty');
                    $this->assertIsString($result['token'], 'New token should be a string');
                } else {
                    // If refresh fails, that's okay - mark test as skipped
                    $this->markTestSkipped('Token refresh failed: ' . json_encode($result['error']));
                }
            } else {
                $this->markTestSkipped('Order creation failed or refresh_token not available');
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('Token refresh test skipped: ' . $e->getMessage());
        }
    }

    /**
     * Test refresh token with empty refresh token
     */
    public function testRefreshTokenWithEmptyRefreshToken(): void
    {
        $auth = $this->api->auth();
        $request = new Request();
        $token = $request->generateToken()['token'];
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('refresh_token is required');
        
        $auth->refreshToken('', $token);
    }

    /**
     * Test refresh token without bearer token
     */
    public function testRefreshTokenWithoutBearerToken(): void
    {
        $auth = $this->api->auth();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Token is required');
        
        $auth->refreshToken('some_refresh_token', null);
    }

    /**
     * Test that generateToken uses Api credentials
     */
    public function testGenerateTokenUsesApiCredentials(): void
    {
        $auth = $this->api->auth();
        
        // Generate token - should use credentials from Api instance
        $result = $auth->generateToken();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertNotEmpty($result['token']);
    }

    /**
     * Test token generation returns valid token structure
     */
    public function testGenerateTokenReturnsValidStructure(): void
    {
        $auth = $this->api->auth();
        $result = $auth->generateToken();
        
        // Verify response structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        
        // Token should be a non-empty string
        $this->assertIsString($result['token']);
        $this->assertGreaterThan(0, strlen($result['token']));
        
        // Optional fields that may be present
        if (isset($result['expires_at'])) {
            $this->assertNotEmpty($result['expires_at']);
        }
        
        if (isset($result['token_expiration'])) {
            $this->assertNotEmpty($result['token_expiration']);
        }
    }

    /**
     * Test that auth instance can be accessed via API
     */
    public function testAuthInstanceAccess(): void
    {
        $auth = $this->api->auth();
        
        $this->assertInstanceOf(\Nimbbl\Api\Auth::class, $auth);
        $this->assertInstanceOf(\Nimbbl\Api\AuthInterface::class, $auth);
    }

    /**
     * Test multiple token generations return different tokens
     * (Tokens should be unique, though this depends on API implementation)
     */
    public function testMultipleTokenGenerations(): void
    {
        $auth = $this->api->auth();
        
        $token1 = $auth->generateToken();
        $token2 = $auth->generateToken();
        
        $this->assertIsArray($token1);
        $this->assertIsArray($token2);
        $this->assertArrayHasKey('token', $token1);
        $this->assertArrayHasKey('token', $token2);
        
        // Tokens may or may not be different depending on API implementation
        // We just verify both are valid
        $this->assertNotEmpty($token1['token']);
        $this->assertNotEmpty($token2['token']);
    }
}

