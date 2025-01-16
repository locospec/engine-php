<?php

namespace Locospec\Engine\Query;

class SortCollection
{
    /** @var Sort[] */
    private array $sorts = [];

    public function addSort(Sort $sort): self
    {
        $clone = clone $this;
        $clone->sorts[] = $sort;

        return $clone;
    }

    public function getSorts(): array
    {
        return $this->sorts;
    }

    public function validate(): void
    {
        foreach ($this->sorts as $sort) {
            $sort->validate();
        }
    }

    public static function fromArray(array $data): self
    {
        $collection = new self;

        foreach ($data as $sortData) {
            $collection = $collection->addSort(Sort::fromArray($sortData));
        }

        $collection->validate();

        return $collection;
    }

    public function toArray(): array
    {
        return array_map(fn (Sort $sort) => $sort->toArray(), $this->sorts);
    }
}
