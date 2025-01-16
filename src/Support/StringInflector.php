<?php

namespace Locospec\Engine\Support;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;

class StringInflector
{
    private static ?StringInflector $instance = null;

    private Inflector $inflector;

    private array $cache = [];

    private function __construct()
    {
        $this->inflector = InflectorFactory::create()->build();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function plural(string $value): string
    {
        $cacheKey = "plural_{$value}";

        return $this->cache[$cacheKey] ??= $this->inflector->pluralize($value);
    }

    public function singular(string $value): string
    {
        $cacheKey = "singular_{$value}";

        return $this->cache[$cacheKey] ??= $this->inflector->singularize($value);
    }

    public function camel(string $value): string
    {
        $cacheKey = "camel_{$value}";

        return $this->cache[$cacheKey] ??= $this->inflector->camelize($value);
    }

    public function snake(string $value): string
    {
        $cacheKey = "snake_{$value}";

        return $this->cache[$cacheKey] ??= mb_strtolower(
            preg_replace('/[A-Z]/', '_\\0', lcfirst($this->camel($value)))
        );
    }

    public function studly(string $value): string
    {
        $cacheKey = "studly_{$value}";

        return $this->cache[$cacheKey] ??= str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }
}
