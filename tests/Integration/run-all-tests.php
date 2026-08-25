<?php
/**
 * Nimbbl PHP SDK - Master Test Runner
 * 
 * Runs all API client tests
 * 
 * Usage: php run-all-tests.php
 */

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     Nimbbl PHP SDK - API Client Test Suite                  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$tests = [
    'Addresses API' => 'AddressesTest.php',
    'Payments API' => 'PaymentTest.php',
    'Payment Links API' => 'PaymentLinkTest.php',
    'Checkout Utilities API' => 'CheckoutUtilitiesTest.php',
    'Transaction Status API' => 'TransactionTest.php',
];

$results = [];

foreach ($tests as $testName => $testFile) {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Running: {$testName}\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $startTime = microtime(true);
    
    // Capture output
    ob_start();
    $exitCode = 0;
    
    try {
        include __DIR__ . '/../Services/' . $testFile;
    } catch (Exception $e) {
        echo "[ERROR] Fatal Error: " . $e->getMessage() . "\n";
        $exitCode = 1;
    }
    
    $output = ob_get_clean();
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo $output;
    
    $results[$testName] = [
        'file' => $testFile,
        'duration' => $duration,
        'exit_code' => $exitCode,
        'success' => $exitCode === 0
    ];
    
    echo "\n[INFO]  Duration: {$duration}s\n";
}

// Summary
echo "\n\n" . str_repeat('=', 60) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat('=', 60) . "\n\n";

$totalTests = count($results);
$passedTests = count(array_filter($results, function($r) { return $r['success']; }));
$failedTests = $totalTests - $passedTests;
$totalDuration = array_sum(array_column($results, 'duration'));

foreach ($results as $testName => $result) {
    $status = $result['success'] ? '[SUCCESS] PASS' : '[ERROR] FAIL';
    $duration = $result['duration'];
    echo sprintf("%-30s %-10s %6ss\n", $testName, $status, $duration);
}

echo "\n" . str_repeat('-', 60) . "\n";
echo sprintf("Total: %d tests | Passed: %d | Failed: %d | Duration: %.2fs\n", 
    $totalTests, $passedTests, $failedTests, $totalDuration);

if ($failedTests > 0) {
    echo "\n[WARNING]  Some tests failed. Please check the output above for details.\n";
    exit(1);
} else {
    echo "\n[SUCCESS] All tests passed!\n";
    exit(0);
}

