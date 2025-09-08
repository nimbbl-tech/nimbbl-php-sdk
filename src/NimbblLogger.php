<?php

namespace Nimbbl\Api;

class NimbblLogger
{
    private static $instance = null;
    private $logFile;
    private $logDir;

    private function __construct($logFile = null)
    {
        if ($logFile === null) {
            $logFile = dirname(__FILE__) . '/../../../logs/nimbbl_debug.log';
        }
        $this->logFile = $logFile;
        $this->logDir = dirname($logFile);
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public static function getInstance($logFile = null)
    {
        if (self::$instance === null) {
            self::$instance = new self($logFile);
        }
        return self::$instance;
    }

    public function log($message, $level = 'INFO', $component = 'NimbblSDK')
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] [{$component}] {$message}" . PHP_EOL;

        // Write to error log (nginx error.log)
        error_log($logMessage);

        // Write to custom log file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);

        // Print to stdout if CLI
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }
} 