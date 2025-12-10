<?php
// Suppress deprecation warnings from vendor libraries (PHP 8.2+)
// This should be called before loading vendor/autoload.php
if (!defined('NIMBBL_ERROR_REPORTING_SET')) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    define('NIMBBL_ERROR_REPORTING_SET', true);
}
/**
 * Helper Functions for Nimbbl PHP SDK Examples
 */

/**
 * Load configuration from config.php
 * 
 * @return array Configuration array
 * @throws Exception If config.php doesn't exist
 */
function loadConfig()
{
    $configFile = __DIR__ . '/../config.php';
    
    if (!file_exists($configFile)) {
        throw new Exception(
            "Configuration file not found. Please copy config.php.example to config.php and update with your credentials."
        );
    }
    
    $config = require $configFile;
    
    // Validate required configuration
    $required = ['access_key', 'access_secret', 'api_endpoint'];
    foreach ($required as $key) {
        if (empty($config[$key]) || $config[$key] === "your_{$key}_here") {
            throw new Exception("Please configure '{$key}' in config.php");
        }
    }
    
    return $config;
}





/**
 * Initialize Nimbbl API instance with configuration
 *
 * @param array $config Configuration array from loadConfig()
 * @return NimbblApiApi Initialized API instance
 */
function initApi($config)
{
    return new NimbblApiApi(
        $config['access_key'],
        $config['access_secret'],
        $config['api_endpoint'],
        null,
        null,
        $config['log_file'] ?? null
    );
}
