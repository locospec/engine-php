<?php

namespace Locospec\LCS\Registry;

interface RegistryInterface
{
    public function register(mixed $item): void;

    public function get(string $name): mixed;

    public function has(string $name): bool;

    public function all(): array;

    public function clear(): void;

    public function getType(): string;
}
