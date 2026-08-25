<?php
/**
 * CLI Output utility
 * 
 * Provides colored output functions and input helpers for CLI examples
 */

// ANSI color codes for terminal output
if (!class_exists('Colors')) {
    class Colors
    {
        const RESET = "\033[0m";
        const BOLD = "\033[1m";
        const RED = "\033[31m";
        const GREEN = "\033[32m";
        const YELLOW = "\033[33m";
        const BLUE = "\033[34m";
        const MAGENTA = "\033[35m";
        const CYAN = "\033[36m";
        const WHITE = "\033[37m";
    }
}

// Check if functions are already defined (when called from cli.php)
if (!function_exists('printError')) {
    function printError($message)
    {
        echo Colors::RED . "[ERROR] " . $message . Colors::RESET . "\n";
    }
}

if (!function_exists('printSuccess')) {
    function printSuccess($message)
    {
        echo Colors::GREEN . "[SUCCESS] " . $message . Colors::RESET . "\n";
    }
}

if (!function_exists('printInfo')) {
    function printInfo($message)
    {
        echo Colors::CYAN . "[INFO] " . $message . Colors::RESET . "\n";
    }
}

if (!function_exists('printWarning')) {
    function printWarning($message)
    {
        echo Colors::YELLOW . "[WARNING] " . $message . Colors::RESET . "\n";
    }
}

if (!function_exists('printSeparator')) {
    function printSeparator()
    {
        echo Colors::BLUE . str_repeat("=", 60) . Colors::RESET . "\n";
    }
}

if (!function_exists('printDocLink')) {
    function printDocLink($url, $description = '')
    {
        echo "\n" . Colors::CYAN . " Reference: " . Colors::RESET;
        echo Colors::BLUE . $url . Colors::RESET;
        if ($description) {
            echo " - " . $description;
        }
        echo "\n";
    }
}



if (!function_exists('getInput')) {
    function getInput($prompt, $required = true)
    {
        echo $prompt;
        $input = trim(fgets(STDIN));
        if ($required && empty($input)) {
            return null;
        }
        return $input;
    }
}

if (!function_exists('printHeader')) {
    function printHeader($title = "=== Nimbbl PHP SDK - Event-Based CLI ===")
    {
        echo Colors::CYAN . Colors::BOLD;
        echo $title . "\n";
        echo Colors::RESET . "\n";
    }
}

if (!function_exists('printStep')) {
    function printStep($stepNumber, $stepName)
    {
        echo "\n" . Colors::BLUE . Colors::BOLD;
        echo "Step {$stepNumber}: {$stepName}\n";
        echo str_repeat('-', 60) . Colors::RESET . "\n";
    }
}

if (!function_exists('printException')) {
    function printException($e)
    {
        // Check if it's a NimbblException with full error data
        if ($e instanceof \Nimbbl\Api\Exception\NimbblException) {
            printError("Exception: " . $e->getMessage() . "\n");

            $errorCode = $e->getErrorCode();
            $httpStatusCode = $e->getHttpStatusCode();
            $requestId = $e->getRequestId();
            $errorData = $e->getErrorData();

            if ($errorCode) {
                echo "  Error Code: {$errorCode}\n";
            }
            if ($httpStatusCode) {
                echo "  HTTP Status: {$httpStatusCode}\n";
            }
            if ($requestId) {
                echo "  Request ID: {$requestId}\n";
            }
            if ($errorData && !empty($errorData)) {
                echo "  Full Error Response:\n";
                // Format the error data with proper indentation
                $formatted = json_encode($errorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $lines = explode("\n", $formatted);
                foreach ($lines as $line) {
                    echo "  " . $line . "\n";
                }
            }
        } else {
            // Regular exception - just show message
            printError("Exception: " . $e->getMessage() . "\n");
        }
    }
}

