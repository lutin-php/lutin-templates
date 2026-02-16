<?php

declare(strict_types=1);

namespace LutinStarters\Tests;

/**
 * Tests for the build-zips.php script
 */
class BuildScriptTest extends TestCase
{
    private string $testDistDir;
    private string $testManifestFile;
    private string $originalManifest;
    private array $envBackup;
    private ?\StarterBuilder $builder = null;

    protected function setUp(): void
    {
        // Backup original environment variables
        $this->envBackup = [
            'GITHUB_REPOSITORY' => $_ENV['GITHUB_REPOSITORY'] ?? null,
            'RELEASE_VERSION' => $_ENV['RELEASE_VERSION'] ?? null,
        ];

        // Set up test directories
        $this->testDistDir = TEST_TMP_DIR . '/dist-' . uniqid();
        $this->testManifestFile = TEST_TMP_DIR . '/starters-' . uniqid() . '.json';

        if (!is_dir($this->testDistDir)) {
            mkdir($this->testDistDir, 0755, true);
        }

        // Backup original manifest if exists
        $originalManifestPath = PROJECT_ROOT . '/starters.json';
        if (file_exists($originalManifestPath)) {
            $this->originalManifest = file_get_contents($originalManifestPath);
        }

        // Load the builder class
        require_once PROJECT_ROOT . '/scripts/build-zips.php';
        $this->builder = new \StarterBuilder(
            PROJECT_ROOT . '/starters',
            $this->testDistDir,
            $this->testManifestFile
        );
    }

    protected function tearDown(): void
    {
        // Restore environment variables
        foreach ($this->envBackup as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                $_ENV[$key] = $_SERVER[$key] = $value;
            }
        }

        // Clean up test files
        $this->recursiveDelete($this->testDistDir);
        if (file_exists($this->testManifestFile)) {
            unlink($this->testManifestFile);
        }

        // Restore original manifest
        $originalManifestPath = PROJECT_ROOT . '/starters.json';
        if (isset($this->originalManifest)) {
            file_put_contents($originalManifestPath, $this->originalManifest);
        }
    }

    /**
     * Test that the build script requires GITHUB_REPOSITORY environment variable
     */
    public function testBuildScriptRequiresGithubRepository(): void
    {
        unset($_ENV['GITHUB_REPOSITORY'], $_SERVER['GITHUB_REPOSITORY']);
        $_ENV['RELEASE_VERSION'] = '1.0.0';

        try {
            $this->builder->getRequiredEnv('GITHUB_REPOSITORY');
            $this->assertFalse(true, 'Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('GITHUB_REPOSITORY', $e->getMessage());
        }
    }

    /**
     * Test that the build script requires RELEASE_VERSION environment variable
     */
    public function testBuildScriptRequiresReleaseVersion(): void
    {
        $_ENV['GITHUB_REPOSITORY'] = 'test/repo';
        unset($_ENV['RELEASE_VERSION'], $_SERVER['RELEASE_VERSION']);

        try {
            $this->builder->getRequiredEnv('RELEASE_VERSION');
            $this->assertFalse(true, 'Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('RELEASE_VERSION', $e->getMessage());
        }
    }

    /**
     * Test that the build script rejects empty GITHUB_REPOSITORY
     */
    public function testBuildScriptRejectsEmptyGithubRepository(): void
    {
        $_ENV['GITHUB_REPOSITORY'] = '   ';
        $_ENV['RELEASE_VERSION'] = '1.0.0';

        try {
            $this->builder->getRequiredEnv('GITHUB_REPOSITORY');
            $this->assertFalse(true, 'Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('GITHUB_REPOSITORY', $e->getMessage());
        }
    }

    /**
     * Test successful build execution
     */
    public function testBuildScriptCreatesZipAndManifest(): void
    {
        $_ENV['GITHUB_REPOSITORY'] = 'test/repo';
        $_ENV['RELEASE_VERSION'] = '1.0.0-test';

        // Run the build
        ob_start();
        $this->builder->build('test/repo', '1.0.0-test');
        $output = ob_get_clean();

        // Assert the script ran successfully
        $this->assertStringContainsString('=== Lutin-Starters Build Script ===', $output);
        $this->assertStringContainsString('Build complete!', $output);

        // Check that ZIP files were created
        $distFiles = glob($this->testDistDir . '/*.zip');
        $this->assertNotEmpty($distFiles, 'No ZIP files were created in dist/');

        // Check that manifest was created
        $this->assertFileExists($this->testManifestFile);

        // Validate JSON structure
        $manifestContent = file_get_contents($this->testManifestFile);
        $this->assertJson($manifestContent);

        $manifest = json_decode($manifestContent, true);
        $this->assertIsArray($manifest);
        $this->assertArrayHasKey('version', $manifest);
        $this->assertArrayHasKey('generated_at', $manifest);
        $this->assertArrayHasKey('starters', $manifest);
        $this->assertIsArray($manifest['starters']);
    }

    /**
     * Test that created ZIP files contain expected structure
     */
    public function testZipFilesContainExpectedStructure(): void
    {
        $_ENV['GITHUB_REPOSITORY'] = 'test/repo';
        $_ENV['RELEASE_VERSION'] = '1.0.0-test';

        ob_start();
        $this->builder->build('test/repo', '1.0.0-test');
        ob_end_clean();

        $zipFiles = glob($this->testDistDir . '/*.zip');
        $this->assertNotEmpty($zipFiles);

        foreach ($zipFiles as $zipFile) {
            $zip = new \ZipArchive();
            $result = $zip->open($zipFile);
            $this->assertTrue($result, "Failed to open ZIP: $zipFile");

            // Check that ZIP is not empty
            $this->assertGreaterThan(0, $zip->numFiles, "ZIP file is empty: $zipFile");

            // Check that expected directories exist
            $foundPublic = false;
            $foundIndex = false;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                
                // Check for public/ directory
                if (str_contains($name, '/public/')) {
                    $foundPublic = true;
                }
                
                // Check for index.php in public
                if (str_ends_with($name, '/public/index.php')) {
                    $foundIndex = true;
                }
            }

            $zip->close();

            $this->assertTrue($foundPublic, "ZIP should contain public/ directory: $zipFile");
            $this->assertTrue($foundIndex, "ZIP should contain public/index.php: $zipFile");
        }
    }

    /**
     * Test manifest content structure for each starter
     */
    public function testManifestContainsValidStarterEntries(): void
    {
        $_ENV['GITHUB_REPOSITORY'] = 'test/repo';
        $_ENV['RELEASE_VERSION'] = '1.0.0-test';

        ob_start();
        $this->builder->build('test/repo', '1.0.0-test');
        ob_end_clean();

        $manifestContent = file_get_contents($this->testManifestFile);
        $manifest = json_decode($manifestContent, true);

        $this->assertArrayHasKey('starters', $manifest);
        $this->assertNotEmpty($manifest['starters'], 'Manifest should contain at least one starter');

        foreach ($manifest['starters'] as $starter) {
            $this->assertArrayHasKey('id', $starter);
            $this->assertArrayHasKey('name', $starter);
            $this->assertArrayHasKey('description', $starter);
            $this->assertArrayHasKey('hash', $starter);
            $this->assertArrayHasKey('size', $starter);
            $this->assertArrayHasKey('zip_name', $starter);
            $this->assertArrayHasKey('download_url', $starter);

            // Validate hash format
            $this->assertStringStartsWith('sha256-', $starter['hash']);
            $this->assertEquals(71, strlen($starter['hash'])); // 'sha256-' + 64 hex chars

            // Validate size is positive integer
            $this->assertIsInt($starter['size']);
            $this->assertGreaterThan(0, $starter['size']);

            // Validate download URL format
            $this->assertStringStartsWith('https://github.com/', $starter['download_url']);
            $this->assertStringContainsString('/releases/download/', $starter['download_url']);
            $this->assertStringEndsWith($starter['zip_name'], $starter['download_url']);

            // Validate ZIP file exists
            $zipPath = $this->testDistDir . '/' . $starter['zip_name'];
            $this->assertFileExists($zipPath);

            // Verify hash matches actual file
            $actualHash = hash_file('sha256', $zipPath);
            $this->assertEquals($starter['hash'], 'sha256-' . $actualHash);

            // Verify size matches actual file
            $actualSize = filesize($zipPath);
            $this->assertEquals($starter['size'], $actualSize);
        }
    }

    /**
     * Test that manifest version matches release version
     */
    public function testManifestVersionMatchesReleaseVersion(): void
    {
        $_ENV['GITHUB_REPOSITORY'] = 'test/repo';
        $_ENV['RELEASE_VERSION'] = '2.0.0-test-version';

        ob_start();
        $this->builder->build('test/repo', '2.0.0-test-version');
        ob_end_clean();

        $manifest = json_decode(file_get_contents($this->testManifestFile), true);
        $this->assertEquals('2.0.0-test-version', $manifest['version']);
    }

    /**
     * Test that generated_at is a valid ISO 8601 date
     */
    public function testManifestGeneratedAtIsValidIsoDate(): void
    {
        $_ENV['GITHUB_REPOSITORY'] = 'test/repo';
        $_ENV['RELEASE_VERSION'] = '1.0.0';

        ob_start();
        $this->builder->build('test/repo', '1.0.0');
        ob_end_clean();

        $manifest = json_decode(file_get_contents($this->testManifestFile), true);
        
        $this->assertArrayHasKey('generated_at', $manifest);
        $this->assertNotEmpty($manifest['generated_at']);

        // Validate ISO 8601 format
        $date = \DateTime::createFromFormat(\DateTime::ATOM, $manifest['generated_at']);
        $this->assertInstanceOf(\DateTime::class, $date);
    }

    /**
     * Test hash calculation
     */
    public function testCalculateHashReturnsValidSha256(): void
    {
        // Create a test file
        $testFile = TEST_TMP_DIR . '/test-hash-' . uniqid() . '.txt';
        file_put_contents($testFile, 'test content');

        $hash = $this->builder->calculateHash($testFile);

        // SHA-256 of "test content" is 6ae8a75555209fd6c44157c0aed8016e763ff435a19cf186f76863140143ff72
        $this->assertEquals(64, strlen($hash));
        $this->assertTrue(ctype_xdigit($hash), 'Hash should be hexadecimal');

        unlink($testFile);
    }

    /**
     * Test loadManifest returns default structure when file doesn't exist
     */
    public function testLoadManifestReturnsDefaultStructure(): void
    {
        $nonExistentFile = TEST_TMP_DIR . '/non-existent-' . uniqid() . '.json';
        $builder = new \StarterBuilder(
            PROJECT_ROOT . '/starters',
            $this->testDistDir,
            $nonExistentFile
        );

        $manifest = $builder->loadManifest();

        $this->assertIsArray($manifest);
        $this->assertArrayHasKey('version', $manifest);
        $this->assertArrayHasKey('generated_at', $manifest);
        $this->assertArrayHasKey('starters', $manifest);
        $this->assertEquals('1.0', $manifest['version']);
        $this->assertIsArray($manifest['starters']);
        $this->assertEmpty($manifest['starters']);
    }

    /**
     * Test extractStarterMetadata from AGENTS.md
     */
    public function testExtractMetadataFromAgentsMd(): void
    {
        // The blog-static starter has an AGENTS.md file
        $metadata = $this->builder->extractStarterMetadata(
            PROJECT_ROOT . '/starters/blog-static',
            'blog-static'
        );

        $this->assertIsArray($metadata);
        // Should extract name and description from AGENTS.md
        $this->assertArrayHasKey('name', $metadata);
        $this->assertNotEmpty($metadata['name']);
    }

    /**
     * Recursively delete a directory
     */
    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }
}
