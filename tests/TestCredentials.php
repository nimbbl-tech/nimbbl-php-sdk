<?php
/**
 * Test Credentials - API Keys
 * 
 * Contains API credentials used in tests
 * 
 * NOTE: These are test credentials. For production, use environment variables
 * or a secure configuration file that is not committed to version control.
 */

namespace Nimbbl\Tests;

class TestCredentials
{
    // Default test credentials (most commonly used)
    const ACCESS_KEY = 'access_key_1MwvMkKkweorz0ry';
    const ACCESS_SECRET = 'access_secret_81x7ByYkRpB4g05N';
    
    // Alternative test credentials (for specific test scenarios)
    const ACCESS_KEY_ALT = 'access_key_rQv9VOyaVwkrD3zg';
    const ACCESS_SECRET_ALT = 'access_secret_NYP0EYDMe9p1Z0GD';
    
    // API Base URL
    const API_BASE_URL = 'https://apipp.nimbbl.tech/api/';
    
    // API Version
    const API_VERSION = 'v3';
}

