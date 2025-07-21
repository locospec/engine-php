<?php

namespace LCSEngine\Schemas\DatabaseOperations;

class Pagination
{
    private PaginationType $type;

    private ?int $page = null;

    private int $perPage;

    private ?string $cursor = null;

    private function __construct(
        PaginationType $type,
        int $perPage,
        ?int $page = null,
        ?string $cursor = null
    ) {
        $this->type = $type;
        $this->perPage = $perPage;
        $this->page = $page;
        $this->cursor = $cursor;

        $this->validate();
    }

    public static function offset(int $page, int $perPage): self
    {
        return new self(PaginationType::OFFSET, $perPage, $page);
    }

    public static function cursor(int $perPage, ?string $cursor = null): self
    {
        return new self(PaginationType::CURSOR, $perPage, null, $cursor);
    }

    public function getType(): PaginationType
    {
        return $this->type;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getCursor(): ?string
    {
        return $this->cursor;
    }

    private function validate(): void
    {
        if ($this->type === PaginationType::OFFSET && $this->page === null) {
            throw new \InvalidArgumentException('Page is required for offset pagination');
        }

        if ($this->perPage < 1) {
            throw new \InvalidArgumentException('Per page must be greater than 0');
        }

        if ($this->page !== null && $this->page < 1) {
            throw new \InvalidArgumentException('Page must be greater than 0');
        }
    }

    public function toArray(): array
    {
        $data = [
            'type' => $this->type->value,
            'per_page' => $this->perPage,
        ];

        if ($this->type === PaginationType::OFFSET) {
            $data['page'] = $this->page;
        } elseif ($this->cursor !== null) {
            $data['cursor'] = $this->cursor;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        $type = PaginationType::from($data['type']);
        $perPage = $data['per_page'];

        if ($type === PaginationType::OFFSET) {
            if (! isset($data['page'])) {
                throw new \InvalidArgumentException('Page is required for offset pagination');
            }

            return self::offset($data['page'], $perPage);
        } else {
            return self::cursor($perPage, $data['cursor'] ?? null);
        }
    }
}
