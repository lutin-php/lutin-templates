<?php
/**
 * Build Script for Lutin-Starters
 *
 * This script:
 * 1. Iterates through each folder in /starters/
 * 2. Compresses content into a ZIP file named starter-name.zip
 * 3. Calculates SHA-256 hash of each ZIP
 * 4. Updates starters.json with metadata
 *
 * Requirements: PHP 8.1+ with zip extension
 *
 * Usage:
 *   GITHUB_REPOSITORY=user/repo RELEASE_VERSION=1.0.0 php scripts/build-zips.php
 */

declare(strict_types=1);

// Check for required extension
if (!extension_loaded('zip')) {
    fwrite(STDERR, "ERROR: The 'zip' PHP extension is required but not installed.\n");
    fwrite(STDERR, "Install it with: sudo apt-get install php-zip (Debian/Ubuntu)\n");
    fwrite(STDERR, "                or: sudo yum install php-zip (RHEL/CentOS)\n");
    exit(1);
}

/**
 * Builder class for creating starter ZIP archives and manifest
 */
class StarterBuilder
{
    private string $startersDir;
    private string $distDir;
    private string $manifestFile;

    public function __construct(
        string $startersDir = __DIR__ . '/../starters',
        string $distDir = __DIR__ . '/../dist',
        string $manifestFile = __DIR__ . '/../starters.json'
    ) {
        $this->startersDir = $startersDir;
        $this->distDir = $distDir;
        $this->manifestFile = $manifestFile;
    }

    /**
     * Get environment variable or throw exception
     */
    public function getRequiredEnv(string $name): string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? null;

        if (empty($value) || trim($value) === '') {
            throw new \RuntimeException("{$name} environment variable is required and must not be empty");
        }

        return trim($value);
    }

    /**
     * Main build process
     */
    public function build(string $githubRepo, string $releaseVersion): void
    {
        echo "=== Lutin-Starters Build Script ===\n\n";

        // Ensure dist directory exists
        if (!is_dir($this->distDir)) {
            mkdir($this->distDir, 0755, true);
            echo "Created dist directory\n";
        }

        // Load existing manifest
        $manifest = $this->loadManifest();
        $starters = [];

        // Get all starter directories
        $starterDirs = glob($this->startersDir . '/*', GLOB_ONLYDIR);

        if (empty($starterDirs)) {
            echo "No starter directories found in " . $this->startersDir . "\n";
            exit(1);
        }

        foreach ($starterDirs as $starterPath) {
            $starterId = basename($starterPath);
            $zipName = $starterId . '.zip';
            $zipPath = $this->distDir . '/' . $zipName;

            echo "Processing starter: {$starterId}\n";

            // Create ZIP archive
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                echo "  ERROR: Failed to create ZIP: {$zipPath}\n";
                continue;
            }

            // Add all files from starter directory
            $this->addDirectoryToZip($zip, $starterPath, $starterId . '/');
            $zip->close();

            // Calculate hash
            $hash = $this->calculateHash($zipPath);
            $fileSize = filesize($zipPath);

            echo "  Created: {$zipName}\n";
            echo "  Size: " . number_format($fileSize) . " bytes\n";
            echo "  SHA-256: {$hash}\n";

            // Try to extract metadata from starter's lutin/AGENTS.md or a metadata.json
            $metadata = $this->extractStarterMetadata($starterPath, $starterId);

            $starters[] = [
                'id' => $starterId,
                'name' => $metadata['name'] ?? ucfirst(str_replace('-', ' ', $starterId)),
                'description' => $metadata['description'] ?? 'A starter template for Lutin.php',
                'hash' => 'sha256-' . $hash,
                'size' => $fileSize,
                'zip_name' => $zipName,
                'download_url' => "https://github.com/{$githubRepo}/releases/download/v{$releaseVersion}/{$zipName}"
            ];

            echo "\n";
        }

        // Update manifest
        $manifest['version'] = $releaseVersion;
        $manifest['generated_at'] = date('c');
        $manifest['starters'] = $starters;

        // Save manifest
        $jsonContent = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($jsonContent === false) {
            echo "ERROR: Failed to encode manifest to JSON\n";
            exit(1);
        }

        if (file_put_contents($this->manifestFile, $jsonContent) === false) {
            echo "ERROR: Failed to write manifest file\n";
            exit(1);
        }

        echo "Updated manifest: " . $this->manifestFile . "\n";
        echo "Total starters: " . count($starters) . "\n";
        echo "\nBuild complete!\n";
    }

    /**
     * Recursively add a directory to a ZIP archive
     */
    public function addDirectoryToZip(ZipArchive $zip, string $sourcePath, string $zipPath = ''): void
    {
        // Normalize source path to absolute path for consistent path calculation
        $absoluteSourcePath = realpath($sourcePath);
        if ($absoluteSourcePath === false) {
            throw new \RuntimeException("Source path not found: $sourcePath");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absoluteSourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            // Strip source path and leading slash to get relative path
            $relativeInSource = substr($filePath, strlen($absoluteSourcePath));
            $relativeInSource = ltrim($relativeInSource, '/\\');
            $relativePath = $zipPath . $relativeInSource;

            // Normalize path separators for ZIP
            $relativePath = str_replace('\\', '/', $relativePath);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * Calculate SHA-256 hash of a file
     */
    public function calculateHash(string $filePath): string
    {
        $hash = hash_file('sha256', $filePath);
        return $hash !== false ? $hash : '';
    }

    /**
     * Load existing manifest or create new structure
     */
    public function loadManifest(): array
    {
        if (file_exists($this->manifestFile)) {
            $content = file_get_contents($this->manifestFile);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return [
            'version' => '1.0',
            'generated_at' => '',
            'starters' => []
        ];
    }

    /**
     * Extract metadata from starter directory
     */
    public function extractStarterMetadata(string $starterPath, string $starterId): array
    {
        $metadata = [];

        // Look for metadata.json
        $metadataFile = $starterPath . '/metadata.json';
        if (file_exists($metadataFile)) {
            $content = file_get_contents($metadataFile);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $metadata = $decoded;
                }
            }
        }

        // If no metadata.json, try to extract from AGENTS.md
        if (empty($metadata)) {
            $agentsFile = $starterPath . '/lutin/AGENTS.md';
            if (!file_exists($agentsFile)) {
                // Try alternative location
                $agentsFile = $starterPath . '/AGENTS.md';
            }

            if (file_exists($agentsFile)) {
                $content = file_get_contents($agentsFile);
                if ($content !== false) {
                    // Extract first heading as name
                    if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
                        $metadata['name'] = trim($matches[1]);
                    }

                    // Extract first paragraph as description
                    if (preg_match('/^#\s+.+\n\n(.+)$/m', $content, $matches)) {
                        $metadata['description'] = trim($matches[1]);
                    }
                }
            }
        }

        return $metadata;
    }
}

// Run the build only when script is called directly (not included)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    $builder = new StarterBuilder();
    $githubRepo = $builder->getRequiredEnv('GITHUB_REPOSITORY');
    $releaseVersion = $builder->getRequiredEnv('RELEASE_VERSION');
    $builder->build($githubRepo, $releaseVersion);
}
