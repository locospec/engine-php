<?php

namespace Locospec\EnginePhp\Models;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;

class ModelRegistry
{
    private static ?ModelRegistry $instance = null;

    private array $models = [];

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function register(ModelDefinition $model): void
    {
        $name = $model->getName();
        if (isset($this->models[$name])) {
            throw new InvalidArgumentException("Model {$name} is already registered");
        }
        $this->models[$name] = $model;
    }

    public function get(string $name): ?ModelDefinition
    {
        return $this->models[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->models[$name]);
    }

    public function all(): array
    {
        return $this->models;
    }

    public function clear(): void
    {
        $this->models = [];
    }
}
