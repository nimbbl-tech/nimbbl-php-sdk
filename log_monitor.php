<?php
/**
 * Nimbbl Log Monitor Utility
 * 
 * This script helps monitor all Nimbbl logs in real-time
 * Usage: php log_monitor.php [options]
 * 
 * Options:
 *   --follow     Follow logs in real-time (like tail -f)
 *   --lines=N    Show last N lines (default: 50)
 *   --file=path  Monitor specific log file
 *   --nginx      Monitor nginx error log
 *   --all        Monitor all log sources
 */

class NimbblLogMonitor
{
    private $logFile;
    private $nginxErrorLog;
    private $follow = false;
    private $lines = 50;
    
    public function __construct()
    {
        $this->logFile = dirname(__FILE__) . '/../../../logs/nimbbl_debug.log';
        $this->nginxErrorLog = '/var/log/nginx/error.log'; // Common nginx error log path
        
        $this->parseArguments();
    }
    
    private function parseArguments()
    {
        global $argv;
        
        foreach ($argv as $arg) {
            if ($arg === '--follow') {
                $this->follow = true;
            } elseif (strpos($arg, '--lines=') === 0) {
                $this->lines = (int)substr($arg, 8);
            } elseif (strpos($arg, '--file=') === 0) {
                $this->logFile = substr($arg, 7);
            } elseif ($arg === '--nginx') {
                $this->nginxErrorLog = '/var/log/nginx/error.log';
            }
        }
    }
    
    public function monitor()
    {
        echo "=== Nimbbl Log Monitor ===\n";
        echo "Log File: {$this->logFile}\n";
        echo "Nginx Error Log: {$this->nginxErrorLog}\n";
        echo "Follow Mode: " . ($this->follow ? 'Yes' : 'No') . "\n";
        echo "Lines to Show: {$this->lines}\n";
        echo "========================\n\n";
        
        if ($this->follow) {
            $this->followLogs();
        } else {
            $this->showRecentLogs();
        }
    }
    
    private function showRecentLogs()
    {
        // Show custom log file
        if (file_exists($this->logFile)) {
            echo "=== Custom Log File ===\n";
            $lines = $this->getLastLines($this->logFile, $this->lines);
            foreach ($lines as $line) {
                echo $line;
            }
            echo "\n";
        } else {
            echo "Custom log file not found: {$this->logFile}\n";
        }
        
        // Show nginx error log (Nimbbl entries only)
        if (file_exists($this->nginxErrorLog)) {
            echo "=== Nginx Error Log (Nimbbl entries only) ===\n";
            $lines = $this->getLastLines($this->nginxErrorLog, $this->lines * 2);
            $nimbblLines = array_filter($lines, function($line) {
                return strpos($line, 'Nimbbl') !== false || strpos($line, 'nimbbl') !== false;
            });
            $nimbblLines = array_slice($nimbblLines, -$this->lines);
            foreach ($nimbblLines as $line) {
                echo $line;
            }
            echo "\n";
        } else {
            echo "Nginx error log not found: {$this->nginxErrorLog}\n";
        }
    }
    
    private function followLogs()
    {
        echo "Following logs... Press Ctrl+C to stop\n\n";
        
        $files = [];
        
        if (file_exists($this->logFile)) {
            $files[] = $this->logFile;
        }
        
        if (file_exists($this->nginxErrorLog)) {
            $files[] = $this->nginxErrorLog;
        }
        
        if (empty($files)) {
            echo "No log files found to monitor.\n";
            return;
        }
        
        $positions = [];
        foreach ($files as $file) {
            $positions[$file] = filesize($file);
        }
        
        while (true) {
            foreach ($files as $file) {
                $currentSize = filesize($file);
                if ($currentSize > $positions[$file]) {
                    $handle = fopen($file, 'r');
                    fseek($handle, $positions[$file]);
                    
                    while (($line = fgets($handle)) !== false) {
                        // Only show Nimbbl-related lines for nginx log
                        if ($file === $this->nginxErrorLog) {
                            if (strpos($line, 'Nimbbl') !== false || strpos($line, 'nimbbl') !== false) {
                                echo "[{$file}] " . trim($line) . "\n";
                            }
                        } else {
                            echo "[{$file}] " . trim($line) . "\n";
                        }
                    }
                    
                    fclose($handle);
                    $positions[$file] = $currentSize;
                }
            }
            
            usleep(100000); // Sleep for 0.1 seconds
        }
    }
    
    private function getLastLines($file, $lines)
    {
        if (!file_exists($file)) {
            return [];
        }
        
        $fileSize = filesize($file);
        if ($fileSize === 0) {
            return [];
        }
        
        $handle = fopen($file, 'r');
        $pos = $fileSize - 1;
        $lineCount = 0;
        $lines = [];
        
        while ($pos > 0 && $lineCount < $lines) {
            fseek($handle, $pos);
            $char = fgetc($handle);
            
            if ($char === "\n") {
                $line = fgets($handle);
                if ($line !== false) {
                    array_unshift($lines, $line);
                    $lineCount++;
                }
            }
            
            $pos--;
        }
        
        // Get the first line if we haven't reached the beginning
        if ($pos === 0) {
            fseek($handle, 0);
            $line = fgets($handle);
            if ($line !== false) {
                array_unshift($lines, $line);
            }
        }
        
        fclose($handle);
        return $lines;
    }
}

// Run the monitor
if (php_sapi_name() === 'cli') {
    $monitor = new NimbblLogMonitor();
    $monitor->monitor();
} else {
    echo "This script should be run from command line.\n";
    echo "Usage: php log_monitor.php [--follow] [--lines=N] [--file=path] [--nginx]\n";
}
?> 