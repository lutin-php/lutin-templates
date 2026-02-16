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
 */

declare(strict_types=1);

// Check for required extension
if (!extension_loaded('zip')) {
    fwrite(STDERR, "ERROR: The 'zip' PHP extension is required but not installed.\n");
    fwrite(STDERR, "Install it with: sudo apt-get install php-zip (Debian/Ubuntu)\n");
    fwrite(STDERR, "                or: sudo yum install php-zip (RHEL/CentOS)\n");
    exit(1);
}

// Configuration
const STARTERS_DIR = __DIR__ . '/../starters';
const DIST_DIR = __DIR__ . '/../dist';
const MANIFEST_FILE = __DIR__ . '/../starters.json';

// Runtime configuration - use placeholders for CI to replace
$githubRepo = '{REPO}';
$releaseVersion = '{VERSION}';

/**
 * Recursively add a directory to a ZIP archive
 */
function addDirectoryToZip(ZipArchive $zip, string $sourcePath, string $zipPath = ''): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        $filePath = $file->getRealPath();
        $relativePath = $zipPath . substr($filePath, strlen($sourcePath));

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
function calculateHash(string $filePath): string
{
    $hash = hash_file('sha256', $filePath);
    return $hash !== false ? $hash : '';
}

/**
 * Load existing manifest or create new structure
 */
function loadManifest(): array
{
    if (file_exists(MANIFEST_FILE)) {
        $content = file_get_contents(MANIFEST_FILE);
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
 * Main build process
 */
function buildStarters(): void
{
    echo "=== Lutin-Starters Build Script ===\n\n";

    // Ensure dist directory exists
    if (!is_dir(DIST_DIR)) {
        mkdir(DIST_DIR, 0755, true);
        echo "Created dist directory\n";
    }

    // Load existing manifest
    $manifest = loadManifest();
    $starters = [];

    // Get all starter directories
    $starterDirs = glob(STARTERS_DIR . '/*', GLOB_ONLYDIR);

    if (empty($starterDirs)) {
        echo "No starter directories found in " . STARTERS_DIR . "\n";
        exit(1);
    }

    foreach ($starterDirs as $starterPath) {
        $starterId = basename($starterPath);
        $zipName = $starterId . '.zip';
        $zipPath = DIST_DIR . '/' . $zipName;

        echo "Processing starter: {$starterId}\n";

        // Create ZIP archive
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            echo "  ERROR: Failed to create ZIP: {$zipPath}\n";
            continue;
        }

        // Add all files from starter directory
        addDirectoryToZip($zip, $starterPath, $starterId . '/');
        $zip->close();

        // Calculate hash
        $hash = calculateHash($zipPath);
        $fileSize = filesize($zipPath);

        echo "  Created: {$zipName}\n";
        echo "  Size: " . number_format($fileSize) . " bytes\n";
        echo "  SHA-256: {$hash}\n";

        // Try to extract metadata from starter's lutin/AGENTS.md or a metadata.json
        $metadata = extractStarterMetadata($starterPath, $starterId);

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

    if (file_put_contents(MANIFEST_FILE, $jsonContent) === false) {
        echo "ERROR: Failed to write manifest file\n";
        exit(1);
    }

    echo "Updated manifest: " . MANIFEST_FILE . "\n";
    echo "Total starters: " . count($starters) . "\n";
    echo "\nBuild complete!\n";
}

/**
 * Extract metadata from starter directory
 */
function extractStarterMetadata(string $starterPath, string $starterId): array
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

// Run the build
buildStarters();
