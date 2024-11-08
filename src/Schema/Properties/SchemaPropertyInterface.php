<?php

namespace Locospec\LCS\Schema\Properties;

use Locospec\LCS\Schema\Schema;

interface SchemaPropertyInterface
{
    public function getType(): string;

    public function toArray(): array;

    public function setSchema(Schema $schema): self;

    public function getSchema(): ?Schema;
}
