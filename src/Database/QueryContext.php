<?php

namespace Locospec\Engine\Database;

use Locospec\Engine\Exceptions\InvalidArgumentException;

class QueryContext
{
    private array $data = [];

    public function __construct(array $initialData = [])
    {
        foreach ($initialData as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function set(string $path, mixed $value): void
    {
        $segments = $this->parsePath($path);
        $current = &$this->data;

        // Navigate through path segments
        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $current[$segment] = $value;
                break;
            }

            if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }
    }

    public function get(string $path, mixed $default = null): mixed
    {
        $segments = $this->parsePath($path);
        $current = $this->data;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    public function has(string $path): bool
    {
        $segments = $this->parsePath($path);
        $current = $this->data;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return false;
            }
            $current = $current[$segment];
        }

        return true;
    }

    public function remove(string $path): void
    {
        $segments = $this->parsePath($path);
        $current = &$this->data;

        // Navigate to parent of target
        for ($i = 0; $i < count($segments) - 1; $i++) {
            if (! isset($current[$segments[$i]]) || ! is_array($current[$segments[$i]])) {
                return;
            }
            $current = &$current[$segments[$i]];
        }

        // Remove the target key
        unset($current[end($segments)]);
    }

    public function all(): array
    {
        return $this->data;
    }

    public function resolveValue(mixed $value): mixed
    {
        if (! is_string($value) || ! str_starts_with($value, '$.')) {
            return $value;
        }

        $path = substr($value, 2); // Remove the $. prefix
        if (! $this->has($path)) {
            throw new InvalidArgumentException("Context variable not found: {$value}");
        }

        return $this->get($path);
    }

    private function parsePath(string $path): array
    {
        if (empty($path)) {
            throw new InvalidArgumentException('Path cannot be empty');
        }

        // Remove $. prefix if present
        if (str_starts_with($path, '$.')) {
            $path = substr($path, 2);
        }

        return explode('.', $path);
    }

    public function merge(QueryContext $other): self
    {
        $newContext = new self($this->data);

        foreach ($other->all() as $key => $value) {
            $newContext->set($key, $value);
        }

        return $newContext;
    }

    public static function create(array $data = []): self
    {
        return new self($data);
    }
}
