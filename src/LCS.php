<?php

namespace Locospec\Engine;

use Locospec\Engine\Registry\RegistryManager;
use Locospec\Engine\Specifications\SpecificationProcessor;

class LCS
{
    private static ?RegistryManager $globalRegistryManager = null;

    private static bool $isInitialized = false;

    private static string $cacheFile; // 'registry_cache.php';

    private SpecificationProcessor $specProcessor;

    private static ?Logger $logger = null;

    /**
     * Bootstrap LCS with initial configuration
     * This should be called only once during application startup
     */
    public static function bootstrap(array $config = []): void
    {
        // $config should be proper class with validation

        try {
            if (self::$isInitialized) {
                return;
            }

            if (isset($config['logging']['file_path'])) {
                self::$logger = new Logger($config['logging']['file_path'], $config['logging']['retention_days'], $config['logging']['query_logs']);
                self::$logger->info('Initializing LCS...');
            }

            // Set the cache file path from config
            if (isset($config['cache_path'])) {
                // Ensure the cache path doesn't end with a directory separator.
                self::$cacheFile = rtrim($config['cache_path'], DIRECTORY_SEPARATOR)
                    .DIRECTORY_SEPARATOR.'locospec_registry_cache.php';
            } else {
                // Fallback to a default path within the package directory.
                self::$cacheFile = __DIR__ . '/locospec_registry_cache.php';
            }

            // self::$globalRegistryManager = new RegistryManager;
            self::$isInitialized = true;

            if (isset($config['paths'])) {
                // register specification
                self::registerSpecifications($config['paths']);
            }

            self::$logger->info('LCS successfully bootstrapped.');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Register specifications from given paths
     */
    public static function registerSpecifications(array $paths): void
    {
        try {
            self::$logger->info('Specification registration started');
            if (! self::$isInitialized) {
                throw new \RuntimeException('LCS must be bootstrapped before loading specifications');
            }

            // Try to load from cache if specs haven't changed
            if (! self::needsReprocessing($paths) && self::loadFromCache()) {
                self::$logger->info('Using cached registry');

                return;
            }

            // Create new registry manager if needed
            if (! self::$globalRegistryManager) {
                self::$globalRegistryManager = new RegistryManager;
            }

            $specProcessor = new SpecificationProcessor(self::$globalRegistryManager);

            self::$logger->info('Looping all the JSON Spec for registration');
            foreach ($paths as $path) {
                if (is_dir($path)) {
                    foreach (glob($path.'/*.json') as $file) {
                        $specProcessor->processFile($file);
                    }
                } elseif (is_file($path)) {
                    $specProcessor->processFile($path);
                }
            }

            // Process all relationships after all models are registered
            $specProcessor->processRelationships();
            self::$logger->info('Specification registration finished');

            // Process all views after all models and relationships are registered
            $specProcessor->processAllViewSpec();
            self::$logger->info('Views registration finished');

            // Process all mutator after all views models and relationships are registered
            $specProcessor->processAllMutatorsSpec();
            self::$logger->info('Mutators registration finished');

            self::saveToCache();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Constructor now just provides access to the global registry
     */
    public function __construct()
    {
        if (! self::$isInitialized) {
            throw new \RuntimeException('LCS must be bootstrapped before instantiation');
        }

        self::$logger?->info('LCS instance created');

        $this->specProcessor = new SpecificationProcessor(self::$globalRegistryManager);
    }

    /**
     * Get the global registry manager instance
     */
    public function getRegistryManager(): RegistryManager
    {
        self::$logger?->info('Fetching global RegistryManager');

        return self::$globalRegistryManager;
    }

    public function getDefaultDriverOfType($type)
    {
        return self::$globalRegistryManager->getRegistry($type)->getDefaultDriver();
    }

    /**
     * Process a specification from file
     */
    public function processSpecificationFile(string $path): void
    {
        self::$logger?->info("Processing specification file: {$path}");
        $this->specProcessor->processFile($path);
    }

    /**
     * Process a specification from JSON string
     */
    public function processSpecificationJson(string $json): void
    {
        $this->specProcessor->processJson($json);
    }

    /**
     * Check if LCS has been bootstrapped
     */
    public static function isInitialized(): bool
    {
        return self::$isInitialized;
    }

    /**
     * Reset LCS (mainly for testing purposes)
     */
    public static function reset(): void
    {
        self::$logger?->warning('Resetting LCS system');
        self::$globalRegistryManager = null;
        self::$isInitialized = false;
    }

    /**
     * Retrieve the Logger instance.
     */
    public static function getLogger(): ?Logger
    {
        return self::$logger;
    }

    /**
     * Save registry to cache
     */
    private static function saveToCache(): void
    {
        try {
            self::$logger->info('Saving registry to cache');
            $cacheData = [
                'registry' => serialize(self::$globalRegistryManager),
                'timestamp' => time(),
            ];

            $cacheContent = '<?php return '.var_export($cacheData, true).';';
            file_put_contents(self::$cacheFile, $cacheContent);
            self::$logger->info('Registry saved to cache successfully');
        } catch (\Exception $e) {
            self::$logger->error('Failed to save cache', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Check if specs need reprocessing
     */
    private static function needsReprocessing(array $paths): bool
    {
        if (! file_exists(self::$cacheFile)) {
            self::$logger->info('Cache file not found, reprocessing required');

            return true;
        }

        $cacheTime = filemtime(self::$cacheFile);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                foreach (glob($path.'/*.json') as $file) {
                    if (filemtime($file) > $cacheTime) {
                        self::$logger->info('Spec file modified, reprocessing required', ['file' => $file]);

                        return true;
                    }
                }
            } elseif (is_file($path)) {
                if (filemtime($path) > $cacheTime) {
                    self::$logger->info('Spec file modified, reprocessing required', ['file' => $path]);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Load registry from cache
     */
    private static function loadFromCache(): bool
    {
        try {
            if (! file_exists(self::$cacheFile)) {
                return false;
            }

            self::$logger->info('Loading registry from cache');
            $cached = require self::$cacheFile;

            if (! $cached || ! isset($cached['registry'])) {
                return false;
            }

            self::$globalRegistryManager = unserialize($cached['registry']);
            self::$logger->info('Registry loaded from cache successfully');

            return true;
        } catch (\Exception $e) {
            self::$logger->error('Failed to load from cache', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Clear the cached registry file.
     *
     * @return bool True on success, false on failure.
     */
    public static function clearCache(): bool
    {
        if (file_exists(self::$cacheFile)) {
            self::$logger->info('Clearing registry cache');

            return unlink(self::$cacheFile);
        }

        return true;
    }

    /**
     * return the cached registry file.
     *
     * @return array Cached registry file on success, false on failure.
     */
    public static function checkCacheStatus(): array
    {
        self::$logger->info('Checking status for registry cache');
        if (file_exists(self::$cacheFile)) {
            return [
                'exists' => true,
                'modified' => filemtime(self::$cacheFile),
                'size' => filesize(self::$cacheFile),
            ];
        }

        return ['exists' => false];
    }
}
