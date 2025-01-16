<?php

namespace Locospec\Engine\Tasks;

use Locospec\Engine\Exceptions\DatabaseOperationException;
use Locospec\Engine\Query\FilterGroup;

class DatabasePaginateTask extends AbstractDatabaseTask
{
    public function getName(): string
    {
        return 'database.paginate';
    }

    public function execute(array $input): array
    {
        $this->validateInput($input);

        try {
            $table = $this->getTableName();
            $columns = $input['columns'] ?? ['*'];
            $conditions = $input['conditions'] ?? new FilterGroup;
            $pagination = $input['pagination'];

            if (isset($pagination['cursor'])) {
                $result = $this->operator->cursorPaginate(
                    $table,
                    $columns,
                    $pagination,
                    $conditions
                );
            } else {
                $result = $this->operator->paginate(
                    $table,
                    $columns,
                    $pagination,
                    $conditions
                );
            }

            // $result from operator now contains ['result', 'sql', 'timing']
            return $this->formatOutput($result);
        } catch (\Exception $e) {
            throw new DatabaseOperationException("Pagination operation failed: {$e->getMessage()}");
        }
    }

    private function validateInput(array $input): void
    {
        if (! isset($input['pagination'])) {
            throw new DatabaseOperationException('Pagination task requires pagination configuration');
        }

        if (isset($input['columns']) && ! is_array($input['columns'])) {
            throw new DatabaseOperationException('Columns must be an array');
        }
    }
}
