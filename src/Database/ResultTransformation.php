<?php

namespace LCSEngine\Database;

use LCSEngine\Schemas\Model\Model;

class ResultTransformation
{
    private Model $model;

    public function __construct() {}

    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    public function transform(array $data): array
    {
        $transformAttributes = $this->model->getTransformAttributes();
        if ($transformAttributes->isEmpty()) {
            return $data;
        }

        $isCollection = is_array($data) && ! empty($data) && ! isset($data['id']);
        $records = $isCollection ? $data : [$data];

        $transformedRecords = [];

        foreach ($records as $record) {
            $transformedRecords[] = $this->processRecord($record, $transformAttributes);
        }

        return $isCollection ? $transformedRecords : $transformedRecords[0];
    }

    private function processRecord(array $record, object $transformAttributes): array
    {
        $processed = $record;

        $transformAttributes->each(function ($attribute, $transformKey) use (&$processed, $record) {
            $extracted = null;
            if ($attribute->hasTransformSource()) {
                $source = $attribute->getTransformSource();

                if (strpos($source, '->') !== false) {
                    $source = preg_replace(['/^/', '/->?/'], ['', '.'], $source);
                }

                $extracted = $this->executeJMESPathExpression($record, $source);
                $processed[$transformKey] = $extracted;
            }

            if ($attribute->hasTransformTransformation()) {
                $transformation = $attribute->getTransformTransformation();
                $valueToTransform = (! empty($extracted) && json_decode($extracted, true) !== null) ? json_decode($extracted, true) : $extracted;
                $inputData = isset($extracted) && ! empty($extracted) ? ['value' => $valueToTransform] : $record;
                $processed[$transformKey] = $this->executeJMESPathExpression($inputData, $transformation);
            }
        });

        return $processed;
    }

    private function executeJMESPathExpression(array $data, ?string $expression): mixed
    {
        if (empty($expression)) {
            return null;
        }

        try {
            $runtime = new JMESPathCustomRuntime;
            $output = $runtime->search($expression, $data);

            return $output;
        } catch (\Exception $e) {
            return null;
            // throw $e;
        }
    }
}
