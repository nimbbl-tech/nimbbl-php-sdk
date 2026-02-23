<?php

namespace Nimbbl\Api\Log;

use Nimbbl\Api\Common\SdkConstants;
use Nimbbl\Api\RestClient\NimbblClient;

/**
 * Logger with simple formatted output
 * 
 * Features:
 * - Convenience level methods (info, debug, error, warning, critical, exception)
 * - Writes to log file, error_log (non-CLI), and stdout (CLI) in a uniform format
 */
class Logger
{
    private static $instance = null;
    private static $enableDebugLogging = false;
    
    private $logFile;
    private $logDir;

    // Log format: [timestamp][sdk sdkVersion][level][module:line][function]: message
    private const LOGGER_FORMAT = '[%s][%s %s][%s][%s:%d][%s]: %s';
    private const LOGGER_DATEFMT = 'Y-m-d H:i:s';

    // Log Levels
    public const LOG_ERROR = 'ERROR';
    public const LOG_REQUEST = 'REQUEST';
    public const LOG_RESPONSE = 'RESPONSE';
    public const LOG_INFO = 'INFO';
    public const LOG_DEBUG = 'DEBUG';
    public const LOG_WARNING = 'WARNING';
    public const LOG_DESERIALIZATION_ERROR = 'DESERIALIZATION_ERROR';

    private function __construct($logFile = null, $overrideLogFilename = false)
    {
        if ($logFile === null) {
            // Check if Api class has a log file configured
            if (class_exists('Nimbbl\Api\RestClient\NimbblClient') && NimbblClient::getLogFile() !== null) {
                $logFile = NimbblClient::getLogFile();
            } else {
                // Fallback: use logs directory relative to application root
                $logFile = getcwd() . '/logs/nimbbl_debug.log';
            }
        }
        $this->logFile = self::resolveLogFilePath($logFile, $overrideLogFilename);
        $this->logDir = dirname($logFile);
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public static function getInstance($logFile = null, ?bool $overrideLogFilename = null)
    {
        $overrideLogFilename = $overrideLogFilename ?? false;

        if ($logFile !== null) {
            $logFile = self::resolveLogFilePath($logFile, $overrideLogFilename);
        }
        // If logFile is provided and instance exists with different path, reset instance
        if (self::$instance !== null && $logFile !== null && self::$instance->logFile !== $logFile) {
            self::$instance = null;
        }

        if (self::$instance === null) {
            self::$instance = new self($logFile, $overrideLogFilename);
        }
        return self::$instance;
    }

    /**
     * Resolve the effective log file path used by the SDK.
     * 
     * When $overrideLogFilename is false (default), appends date suffix (_ddMMyyyy).
     * When $overrideLogFilename is true, returns the path as-is without date suffix.
     * Idempotent: if the input already ends with _ddMMyyyy before the extension, it will not add another suffix.
     *
     * Example with auto-dating enabled (override=false): logs/nimbbl_debug.log -> logs/nimbbl_debug_06012026.log
     * Example with override enabled (override=true): logs/nimbbl_debug.log -> logs/nimbbl_debug.log
     * 
     * @param string $logFilePath The log file path
     * @param bool $overrideLogFilename Whether to override log filename (no date suffix)
     * @return string The resolved log file path
     */
    private static function resolveLogFilePath($logFilePath, $overrideLogFilename = false)
    {
        if (!is_string($logFilePath) || trim($logFilePath) === '') {
            return $logFilePath;
        }
        // Don't mutate stream targets or /dev/null
        if (strpos($logFilePath, 'php://') === 0 || $logFilePath === '/dev/null') {
            return $logFilePath;
        }

        // If override filename is enabled, return path as-is (no date suffix)
        if ($overrideLogFilename) {
            return $logFilePath;
        }

        $dir = dirname($logFilePath);
        $ext = pathinfo($logFilePath, PATHINFO_EXTENSION);
        $name = pathinfo($logFilePath, PATHINFO_FILENAME);

        // Already has _ddMMyyyy suffix
        if (preg_match('/_\d{8}$/', $name) === 1) {
            return $logFilePath;
        }

        $suffix = date('dmY'); // ddMMyyyy (local date)
        $newName = $name . '_' . $suffix . ($ext ? ('.' . $ext) : '');
        return ($dir && $dir !== '.') ? ($dir . DIRECTORY_SEPARATOR . $newName) : $newName;
    }

    /**
     * Enable debug logging
     * 
     * @return void
     */
    public static function enableDebugLogging()
    {
        self::$enableDebugLogging = true;
    }


    /**
     * Disable debug logging
     * 
     * @return void
     */
    public static function disableDebugLogging()
    {
        self::$enableDebugLogging = false;
    }

    /**
     * Check if debug logging is enabled
     * 
     * @return bool
     */
    public static function isDebugLoggingEnabled()
    {
        return self::$enableDebugLogging;
    }



    /**
     * Log method - main logging method
     * 
     * DEBUG level logs are only printed if debug logging is enabled.
     * INFO, ERROR, WARNING, CRITICAL logs are always printed.
     * 
     * @param string $message Log message
     * @param string $level Log level (INFO, DEBUG, ERROR, WARNING, CRITICAL)
     * @param string $component Component name (used as module name)
     * @param int|null $line Line number (optional, will be 0 if not provided)
     * @param string|null $function Function name (optional, will be '-' if not provided)
     * @return void
     */
    public function log($message, $level = 'INFO', $component = 'NimbblSDK', $line = null, $function = null)
    {
        $upperLevel = strtoupper($level);

        // INFO/WARNING/ERROR/CRITICAL always logged
        // Only DEBUG logs are gated
        if ($upperLevel === 'DEBUG' && !self::$enableDebugLogging) {
            return;
        }

        // Use UTC timestamps
        $timestamp = gmdate(self::LOGGER_DATEFMT);
        $logMessage = sprintf(
            self::LOGGER_FORMAT,
            $timestamp,
            SdkConstants::SDK_NAME,
            SdkConstants::SDK_VERSION,
            $upperLevel,
            $component,
            $line ?? 0,
            $function ?? '-',
            $message
        ) . PHP_EOL;

        $this->writeLog($logMessage);
    }

    /**
     * Write log to file and output
     * 
     * @param string $logMessage Formatted log message
     * @return void
     */
    private function writeLog($logMessage)
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
            // Flush output buffer to ensure logs appear immediately
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
    }

    // Convenience level methods (no APIContext required)
    // All convenience methods extract caller info and use log() method

    /**
     * Log INFO level message
     * INFO logs are always printed regardless of debug flag
     */
    public function info($message, $exception = null)
    {
        $callerInfo = $this->getCallerInfo();
        $formattedMessage = $this->formatMessage($message, $exception);
        $this->log($formattedMessage, 'INFO', $callerInfo['module'], $callerInfo['line'], $callerInfo['function']);
    }

    /**
     * Log DEBUG level message
     * DEBUG logs are only printed if debug logging is enabled
     */
    public function debug($message, $exception = null)
    {
        $callerInfo = $this->getCallerInfo();
        $formattedMessage = $this->formatMessage($message, $exception);
        $this->log($formattedMessage, 'DEBUG', $callerInfo['module'], $callerInfo['line'], $callerInfo['function']);
    }

    /**
     * Log ERROR level message
     * ERROR logs are always printed
     */
    public function error($message, $exception = null)
    {
        $callerInfo = $this->getCallerInfo();
        $formattedMessage = $this->formatMessage($message, $exception);
        $this->log($formattedMessage, 'ERROR', $callerInfo['module'], $callerInfo['line'], $callerInfo['function']);
    }

    /**
     * Log WARNING level message
     * WARNING logs are always printed
     */
    public function warning($message, $exception = null)
    {
        $callerInfo = $this->getCallerInfo();
        $formattedMessage = $this->formatMessage($message, $exception);
        $this->log($formattedMessage, 'WARNING', $callerInfo['module'], $callerInfo['line'], $callerInfo['function']);
    }

    /**
     * Log CRITICAL level message
     * CRITICAL logs are always printed
     */
    public function critical($message, $exception = null)
    {
        $callerInfo = $this->getCallerInfo();
        $formattedMessage = $this->formatMessage($message, $exception);
        $this->log($formattedMessage, 'CRITICAL', $callerInfo['module'], $callerInfo['line'], $callerInfo['function']);
    }

    /**
     * Log exception as ERROR level
     * Exception logs are always printed
     */
    public function exception($message, \Exception $exception)
    {
        $callerInfo = $this->getCallerInfo();
        $formattedMessage = $this->formatMessage($message, $exception);
        $this->log($formattedMessage, 'EXCEPTION', $callerInfo['module'], $callerInfo['line'], $callerInfo['function']);
    }

    /**
     * Get caller information from backtrace
     * 
     * Skips the convenience method (debug, info, error, etc.) and gets the actual caller
     * 
     * @return array Array with 'module', 'line', and 'function' keys
     */
    private function getCallerInfo()
    {
        // We want:
        // - module:line => the call-site location where logger method was invoked
        // - function => the function/method that invoked the logger
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 32);

        $loggerClass = __CLASS__;
        $loggerFns = ['log', 'debug', 'info', 'warning', 'error', 'critical', 'exception', 'getCallerInfo', 'formatMessage', 'writeLog'];

        $callSiteFrame = null;
        $callerFrame = null;

        // Find the first frame for a logger public method; its 'file'/'line' point to the call-site.
        for ($i = 0; $i < count($backtrace); $i++) {
            $f = $backtrace[$i] ?? null;
            if (!is_array($f))
                continue;
            $cls = $f['class'] ?? null;
            $fn = $f['function'] ?? null;
            if ($cls === $loggerClass && is_string($fn) && in_array($fn, $loggerFns, true)) {
                // Skip internal logger helpers; look for an actual logging entrypoint
                if (in_array($fn, ['getCallerInfo', 'formatMessage', 'writeLog'], true)) {
                    continue;
                }
                $callSiteFrame = $f;
                // Caller is the next non-logger frame after this
                for ($j = $i + 1; $j < count($backtrace); $j++) {
                    $c = $backtrace[$j] ?? null;
                    if (!is_array($c))
                        continue;
                    $cCls = $c['class'] ?? null;
                    $cFn = $c['function'] ?? '';
                    if ($cCls === $loggerClass) {
                        continue;
                    }
                    // Skip anonymous/internal wrappers
                    if (is_string($cFn) && in_array($cFn, $loggerFns, true)) {
                        continue;
                    }
                    $callerFrame = $c;
                    break;
                }
                break;
            }
        }

        $moduleFile = $callSiteFrame['file'] ?? ($callerFrame['file'] ?? null);
        $module = $moduleFile ? basename($moduleFile) : 'unknown';
        $line = $callSiteFrame['line'] ?? 0;

        $fn = $callerFrame['function'] ?? '-';
        $cls = $callerFrame['class'] ?? '';
        $type = $callerFrame['type'] ?? '';
        $function = ($cls !== '' && $fn !== '-') ? ($cls . '.' . $fn) : ($fn ?: '-');

        return [
            'module' => $module,
            'line' => $line,
            'function' => $function,
        ];
    }

    /**
     * Format message with exception if provided
     * 
     * @param string $message Log message
     * @param \Exception|null $exception Optional exception
     * @return string Formatted message
     */
    private function formatMessage($message, $exception = null)
    {
        if ($exception instanceof \Exception) {
            return $message . ' Exception: ' . $exception->getMessage() . "\nTrace: " . $exception->getTraceAsString();
        }
        return $message;
    }
}