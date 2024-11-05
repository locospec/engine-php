<?php

namespace Locospec\EnginePhp\Specifications;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;
use Locospec\EnginePhp\Parsers\ParserFactory;
use Locospec\EnginePhp\Registry\RegistryManager;

class SpecificationProcessor
{
    private RegistryManager $registryManager;

    private ParserFactory $parserFactory;

    public function __construct(RegistryManager $registryManager)
    {
        $this->registryManager = $registryManager;
        $this->parserFactory = new ParserFactory;
    }

    /**
     * Process specifications from a file path
     *
     * @throws InvalidArgumentException If file doesn't exist
     */
    public function processFile(string $filePath): void
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("Specification file not found: {$filePath}");
        }

        $json = file_get_contents($filePath);
        $this->processJson($json);
    }

    /**
     * Process specifications from a JSON string
     *
     * @throws InvalidArgumentException If JSON is invalid
     */
    public function processJson(string $json): void
    {
        $data = $this->parseJson($json);
        $specs = $this->normalizeSpecifications($data);

        foreach ($specs as $spec) {
            $this->processSingleSpec($spec);
        }
    }

    private function parseJson(string $json): array
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON provided: '.json_last_error_msg());
        }

        return $data;
    }

    private function normalizeSpecifications(array $data): array
    {
        return is_array($data) && ! isset($data['type']) ? $data : [$data];
    }

    /**
     * Process a single specification
     *
     * @throws InvalidArgumentException If specification type is invalid or missing
     */
    private function processSingleSpec(array $spec): void
    {
        if (! isset($spec['type'])) {
            throw new InvalidArgumentException('Specification must include a type');
        }

        $type = $spec['type'];
        $parser = $this->parserFactory->createParser($type);
        $item = $parser->parseArray($spec);

        $this->registryManager->register($type, $item);
    }
}
