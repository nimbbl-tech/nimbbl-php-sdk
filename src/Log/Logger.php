<?php

namespace Nimbbl\Api;

/**
 * Logger with simple formatted output
 * 
 * Features:
 * - Legacy log() method
 * - Convenience level methods (info, debug, error, warning, critical, exception)
 * - Writes to log file, error_log (non-CLI), and stdout (CLI) in a uniform format
 */
class Logger
{
    private static $instance = null;
    private $logFile;
    private $logDir;

    // Log format: [timestamp][sdk sdkVersion][level][module:line][function]: message
    private const LOGGER_FORMAT = '[%s][%s %s][%s][%s:%d][%s]: %s';
    private const LOGGER_DATEFMT = 'Y-m-d H:i:s';

    private function __construct($logFile = null)
    {
        if ($logFile === null) {
            // Check if Api class has a log file configured
            if (class_exists('Nimbbl\Api\Api') && Api::getLogFile() !== null) {
                $logFile = Api::getLogFile();
            } else {
                // Calculate path relative to src/Log/ directory: go up two levels to project root, then logs/
                $logFile = dirname(__FILE__) . '/../../logs/nimbbl_debug.log';
            }
        }
        $this->logFile = $logFile;
        $this->logDir = dirname($logFile);
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public static function getInstance($logFile = null)
    {
        // If logFile is provided and instance exists with different path, reset instance
        if (self::$instance !== null && $logFile !== null && self::$instance->logFile !== $logFile) {
            self::$instance = null;
        }
        
        if (self::$instance === null) {
            self::$instance = new self($logFile);
        }
        return self::$instance;
    }

    /**
     * Legacy log method
     * 
     * @param string $message Log message
     * @param string $level Log level
     * @param string $component Component name
     * @return void
     */
    public function log($message, $level = 'INFO', $component = 'NimbblSDK')
    {
        $timestamp = date(self::LOGGER_DATEFMT);
        $logMessage = sprintf(
            self::LOGGER_FORMAT,
            $timestamp,
            SdkConstants::SDK_NAME,
            SdkConstants::SDK_VERSION,
            strtoupper($level),
            $component,
            0,
            '-',
            $message
        ) . PHP_EOL;

        $this->writeLog($logMessage, $level, $message);
    }

    /**
     * Build log line with caller info
     */
    private function logWithCaller($level, $message, $exception = null)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $backtrace[1] ?? [];
        $module = isset($caller['file']) ? basename($caller['file']) : 'unknown';
        $line = $caller['line'] ?? 0;
        $function = $caller['function'] ?? '-';

        $timestamp = date(self::LOGGER_DATEFMT);
        $payload = $message;
        if ($exception instanceof \Exception) {
            $payload .= ' Exception: ' . $exception->getMessage() . "\nTrace: " . $exception->getTraceAsString();
        }

        $logMessage = sprintf(
            self::LOGGER_FORMAT,
            $timestamp,
            SdkConstants::SDK_NAME,
            SdkConstants::SDK_VERSION,
            strtoupper($level),
            $module,
            $line,
            $function,
            $payload
        ) . PHP_EOL;

        $this->writeLog($logMessage, $level, $payload);
    }

    /**
     * Write log to file and output
     * 
     * @param string $logMessage Formatted log message
     * @param string $level Log level
     * @param string $originalMessage Original message for color detection
     * @return void
     */
    private function writeLog($logMessage, $level, $originalMessage)
    {
        // Write to error log (for web server environments) - only if not CLI
        if (php_sapi_name() !== 'cli') {
            error_log($logMessage);
        }

        // Write to custom log file - always plain text
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);

        // Print to stdout if CLI - plain text (no colors)
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }

    // Convenience level methods (no APIContext required)
    public function info($message, $exception = null)      { $this->logWithCaller('INFO', $message, $exception); }
    public function debug($message, $exception = null)     { $this->logWithCaller('DEBUG', $message, $exception); }
    public function error($message, $exception = null)     { $this->logWithCaller('ERROR', $message, $exception); }
    public function warning($message, $exception = null)   { $this->logWithCaller('WARNING', $message, $exception); }
    public function critical($message, $exception = null)  { $this->logWithCaller('CRITICAL', $message, $exception); }
    public function exception($message, \Exception $exception) { $this->logWithCaller('ERROR', $message, $exception); }
} 