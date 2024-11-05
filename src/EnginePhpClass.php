<?php

namespace Locospec\EnginePhp;

use Locospec\EnginePhp\Registry\RegistryManager;
use Locospec\EnginePhp\Specifications\SpecificationProcessor;

class EnginePhpClass
{
    private RegistryManager $registryManager;

    private SpecificationProcessor $specProcessor;

    public function __construct()
    {
        $this->registryManager = new RegistryManager;
        $this->specProcessor = new SpecificationProcessor($this->registryManager);
    }

    /**
     * Process specifications from a JSON file path
     */
    public function processSpecificationFile(string $filePath): void
    {
        $this->specProcessor->processFile($filePath);
    }

    /**
     * Process specifications directly from a JSON string
     */
    public function processSpecificationJson(string $json): void
    {
        $this->specProcessor->processJson($json);
    }

    /**
     * Get the registry manager instance
     */
    public function getRegistryManager(): RegistryManager
    {
        return $this->registryManager;
    }
}
