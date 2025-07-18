<?php

namespace LCSEngine\Schemas\Model\Aggregates;

use Illuminate\Support\Collection;

class Aggregate
{
    protected string $name;

    protected Collection $groupBy; // Collection of GroupByField objects

    protected Collection $columns;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->groupBy = collect();
        $this->columns = collect();
    }

    public function addColumn(Column $column): void
    {
        $this->columns->push($column);
    }

    public function addGroupBy(GroupByField $field): void
    {
        $this->groupBy->push($field);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGroupBy(): Collection
    {
        return $this->groupBy;
    }

    public function getColumns(): Collection
    {
        return $this->columns;
    }

    public static function fromArray(string $name, array $data): self
    {
        $aggregate = new self($name);

        if (! empty($data['groupBy']) && is_array($data['groupBy'])) {
            foreach ($data['groupBy'] as $field) {
                $aggregate->addGroupBy(GroupByField::fromArray($field));
            }
        }

        if (! empty($data['columns']) && is_array($data['columns'])) {
            foreach ($data['columns'] as $columnData) {
                $aggregate->addColumn(Column::fromArray($columnData));
            }
        }

        return $aggregate;
    }

    public function toArray(): array
    {
        return [
            'groupBy' => $this->groupBy->map(fn (GroupByField $field) => $field->toArray())->all(),
            'columns' => $this->columns->map(fn (Column $column) => $column->toArray())->all(),
        ];
    }
}
