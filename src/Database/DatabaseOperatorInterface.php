<?php

namespace Locospec\LCS\Database;

use Locospec\LCS\Query\CursorPagination;
use Locospec\LCS\Query\FilterGroup;
use Locospec\LCS\Query\Pagination;
use Locospec\LCS\Query\Query;

interface DatabaseOperatorInterface
{
    /**
     * Insert a record into the database
     */
    public function insert(string $table, array $data): array;

    /**
     * Update records in the database
     */
    public function update(string $table, array $data, FilterGroup $conditions): array;

    /**
     * Delete records from the database
     */
    public function delete(string $table, FilterGroup $conditions): array;

    /**
     * Soft delete records by setting deleted_at timestamp
     */
    public function softDelete(string $table, FilterGroup $conditions): array;

    /**
     * Select records from the database
     */
    public function select(string $table, array $columns, FilterGroup $conditions): array;

    /**
     * Count records in the database
     */
    public function count(string $table, FilterGroup $conditions): array;

    /**
     * Get paginated records from the database
     */
    public function paginate(string $table, array $columns, Pagination $pagination, ?FilterGroup $conditions = null): array;

    /**
     * Get cursor paginated records from the database
     */
    public function cursorPaginate(string $table, array $columns, CursorPagination $cursor, ?FilterGroup $conditions = null): array;

    /**
     * Execute a raw SQL query
     */
    public function raw(string $sql, array $bindings = []): array;

    /**
     * Get list of attributes used in where conditions
     * Useful for selecting required columns
     */
    public function getWhereAttributes(): array;

    /**
     * Execute a query built from Query object
     * This provides a high-level interface for complex queries
     */
    public function executeQuery(Query $query): array;
}
