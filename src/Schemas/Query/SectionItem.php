<?php

namespace LCSEngine\Schemas\Query;

use Illuminate\Support\Collection;

class SectionItem implements EntityLayoutItem
{
    private string $header;
    private Collection $columns;

    public function __construct(string $header)
    {
        $this->header = $header;
        $this->columns = new Collection();
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    public function addColumn(ColumnItem $column): void
    {
        $this->columns->push($column);
    }

    public function getColumns(): Collection
    {
        return $this->columns;
    }

    public function toArray(): array
    {
        $result = ['$' . $this->header];
        return array_merge($result, $this->columns->map(fn($column) => $column->toArray())->toArray());
    }
}
