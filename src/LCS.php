<?php

namespace Locospec\LCS;

use Locospec\LCS\Registry\RegistryManager;
use Locospec\LCS\Specifications\SpecificationProcessor;
use Locospec\LCS\Database\DatabaseOperatorInterface;
use Locospec\LCS\Exceptions\InvalidArgumentException;

class LCS
{
    private static ?RegistryManager $globalRegistryManager = null;

    private static ?DatabaseOperatorInterface $databaseOperator = null;

    private static bool $isInitialized = false;

    private SpecificationProcessor $specProcessor;

    /**
     * Bootstrap LCS with initial configuration
     * This should be called only once during application startup
     */
    public static function bootstrap(array $config = []): void
    {
        if (self::$isInitialized) {
            return;
        }

        self::$globalRegistryManager = new RegistryManager;
        self::$isInitialized = true;

        if (isset($config['paths'])) {
            self::loadSpecifications($config['paths']);
        }
    }

    /**
     * Load specifications from given paths
     */
    public static function loadSpecifications(array $paths): void
    {
        if (!self::$isInitialized) {
            throw new \RuntimeException('LCS must be bootstrapped before loading specifications');
        }

        $specProcessor = new SpecificationProcessor(self::$globalRegistryManager);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                foreach (glob($path . '/*.json') as $file) {
                    $specProcessor->processFile($file);
                }
            } elseif (is_file($path)) {
                $specProcessor->processFile($path);
            }
        }
    }

    /**
     * Register a database operator implementation
     */
    public static function registerDatabaseOperator(DatabaseOperatorInterface $operator): void
    {
        self::$databaseOperator = $operator;
    }

    /**
     * Get the registered database operator
     */
    public static function getDatabaseOperator(): DatabaseOperatorInterface
    {
        if (!self::$databaseOperator) {
            throw new InvalidArgumentException('No database operator registered. Call registerDatabaseOperator first.');
        }

        return self::$databaseOperator;
    }

    /**
     * Check if a database operator is registered
     */
    public static function hasDatabaseOperator(): bool
    {
        return self::$databaseOperator !== null;
    }

    /**
     * Constructor now just provides access to the global registry
     */
    public function __construct()
    {
        if (!self::$isInitialized) {
            throw new \RuntimeException('LCS must be bootstrapped before instantiation');
        }

        $this->specProcessor = new SpecificationProcessor(self::$globalRegistryManager);
    }

    /**
     * Get the global registry manager instance
     */
    public function getRegistryManager(): RegistryManager
    {
        return self::$globalRegistryManager;
    }

    /**
     * Process a specification from file
     */
    public function processSpecificationFile(string $path): void
    {
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
        self::$globalRegistryManager = null;
        self::$databaseOperator = null;
        self::$isInitialized = false;
    }
}
