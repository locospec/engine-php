<?php

namespace Locospec\EnginePhp\Parsers;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;

class ParserFactory
{
    private array $parsers = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    private function registerDefaults(): void
    {
        $this->registerParser('model', new ModelParser);
    }

    public function registerParser(string $type, ParserInterface $parser): void
    {
        $this->parsers[$type] = $parser;
    }

    public function createParser(string $type): ParserInterface
    {
        if (! isset($this->parsers[$type])) {
            throw new InvalidArgumentException("No parser registered for type: {$type}");
        }

        return $this->parsers[$type];
    }
}
