<?php

namespace LCSEngine\Query;

use LCSEngine\Exceptions\InvalidArgumentException;

class CursorPagination implements Pagination
{
    private ?string $cursor;

    private int $limit;

    private string $cursorColumn;

    private int $maxLimit = 100;

    public function __construct(
        ?string $cursor = null,
        int $limit = 15,
        string $cursorColumn = 'id'
    ) {
        $this->cursor = $cursor;
        $this->limit = $limit;
        $this->cursorColumn = $cursorColumn;
        $this->validate();
    }

    public function getCursor(): ?string
    {
        return $this->cursor;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getCursorColumn(): string
    {
        return $this->cursorColumn;
    }

    // Implementing offset-related methods as null since this is cursor pagination
    public function getPage(): ?int
    {
        return null;
    }

    public function getPerPage(): ?int
    {
        return null;
    }

    public function getOffset(): int
    {
        return 0; // Cursor pagination doesn't use offset
    }

    public function validate(): void
    {
        if ($this->limit < 1) {
            throw new InvalidArgumentException('Limit must be greater than 0');
        }

        if ($this->limit > $this->maxLimit) {
            throw new InvalidArgumentException(
                "Limit cannot exceed {$this->maxLimit}"
            );
        }

        if (empty($this->cursorColumn)) {
            throw new InvalidArgumentException('Cursor column cannot be empty');
        }

        if ($this->cursor !== null && ! $this->isValidCursor($this->cursor)) {
            throw new InvalidArgumentException('Invalid cursor format');
        }
    }

    private function isValidCursor(string $cursor): bool
    {
        if (! preg_match('/^[a-zA-Z0-9+\/]+={0,2}$/', $cursor)) {
            return false;
        }

        $decoded = base64_decode($cursor, true);

        return $decoded !== false;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['cursor'] ?? null,
            $data['limit'] ?? 15,
            $data['cursor_column'] ?? 'id'
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'cursor' => $this->cursor,
            'limit' => $this->limit,
            'cursor_column' => $this->cursorColumn,
        ]);
    }

    public static function encodeCursor(mixed $value): string
    {
        return base64_encode((string) $value);
    }

    public static function decodeCursor(string $cursor): mixed
    {
        return base64_decode($cursor);
    }
}
