<?php

namespace App\Services;

use App\Models\Theme;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class ThemeValidator
{
    /**
     * Required files for a valid theme
     */
    protected array $requiredFiles = [
        'theme.json',
    ];

    /**
     * Required fields in theme.json
     */
    protected array $requiredFields = [
        'name',
        'slug',
        'version',
        'description',
        'author',
    ];

    /**
     * Forbidden file patterns (no PHP execution in themes)
     */
    protected array $forbiddenPatterns = [
        '*.php',
        '*.phtml',
        '*.php3',
        '*.php4',
        '*.php5',
        '*.phar',
        '.htaccess',
        '.env',
        '.env.*',
    ];

    /**
     * Allowed file extensions for themes
     */
    protected array $allowedExtensions = [
        // Config
        'json',
        // Styles
        'css', 'scss', 'sass', 'less',
        // Scripts
        'js', 'mjs', 'ts',
        // Templates (blade is allowed)
        'blade.php', 'html',
        // Images
        'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'avif',
        // Fonts
        'woff', 'woff2', 'ttf', 'eot', 'otf',
        // Documentation
        'md', 'txt', 'LICENSE',
        // Build artifacts
        'map',
    ];

    /**
     * Allowed specific filenames (exact match)
     */
    protected array $allowedFilenames = [
        'package-lock.json',
        'composer.lock',
        'yarn.lock',
        'pnpm-lock.yaml',
    ];

    /**
     * Validate a theme directory before activation (preflight check)
     */
    public function preflightCheck(Theme $theme): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Verify theme directory exists
        if (!File::exists($theme->path)) {
            return ValidationResult::failed(['Theme directory not found: ' . $theme->path]);
        }

        // Verify theme.json exists and is readable
        $themeJsonPath = "{$theme->path}/theme.json";
        if (!File::exists($themeJsonPath)) {
            return ValidationResult::failed(['theme.json is missing']);
        }

        if (!is_readable($themeJsonPath)) {
            return ValidationResult::failed(['theme.json is not readable']);
        }

        // Parse theme.json
        $themeData = json_decode(File::get($themeJsonPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ValidationResult::failed(['theme.json contains invalid JSON: ' . json_last_error_msg()]);
        }

        // Validate required fields
        foreach ($this->requiredFields as $field) {
            if (empty($themeData[$field])) {
                $errors[] = "Missing required field in theme.json: {$field}";
            }
        }

        // Check compatibility
        $compatResult = $this->checkCompatibility($themeData);
        $errors = array_merge($errors, $compatResult->errors);
        $warnings = array_merge($warnings, $compatResult->warnings);

        // Check if theme is prebuilt and has manifest with valid asset references
        $isPrebuilt = $themeData['compatibility']['prebuilt'] ?? true;
        if ($isPrebuilt) {
            $manifestPath = "{$theme->path}/public/build/manifest.json";
            if (!File::exists($manifestPath)) {
                $errors[] = 'Theme is marked as prebuilt but public/build/manifest.json is missing. Run "npm run build" in the theme directory.';
            } else {
                // Verify files referenced in manifest actually exist
                $manifestErrors = $this->validateManifestFiles($theme->path, $manifestPath);
                $errors = array_merge($errors, $manifestErrors);
            }
        }

        // Check asset symlinks
        $publicThemePath = public_path("themes/{$theme->slug}");
        if (!File::exists($publicThemePath)) {
            $warnings[] = 'Theme assets not published - will be created on activation';
        }

        // Scan for forbidden files
        $forbiddenFiles = $this->scanForForbiddenFiles($theme->path);
        if (!empty($forbiddenFiles)) {
            foreach ($forbiddenFiles as $file) {
                $errors[] = "Forbidden file found: {$file}";
            }
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings,
            $themeData
        );
    }

    /**
     * Validate a ZIP file before extraction (Phase 2)
     */
    public function validateZip(string $zipPath): ValidationResult
    {
        $errors = [];
        $warnings = [];
        $themeData = [];

        if (!File::exists($zipPath)) {
            return ValidationResult::failed(['ZIP file not found']);
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($zipPath);

        if ($openResult !== true) {
            return ValidationResult::failed(['Unable to open ZIP file: error code ' . $openResult]);
        }

        try {
            // Check ZIP size (prevent ZIP bombs)
            $totalSize = 0;
            $fileCount = 0;
            $maxSize = 100 * 1024 * 1024; // 100MB uncompressed limit
            $maxFiles = 5000;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $totalSize += $stat['size'];
                $fileCount++;

                if ($totalSize > $maxSize) {
                    $errors[] = 'ZIP file exceeds maximum uncompressed size (100MB)';
                    break;
                }

                if ($fileCount > $maxFiles) {
                    $errors[] = 'ZIP file contains too many files (max 5000)';
                    break;
                }
            }

            // Find and validate theme.json
            $themeJsonIndex = $this->findThemeJsonInZip($zip);
            if ($themeJsonIndex === null) {
                $errors[] = 'theme.json not found in ZIP archive';
            } else {
                $themeJsonContent = $zip->getFromIndex($themeJsonIndex);
                $themeData = json_decode($themeJsonContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = 'theme.json contains invalid JSON: ' . json_last_error_msg();
                } else {
                    // Validate required fields
                    foreach ($this->requiredFields as $field) {
                        if (empty($themeData[$field])) {
                            $errors[] = "Missing required field in theme.json: {$field}";
                        }
                    }

                    // Validate slug format (prevent path traversal via slug)
                    if (!empty($themeData['slug'])) {
                        if (!preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]$/', $themeData['slug'])) {
                            $errors[] = 'Slug must contain only lowercase letters, numbers, and hyphens (cannot start or end with hyphen)';
                        }
                        if (strlen($themeData['slug']) > 64) {
                            $errors[] = 'Slug must be 64 characters or less';
                        }
                    }

                    // Check compatibility
                    $compatResult = $this->checkCompatibility($themeData);
                    $errors = array_merge($errors, $compatResult->errors);
                    $warnings = array_merge($warnings, $compatResult->warnings);
                }
            }

            // Scan for forbidden files
            $forbiddenInZip = $this->scanZipForForbiddenFiles($zip);
            foreach ($forbiddenInZip as $file) {
                $errors[] = "Forbidden file in ZIP: {$file}";
            }

            // Check for path traversal attempts with comprehensive detection
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($this->hasPathTraversal($name)) {
                    $errors[] = "Invalid path in ZIP (potential path traversal): {$name}";
                }
            }

        } finally {
            $zip->close();
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings,
            $themeData
        );
    }

    /**
     * Validate an extracted theme directory
     */
    public function validateDirectory(string $path): ValidationResult
    {
        $errors = [];
        $warnings = [];
        $themeData = [];

        if (!File::exists($path)) {
            return ValidationResult::failed(['Directory does not exist']);
        }

        // Check required files
        foreach ($this->requiredFiles as $file) {
            if (!File::exists("{$path}/{$file}")) {
                $errors[] = "Missing required file: {$file}";
            }
        }

        // Validate theme.json
        $themeJsonPath = "{$path}/theme.json";
        if (File::exists($themeJsonPath)) {
            $themeData = json_decode(File::get($themeJsonPath), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'Invalid JSON in theme.json: ' . json_last_error_msg();
            } else {
                // Validate required fields
                foreach ($this->requiredFields as $field) {
                    if (empty($themeData[$field])) {
                        $errors[] = "Missing required field: {$field}";
                    }
                }

                // Validate slug format (prevent path traversal via slug)
                if (!empty($themeData['slug'])) {
                    if (!preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]$/', $themeData['slug'])) {
                        $errors[] = 'Slug must contain only lowercase letters, numbers, and hyphens (cannot start or end with hyphen)';
                    }
                    if (strlen($themeData['slug']) > 64) {
                        $errors[] = 'Slug must be 64 characters or less';
                    }
                }

                // Check compatibility
                $compatResult = $this->checkCompatibility($themeData);
                $errors = array_merge($errors, $compatResult->errors);
                $warnings = array_merge($warnings, $compatResult->warnings);
            }
        }

        // Scan for forbidden files
        $forbiddenFiles = $this->scanForForbiddenFiles($path);
        foreach ($forbiddenFiles as $file) {
            $errors[] = "Forbidden file found: {$file}";
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings,
            $themeData
        );
    }

    /**
     * Get TallCMS version from config (single source of truth)
     */
    public static function getTallcmsVersion(): string
    {
        return config('tallcms.version', '1.0.0');
    }

    /**
     * Check compatibility requirements
     */
    public function checkCompatibility(array $themeData): ValidationResult
    {
        $errors = [];
        $warnings = [];

        $compatibility = $themeData['compatibility'] ?? [];

        // Check PHP version
        if (!empty($compatibility['php'])) {
            $requiredPhp = $compatibility['php'];
            $currentPhp = PHP_VERSION;

            if (!$this->versionSatisfies($currentPhp, $requiredPhp)) {
                $errors[] = "Theme requires PHP {$requiredPhp}, current version is {$currentPhp}";
            }
        }

        // Check PHP extensions
        if (!empty($compatibility['extensions'])) {
            foreach ($compatibility['extensions'] as $extension) {
                if (!extension_loaded($extension)) {
                    $errors[] = "Theme requires PHP extension: {$extension}";
                }
            }
        }

        // Check TallCMS version - this is now an error if incompatible
        if (!empty($compatibility['tallcms']) && $compatibility['tallcms'] !== '*') {
            $requiredTallcms = $compatibility['tallcms'];
            $currentTallcms = self::getTallcmsVersion();
            if (!$this->versionSatisfies($currentTallcms, $requiredTallcms)) {
                $errors[] = "Theme requires TallCMS {$requiredTallcms}, current version is {$currentTallcms}";
            }
        }

        // Check prebuilt requirement in production
        $isPrebuilt = $compatibility['prebuilt'] ?? true;
        if (!$isPrebuilt && app()->environment('production')) {
            $errors[] = 'Source themes (requiring build) cannot be installed in production environment';
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings,
            []
        );
    }

    /**
     * Scan directory for forbidden files
     */
    protected function scanForForbiddenFiles(string $path): array
    {
        $forbidden = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                $relativePath = str_replace($path . '/', '', $file->getPathname());

                // Skip node_modules and vendor directories
                if (str_contains($relativePath, 'node_modules/') || str_contains($relativePath, 'vendor/')) {
                    continue;
                }

                // Check against forbidden patterns
                if ($this->matchesForbiddenPattern($filename, $relativePath)) {
                    $forbidden[] = $relativePath;
                }
            }
        }

        return $forbidden;
    }

    /**
     * Scan ZIP for forbidden files
     */
    protected function scanZipForForbiddenFiles(ZipArchive $zip): array
    {
        $forbidden = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            // Skip directories
            if (str_ends_with($name, '/')) {
                continue;
            }

            // Skip node_modules and vendor
            if (str_contains($name, 'node_modules/') || str_contains($name, 'vendor/')) {
                continue;
            }

            $filename = basename($name);
            if ($this->matchesForbiddenPattern($filename, $name)) {
                $forbidden[] = $name;
            }
        }

        return $forbidden;
    }

    /**
     * Check if a filename matches forbidden patterns
     */
    protected function matchesForbiddenPattern(string $filename, string $path): bool
    {
        // Special case: allow blade.php files
        if (str_ends_with($path, '.blade.php')) {
            return false;
        }

        // Use stricter PHP detection (catches double extensions like foo.php.txt)
        if ($this->isPotentialPhpFile($filename)) {
            return true;
        }

        foreach ($this->forbiddenPatterns as $pattern) {
            // Skip PHP patterns as they're handled by isPotentialPhpFile
            if (str_contains($pattern, 'php') || str_contains($pattern, 'phar')) {
                continue;
            }

            // Handle wildcard patterns
            if (str_starts_with($pattern, '*.')) {
                $extension = substr($pattern, 1);
                if (str_ends_with($filename, $extension)) {
                    return true;
                }
            } elseif (str_ends_with($pattern, '.*')) {
                $prefix = substr($pattern, 0, -2);
                if (str_starts_with($filename, $prefix)) {
                    return true;
                }
            } elseif ($filename === $pattern) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find theme.json in ZIP (may be in root or in a subdirectory)
     */
    protected function findThemeJsonInZip(ZipArchive $zip): ?int
    {
        // First, look for theme.json in root
        $index = $zip->locateName('theme.json');
        if ($index !== false) {
            return $index;
        }

        // Look for theme.json in a single subdirectory (common pattern)
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/^[^\/]+\/theme\.json$/', $name)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Check if current version satisfies requirement
     */
    protected function versionSatisfies(string $current, string $requirement): bool
    {
        // Handle caret (^) version constraints
        if (str_starts_with($requirement, '^')) {
            $minVersion = substr($requirement, 1);
            $parts = explode('.', $minVersion);
            $major = $parts[0];

            // ^X.Y.Z means >= X.Y.Z and < (X+1).0.0
            return version_compare($current, $minVersion, '>=')
                && version_compare($current, ($major + 1) . '.0.0', '<');
        }

        // Handle tilde (~) version constraints
        if (str_starts_with($requirement, '~')) {
            $minVersion = substr($requirement, 1);
            $parts = explode('.', $minVersion);

            if (count($parts) >= 2) {
                $major = $parts[0];
                $minor = $parts[1];
                // ~X.Y.Z means >= X.Y.Z and < X.(Y+1).0
                return version_compare($current, $minVersion, '>=')
                    && version_compare($current, "{$major}." . ($minor + 1) . '.0', '<');
            }
        }

        // Handle greater than or equal (>=)
        if (str_starts_with($requirement, '>=')) {
            return version_compare($current, substr($requirement, 2), '>=');
        }

        // Handle wildcard (*)
        if ($requirement === '*') {
            return true;
        }

        // Default to exact or >= comparison
        return version_compare($current, $requirement, '>=');
    }

    /**
     * Expected entry points in a theme manifest
     */
    protected array $expectedManifestEntries = [
        'resources/css/app.css',
        'resources/js/app.js',
    ];

    /**
     * Validate that files referenced in manifest actually exist
     */
    protected function validateManifestFiles(string $themePath, string $manifestPath): array
    {
        $errors = [];
        $warnings = [];

        try {
            $content = File::get($manifestPath);
            $manifest = json_decode($content, true);

            // Check for valid JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['Manifest file contains invalid JSON: ' . json_last_error_msg()];
            }

            // Check manifest is an associative array (not sequential)
            if (!is_array($manifest) || (count($manifest) > 0 && array_keys($manifest) === range(0, count($manifest) - 1))) {
                return ['Manifest must be an associative array with entry keys'];
            }

            $buildPath = dirname($manifestPath);

            // Check for expected entry points
            foreach ($this->expectedManifestEntries as $expectedEntry) {
                if (!isset($manifest[$expectedEntry])) {
                    $warnings[] = "Manifest missing expected entry: {$expectedEntry}";
                }
            }

            foreach ($manifest as $entry => $info) {
                // Validate entry structure
                if (!is_array($info)) {
                    $errors[] = "Manifest entry '{$entry}' must be an object";
                    continue;
                }

                // Check 'file' key exists and file exists
                if (!isset($info['file'])) {
                    $errors[] = "Manifest entry '{$entry}' missing 'file' key";
                    continue;
                }

                $filePath = $buildPath . '/' . $info['file'];
                if (!File::exists($filePath)) {
                    $errors[] = "Manifest references missing file: {$info['file']}";
                }

                // Check CSS imports exist
                if (isset($info['css']) && is_array($info['css'])) {
                    foreach ($info['css'] as $cssFile) {
                        $cssPath = $buildPath . '/' . $cssFile;
                        if (!File::exists($cssPath)) {
                            $errors[] = "Manifest references missing CSS file: {$cssFile}";
                        }
                    }
                }

                // Validate imports reference existing manifest keys
                if (isset($info['imports']) && is_array($info['imports'])) {
                    foreach ($info['imports'] as $importKey) {
                        if (!isset($manifest[$importKey])) {
                            $errors[] = "Manifest entry '{$entry}' references non-existent import: {$importKey}";
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'Unable to validate manifest: ' . $e->getMessage();
        }

        return $errors;
    }

    /**
     * Check for path traversal attempts with comprehensive detection
     */
    protected function hasPathTraversal(string $path): bool
    {
        // Decode URL-encoded characters
        $decoded = urldecode($path);

        // Check for various traversal patterns
        $patterns = [
            '..',           // Direct parent reference
            '//',           // Double slash
            '\\',           // Backslash (Windows-style)
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($decoded, $pattern)) {
                return true;
            }
        }

        // Check for absolute paths
        if (str_starts_with($decoded, '/') || preg_match('/^[a-zA-Z]:/', $decoded)) {
            return true;
        }

        // Normalize and check if path escapes root
        $normalized = $this->normalizePath($decoded);
        if (str_starts_with($normalized, '../') || $normalized === '..') {
            return true;
        }

        return false;
    }

    /**
     * Normalize a path by resolving . and .. components
     */
    protected function normalizePath(string $path): string
    {
        $parts = [];
        $segments = explode('/', str_replace('\\', '/', $path));

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if (empty($parts)) {
                    // Attempting to go above root
                    return '../';
                }
                array_pop($parts);
            } else {
                $parts[] = $segment;
            }
        }

        return implode('/', $parts);
    }

    /**
     * Check if filename could be executable PHP (stricter detection)
     */
    protected function isPotentialPhpFile(string $filename): bool
    {
        $lowername = strtolower($filename);

        // Check for PHP extensions anywhere in filename (catches foo.php.txt)
        $phpExtensions = ['.php', '.phtml', '.php3', '.php4', '.php5', '.phar', '.phps'];
        foreach ($phpExtensions as $ext) {
            if (str_contains($lowername, $ext)) {
                // Exception: .blade.php is allowed
                if ($ext === '.php' && str_ends_with($lowername, '.blade.php')) {
                    continue;
                }
                return true;
            }
        }

        return false;
    }
}

/**
 * Validation result value object
 */
class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors,
        public array $warnings,
        public array $themeData
    ) {}

    public static function failed(array $errors): self
    {
        return new self(false, $errors, [], []);
    }

    public static function success(array $themeData = [], array $warnings = []): self
    {
        return new self(true, [], $warnings, $themeData);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
}
