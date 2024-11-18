<?php

namespace Locospec\LCS\Query;

use Locospec\LCS\Exceptions\InvalidArgumentException;

class OffsetPagination implements Pagination
{
    private int $page;

    private int $perPage;

    private int $maxPerPage = 100;

    public function __construct(int $page = 1, int $perPage = 15)
    {
        $this->page = $page;
        $this->perPage = $perPage;
        $this->validate();
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getLimit(): int
    {
        return $this->perPage;
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    // Implementing cursor-related methods as null since this is offset pagination
    public function getCursor(): ?string
    {
        return null;
    }

    public function getCursorColumn(): ?string
    {
        return null;
    }

    public function validate(): void
    {
        if ($this->page < 1) {
            throw new InvalidArgumentException('Page number must be greater than 0');
        }

        if ($this->perPage < 1) {
            throw new InvalidArgumentException('Items per page must be greater than 0');
        }

        if ($this->perPage > $this->maxPerPage) {
            throw new InvalidArgumentException(
                "Items per page cannot exceed {$this->maxPerPage}"
            );
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['page'] ?? 1,
            $data['per_page'] ?? 15
        );
    }

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }
}
