<?php
/**
 * Utility class for loading environment variables from .env files
 */

class EnvLoader
{
    private static $isLoaded = false;
    private static $lock = null;

    /**
     * Loads environment variables from a .env file if it exists.
     * Looks for .env in the current directory and parent directories up to 5 levels.
     * Only sets variables that are not already set in the environment.
     * This method is idempotent - it will only load the .env file once, even if called multiple times.
     */
    public static function loadEnvFile()
    {
        // Early return if already loaded (double-checked locking pattern)
        if (self::$isLoaded) {
            return;
        }

        if (self::$lock === null) {
            self::$lock = new stdClass();
        }

        // Simple locking using a static flag (PHP doesn't have true threading locks)
        // In practice, this is safe for CLI scripts
        if (self::$isLoaded) {
            return;
        }

        // Try to find .env file starting from current directory and going up
        $currentDir = getcwd();
        $envFile = self::findEnvFile($currentDir);
        
        if ($envFile !== null && file_exists($envFile)) {
            self::loadEnvFileFromPath($envFile);
        }

        // Mark as loaded after attempting to load (even if file not found)
        self::$isLoaded = true;
    }

    /**
     * Loads environment variables from a specific .env file path
     * 
     * @param string $envFilePath Path to the .env file
     */
    public static function loadEnvFileFromPath($envFilePath)
    {
        if (!file_exists($envFilePath)) {
            return;
        }

        $lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments and empty lines
            $trimmed = trim($line);
            if (empty($trimmed) || strpos($trimmed, '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE format
            $equalIndex = strpos($trimmed, '=');
            if ($equalIndex > 0) {
                $key = trim(substr($trimmed, 0, $equalIndex));
                $value = trim(substr($trimmed, $equalIndex + 1));
                
                // Remove quotes if present
                if ((strpos($value, '"') === 0 && substr($value, -1) === '"') || 
                    (strpos($value, "'") === 0 && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                // Only set if not already set as environment variable
                if (empty(getenv($key))) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                }
            }
        }
    }

    /**
     * Finds .env file by searching current directory and parent directories (up to 5 levels)
     * 
     * @param string $startDirectory Directory to start searching from
     * @return string|null Path to .env file if found, null otherwise
     */
    private static function findEnvFile($startDirectory)
    {
        $currentDir = $startDirectory;
        $maxLevels = 5;
        $level = 0;

        while ($level < $maxLevels && $currentDir !== false && $currentDir !== '/') {
            $envFile = $currentDir . DIRECTORY_SEPARATOR . '.env';
            if (file_exists($envFile)) {
                return $envFile;
            }
            
            $parentDir = dirname($currentDir);
            if ($parentDir === $currentDir) {
                break; // Reached root
            }
            $currentDir = $parentDir;
            $level++;
        }

        return null;
    }
}
