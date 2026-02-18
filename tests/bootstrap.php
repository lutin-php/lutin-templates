<?php

declare(strict_types=1);

/**
 * Test Bootstrap
 */

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define test constants
define('TEST_ROOT', __DIR__);
define('PROJECT_ROOT', dirname(__DIR__));
define('TEST_TMP_DIR', PROJECT_ROOT . '/tmp/test-output');

// Create test output directory if it doesn't exist
if (!is_dir(TEST_TMP_DIR)) {
    mkdir(TEST_TMP_DIR, 0755, true);
}

// Load base test case
require __DIR__ . '/TestCase.php';

// Autoloader for test classes
spl_autoload_register(function (string $class): void {
    $prefix = 'LutinTemplates\\Tests\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
