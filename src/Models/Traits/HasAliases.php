<?php

namespace Locospec\Engine\Models\Traits;

trait HasAliases
{
    private object $aliases;

    public function addAlias(string $key, string|object $expression): void
    {
        if (is_string($expression)) {
            $this->aliases->$key = $expression;
        } else {
            $this->aliases->$key = $expression;
        }
    }

    public function getAlias(string $key): ?object
    {
        return $this->aliases->$key ?? null;
    }

    public function getAliases(): object
    {
        return $this->aliases;
    }

    protected function loadAliasesFromArray(object $data): void
    {
        if (! isset($this->aliases)) {
            $this->aliases = new \stdClass; // Initialize aliases as an object
        }

        if (isset($data->aliases)) {
            foreach ($data->aliases as $key => $expression) {
                $this->addAlias($key, $expression);
            }
        }
    }

    protected function aliasesToArray(): array
    {
        return ! empty($this->aliases) ? ['aliases' => $this->aliases] : [];
    }
}
