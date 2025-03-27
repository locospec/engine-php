<?php

namespace Locospec\Engine\Database;

use Locospec\Engine\Models\ModelDefinition;
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

    private function processRecord(array $record, object $aliases): array
    {
        $processed = $record;

        foreach ($aliases as $aliasKey => $expression) {
            $extracted = null;
            if (isset($expression->source)) {
                $source = $expression->source;

                if (strpos($expression->source, '->') !== false) {
                    $source = preg_replace(['/^/', '/->?/'], ['.', '.'], $expression->source);
                } else {
                    $source = '.'.$source;
                }

                $extracted = $this->executeJQExpression($record, $source);
                $processed[$aliasKey] = $extracted;
            }

            if (isset($expression->transform)) {
                $valueToTransform = (! empty($extracted) && json_decode($extracted, true) !== null) ? json_decode($extracted, true) : $extracted;
                $inputData = isset($extracted) && ! empty($extracted) ? ['value' => $valueToTransform] : $record;
                $processed[$aliasKey] = $this->executeJQExpression($inputData, $expression->transform);
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

            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            return trim($process->getOutput()) ?: null;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
