<?php

namespace Locospec\Engine\Attributes;

interface AttributeInterface
{
    public function getName(): string;
    public function getType(): string;
    public function getLabel(): string;
    public function toArray(): array;
    public function toObject(): object;
}
