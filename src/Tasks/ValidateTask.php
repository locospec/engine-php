<?php

namespace LCSEngine\Tasks;

use LCSEngine\Database\DatabaseOperationsCollection;
use LCSEngine\Exceptions\ValidationException;
use LCSEngine\SpecValidator;
use LCSEngine\Schemas\Model\Attributes\Type as AttributeType;

class ValidateTask extends AbstractTask implements TaskInterface
{
    public function getName(): string
    {
        return 'validate';
    }

    public function execute(array $input, array $taskArgs = []): array
    {
        try {
            $validator = new SpecValidator;

            switch ($this->context->get('action')) {
                case '_create':
                    $validation = $this->validatePayloadForCreateAndUpdate($input['preparedPayload'], 'insert');
                    break;

                case '_update':
                    $validation = $this->validatePayloadForCreateAndUpdate($input['preparedPayload'], 'update');
                    break;

                default:
                    break;
            }

            $validation = [];

            if (is_array($input['preparedPayload']) && array_is_list($input['preparedPayload'])) {
                foreach ($input['preparedPayload'] as $key => $value) {
                    $validation = $validator->validateOperation($value);

                    if (! $validation['isValid']) {
                        throw new ValidationException(
                            'Invalid operation: ' . json_encode($validation['errors'])
                        );
                    }
                }
            } else {
                $validation = $validator->validateOperation($input['preparedPayload']);

                if (! $validation['isValid']) {
                    throw new ValidationException(
                        'Invalid operation: ' . json_encode($validation['errors'])
                    );
                }
            }

            return [...$input, 'validated' => $validation['isValid']];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function validatePayloadForCreateAndUpdate(array $payload, string $action): array
    {
        try {
            $dbOps = new DatabaseOperationsCollection($this->operator);
            $dbOps->setRegistryManager($this->context->get('lcs')->getRegistryManager());
            $options = [
                'dbOps' => $dbOps,
                'action' => $action,
                'dbOperator' => $this->operator,
                'modelName' => $this->context->get('model')->getName(),
            ];
            $validator = $this->context->get('crudValidator');
            $model = $this->context->get('model');
            $attributes = $this->context->get('mutator')->getAttributes()->filter(fn($attribute) => $attribute->getType() !== AttributeType::ALIAS)->all();
            $errors = [];

            // Ensure "data" is an array of records
            $records = $payload['data'] ?? [];

            // Validate each record individually using the attributes.
            if (is_array($records) && isset($records[0])) {
                foreach ($records as $index => $record) {
                    $result = $validator->validate($record, $attributes, $options);
                    // If the validator returns errors (not true), capture them.
                    if ($result !== true) {
                        $errors[$index] = $result;
                    }
                }
            } else {
                $result = $validator->validate($records, $attributes, $options);

                // If the validator returns errors (not true), capture them.
                if ($result !== true) {
                    $errors = $result;
                }
            }
            // Return validation errors if any
            if (! empty($errors)) {
                if (is_array($errors) && isset($errors[0])) {
                    throw new ValidationException($errors[0]);
                } else {
                    throw new ValidationException($errors);
                }
            }

            return [
                'isValid' => true,
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
