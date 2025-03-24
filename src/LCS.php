<?php

namespace Locospec\Engine;

use Locospec\Engine\Registry\RegistryManager;
use Locospec\Engine\Specifications\SpecificationProcessor;

class LCS
{
    private static ?RegistryManager $globalRegistryManager = null;

    private static bool $isInitialized = false;

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
                self::$logger = new Logger($config['logging']['file_path'], $config['logging']['retention_days']);
                self::$logger->info('Initializing LCS...');
            }
            self::$globalRegistryManager = new RegistryManager;
            self::$isInitialized = true;

            if (isset($config['paths'])) {
                // register specification
                self::registerSpecifications($config['paths']);
            }

            self::$logger->info('LCS successfully bootstrapped.');
        } catch (Exception $e) {
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
            if (!self::$isInitialized) {
                throw new \RuntimeException('LCS must be bootstrapped before loading specifications');
            }

            $specProcessor = new SpecificationProcessor(self::$globalRegistryManager);

            self::$logger->info('Looping all the JSON Spec for registration');
            foreach ($paths as $path) {
                if (is_dir($path)) {
                    foreach (glob($path . '/*.json') as $file) {
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
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Constructor now just provides access to the global registry
     */
    public function __construct()
    {
        if (!self::$isInitialized) {
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
}
