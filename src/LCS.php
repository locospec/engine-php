<?php

namespace Locospec\Engine;

use Locospec\Engine\Registry\RegistryManager;
use Locospec\Engine\Specifications\SpecificationProcessor;

class LCS
{
    private static ?RegistryManager $globalRegistryManager = null;

    private static bool $isInitialized = false;

    private SpecificationProcessor $specProcessor;

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

            self::$globalRegistryManager = new RegistryManager;
            self::$isInitialized = true;

            if (isset($config['paths'])) {
                // TODO: Call SpecificationProcessor right here
                // SpecificationProcessor::process
                // Let it handle looping etc.,
                self::loadSpecifications($config['paths']);
                // Todo: change name to registerSpecification
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Load specifications from given paths
     */
    public static function loadSpecifications(array $paths): void
    {
        try {
            if (! self::$isInitialized) {
                throw new \RuntimeException('LCS must be bootstrapped before loading specifications');
            }

            $specProcessor = new SpecificationProcessor(self::$globalRegistryManager);

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
            $specProcessor->processAllPendingRelationships();
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
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

        $this->specProcessor = new SpecificationProcessor(self::$globalRegistryManager);
    }

    /**
     * Get the global registry manager instance
     */
    public function getRegistryManager(): RegistryManager
    {
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
        self::$isInitialized = false;
    }
}
