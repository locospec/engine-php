<?php

namespace LCSEngine\Attributes;

interface AttributeInterface
{
    public function getName(): string;

    public function getType(): string;

    public function getLabel(): string;

    public function getOptions(): array;

    public function getGenerations(): array;

    public function getValidations(): array;

    public function toArray(): array;

    public function toObject(): object;
}
