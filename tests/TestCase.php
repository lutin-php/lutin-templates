<?php

declare(strict_types=1);

namespace LutinTemplates\Tests;

/**
 * Lightweight test case base class
 * Similar to PHPUnit but without external dependencies
 */
abstract class TestCase
{
    /** @var array<string> */
    private array $errors = [];

    /** @var array<string> */
    private array $passed = [];

    /**
     * Run all test methods in this class
     */
    final public function run(): array
    {
        $this->setUp();

        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (str_starts_with($method, 'test')) {
                $this->runTest($method);
            }
        }

        $this->tearDown();

        return [
            'passed' => $this->passed,
            'errors' => $this->errors,
        ];
    }

    /**
     * Run a single test method
     */
    private function runTest(string $method): void
    {
        try {
            // Reset state for each test
            $this->setUp();
            $this->$method();
            $this->passed[] = $method;
        } catch (\AssertionError $e) {
            $this->errors[$method] = $e->getMessage();
        } catch (\Exception $e) {
            $this->errors[$method] = get_class($e) . ': ' . $e->getMessage();
        } finally {
            $this->tearDown();
        }
    }

    /**
     * Setup before each test
     */
    protected function setUp(): void
    {
        // Override in subclass
    }

    /**
     * Teardown after each test
     */
    protected function tearDown(): void
    {
        // Override in subclass
    }

    // --- Assertion Methods ---

    protected function assertTrue(mixed $condition, string $message = ''): void
    {
        if ($condition !== true) {
            throw new \AssertionError($message ?: 'Failed asserting that ' . var_export($condition, true) . ' is true');
        }
    }

    protected function assertFalse(mixed $condition, string $message = ''): void
    {
        if ($condition !== false) {
            throw new \AssertionError($message ?: 'Failed asserting that ' . var_export($condition, true) . ' is false');
        }
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new \AssertionError($message ?: sprintf(
                "Failed asserting that %s matches expected %s",
                var_export($actual, true),
                var_export($expected, true)
            ));
        }
    }

    protected function assertNotEmpty(mixed $actual, string $message = ''): void
    {
        if (empty($actual)) {
            throw new \AssertionError($message ?: 'Failed asserting that value is not empty');
        }
    }

    protected function assertEmpty(mixed $actual, string $message = ''): void
    {
        if (!empty($actual)) {
            throw new \AssertionError($message ?: 'Failed asserting that ' . var_export($actual, true) . ' is empty');
        }
    }

    protected function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        if (!str_contains($haystack, $needle)) {
            throw new \AssertionError($message ?: "Failed asserting that string contains '$needle'");
        }
    }

    protected function assertStringStartsWith(string $prefix, string $string, string $message = ''): void
    {
        if (!str_starts_with($string, $prefix)) {
            throw new \AssertionError($message ?: "Failed asserting that string starts with '$prefix'");
        }
    }

    protected function assertStringEndsWith(string $suffix, string $string, string $message = ''): void
    {
        if (!str_ends_with($string, $suffix)) {
            throw new \AssertionError($message ?: "Failed asserting that string ends with '$suffix'");
        }
    }

    protected function assertFileExists(string $filename, string $message = ''): void
    {
        if (!file_exists($filename)) {
            throw new \AssertionError($message ?: "Failed asserting that file '$filename' exists");
        }
    }

    protected function assertFileDoesNotExist(string $filename, string $message = ''): void
    {
        if (file_exists($filename)) {
            throw new \AssertionError($message ?: "Failed asserting that file '$filename' does not exist");
        }
    }

    protected function assertIsArray(mixed $actual, string $message = ''): void
    {
        if (!is_array($actual)) {
            throw new \AssertionError($message ?: 'Failed asserting that value is an array');
        }
    }

    protected function assertIsInt(mixed $actual, string $message = ''): void
    {
        if (!is_int($actual)) {
            throw new \AssertionError($message ?: 'Failed asserting that value is an integer');
        }
    }

    protected function assertIsString(mixed $actual, string $message = ''): void
    {
        if (!is_string($actual)) {
            throw new \AssertionError($message ?: 'Failed asserting that value is a string');
        }
    }

    protected function assertGreaterThan(int $expected, int $actual, string $message = ''): void
    {
        if (!($actual > $expected)) {
            throw new \AssertionError($message ?: "Failed asserting that $actual is greater than $expected");
        }
    }

    protected function assertJson(string $json, string $message = ''): void
    {
        json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \AssertionError($message ?: 'Failed asserting that string is valid JSON: ' . json_last_error_msg());
        }
    }

    protected function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
    {
        if (!array_key_exists($key, $array)) {
            throw new \AssertionError($message ?: "Failed asserting that array has key '$key'");
        }
    }

    protected function assertInstanceOf(string $expected, mixed $actual, string $message = ''): void
    {
        if (!($actual instanceof $expected)) {
            throw new \AssertionError($message ?: sprintf(
                "Failed asserting that %s is an instance of %s",
                get_debug_type($actual),
                $expected
            ));
        }
    }

    protected function expectException(string $exceptionClass): void
    {
        // Placeholder - exceptions are caught in runTest
    }

    protected function expectExceptionMessage(string $message): void
    {
        // Placeholder - exceptions are caught in runTest
    }
}
