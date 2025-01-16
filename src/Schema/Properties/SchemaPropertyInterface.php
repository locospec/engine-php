<?php

namespace Locospec\Engine\Schema\Properties;

use Locospec\Engine\Schema\Schema;

interface SchemaPropertyInterface
{
    public function getType(): string;

    public function toArray(): array;

    public function setSchema(Schema $schema): self;

    public function getSchema(): ?Schema;
}
