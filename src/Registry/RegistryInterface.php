<?php

namespace LCSEngine\Registry;

interface RegistryInterface
{
    public function register(mixed $item): void;

    public function get(string $name): mixed;

    public function has(string $name): bool;

    public function all(): array;

    public function clear(): void;

    public function getType(): string;

    public function getDefaultDriver();

    public function setDefaultDriver(string $driverName);

    public function getDefaultGenerator();

    public function setDefaultGenerator(string $generatorName);

    public function getDefaultValidator();

    public function setDefaultValidator(string $validatorName);
}
