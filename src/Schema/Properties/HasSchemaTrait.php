<?php

namespace Locospec\Engine\Schema\Properties;

use Locospec\Engine\Schema\Schema;

trait HasSchemaTrait
{
    protected ?Schema $schema = null;

    public function setSchema(Schema $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    public function getSchema(): ?Schema
    {
        return $this->schema;
    }
}
