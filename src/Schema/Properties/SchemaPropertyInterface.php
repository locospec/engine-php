<?php

namespace Locospec\EnginePhp\Schema\Properties;

use Locospec\EnginePhp\Schema\Schema;

interface SchemaPropertyInterface
{
    public function getType(): string;
    public function toArray(): array;
    public function setSchema(Schema $schema): self;
    public function getSchema(): ?Schema;
}
