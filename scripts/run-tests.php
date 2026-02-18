#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Simple test runner for Lutin-Templates
 * 
 * Usage: php scripts/run-tests.php
 */

// Colors for terminal output
const GREEN = "\033[32m";
const RED = "\033[31m";
const YELLOW = "\033[33m";
const BLUE = "\033[34m";
const RESET = "\033[0m";

/**
 * Print colored message
 */
function printMessage(string $message, string $color = RESET): void
{
    echo $color . $message . RESET . PHP_EOL;
}

/**
 * Check PHP version
 */
function checkPhpVersion(): bool
{
    if (version_compare(PHP_VERSION, '8.1.0', '<')) {
        printMessage("ERROR: PHP 8.1+ is required. Current version: " . PHP_VERSION, RED);
        return false;
    }
    return true;
}

/**
 * Check if zip extension is available
 */
function checkExtensions(): bool
{
    if (!extension_loaded('zip')) {
        printMessage("ERROR: The 'zip' PHP extension is required.", RED);
        printMessage("Install it with: sudo apt-get install php-zip (Debian/Ubuntu)", RED);
        return false;
    }
    return true;
}

/**
 * Find all test classes
 */
function findTestClasses(string $testsDir): array
{
    $classes = [];
    $files = glob($testsDir . '/*Test.php');

    foreach ($files as $file) {
        $className = basename($file, '.php');
        $classes[] = 'LutinTemplates\\Tests\\' . $className;
    }

    return $classes;
}

/**
 * Run all tests
 */
function runTests(array $testClasses): array
{
    $totalPassed = 0;
    $totalFailed = 0;
    $failures = [];

    foreach ($testClasses as $className) {
        printMessage("\n$className", BLUE);

        $test = new $className();
        $results = $test->run();

        foreach ($results['passed'] as $testName) {
            printMessage("  ✓ $testName", GREEN);
            $totalPassed++;
        }

        foreach ($results['errors'] as $testName => $error) {
            printMessage("  ✗ $testName", RED);
            printMessage("    $error", RED);
            $totalFailed++;
            $failures[] = "$className::$testName - $error";
        }
    }

    return [
        'passed' => $totalPassed,
        'failed' => $totalFailed,
        'failures' => $failures,
    ];
}

/**
 * Main execution
 */
function main(): int
{
    printMessage("=== Lutin-Templates Test Runner ===\n", YELLOW);

    // Check requirements
    if (!checkPhpVersion()) {
        return 1;
    }

    if (!checkExtensions()) {
        return 1;
    }

    // Load bootstrap
    $projectRoot = dirname(__DIR__);
    require $projectRoot . '/tests/bootstrap.php';

    // Find and run tests
    $testClasses = findTestClasses($projectRoot . '/tests');

    if (empty($testClasses)) {
        printMessage("No test classes found.", YELLOW);
        return 0;
    }

    printMessage("Found " . count($testClasses) . " test class(es)\n", YELLOW);

    $results = runTests($testClasses);

    // Print summary
    printMessage("\n=== Summary ===", YELLOW);
    printMessage("Passed: {$results['passed']}", GREEN);
    printMessage("Failed: {$results['failed']}", $results['failed'] > 0 ? RED : GREEN);

    if ($results['failed'] > 0) {
        printMessage("\nFailed tests:", RED);
        foreach ($results['failures'] as $failure) {
            printMessage("  - $failure", RED);
        }
        return 1;
    }

    printMessage("\n✓ All tests passed!", GREEN);
    return 0;
}

exit(main());
