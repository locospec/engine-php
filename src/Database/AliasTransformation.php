<?php

namespace Locospec\LCS\Database;

use Locospec\LCS\Models\ModelDefinition;
use Symfony\Component\Process\Process;

class AliasTransformation
{
    private ModelDefinition $model;

    public function __construct(ModelDefinition $model)
    {
        $this->model = $model;
    }

    public function transform(array $data): array
    {
        $aliases = $this->model->getAliases();

        if (empty($aliases)) {
            return $data;
        }

        $isCollection = is_array($data) && ! empty($data) && ! isset($data['id']);
        $records = $isCollection ? $data : [$data];

        $transformedRecords = [];
        foreach ($records as $record) {
            $transformedRecords[] = $this->processRecord($record, $aliases);
        }

        return $isCollection ? $transformedRecords : $transformedRecords[0];
    }

    private function processRecord(array $record, array $aliases): array
    {
        $processed = $record;

        foreach ($aliases as $aliasKey => $expression) {
            // First extract
            $extracted = $this->executeJQExpression($record, $expression['extract']);

            // Then transform if transform expression exists
            if ($extracted !== null && ! empty($expression['transform'])) {
                $transformed = $this->executeJQExpression(['value' => $extracted], '.value | '.$expression['transform']);

                if ($transformed !== null) {
                    $processed[$aliasKey] = $transformed;
                } else {
                    $processed[$aliasKey] = $extracted;
                }
            } else {
                $processed[$aliasKey] = $extracted;
            }
        }

        return $processed;
    }

    private function executeJQExpression(array $data, ?string $expression): mixed
    {
        if (empty($expression)) {
            return null;
        }

        $process = new Process(['jq', '-r', $expression]);
        $process->setInput(json_encode($data));

        try {
            $process->mustRun();

            if (! $process->isSuccessful()) {
                return null;
            }

            return trim($process->getOutput()) ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
