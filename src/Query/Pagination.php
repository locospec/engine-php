<?php

namespace LCSEngine\Query;

interface Pagination
{
    /**
     * Get the limit of items to return
     */
    public function getLimit(): int;

    /**
     * Get the offset for SQL OFFSET clause
     */
    public function getOffset(): int;

    /**
     * Get the page number (for offset pagination)
     */
    public function getPage(): ?int;

    /**
     * Get items per page (for offset pagination)
     */
    public function getPerPage(): ?int;

    /**
     * Get the cursor value (for cursor pagination)
     */
    public function getCursor(): ?string;

    /**
     * Get the cursor column (for cursor pagination)
     */
    public function getCursorColumn(): ?string;

    /**
     * Validate pagination parameters
     */
    public function validate(): void;

    /**
     * Convert pagination to array
     */
    public function toArray(): array;
}
