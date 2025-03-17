<?php

namespace Locospec\Engine\Tasks;

use Locospec\Engine\SpecValidator;
use RuntimeException;

class ValidateTask extends AbstractTask implements TaskInterface
{
    public function getName(): string
    {
        return 'validate';
    }

    public function execute(array $input): array
    {
        $validator = new SpecValidator;

        switch ($this->context->get('action')) {
            case '_create':
                $validation = $this->validatePayloadForCreateAndUpdate($input['preparedPayload'], 'insert');
                break;

            case '_update':
                $validation = $this->validatePayloadForCreateAndUpdate($input['preparedPayload']['data'], 'update');
                break;

            default:
                break;
        }
        
        $validation = $validator->validateOperation($input['preparedPayload']);

        if (! $validation['isValid']) {
            throw new RuntimeException(
                'Invalid operation: '.json_encode($validation['errors'])
            );
        }

        return [...$input, 'validated' => $validation['isValid']];
    }

    public function validatePayloadForCreateAndUpdate(array $payload, string $dbOp): array
    {
        $validator = $this->context->get('crudValidator');
        $model = $this->context->get('model');
        $attributes = $this->context->get('model')->getAttributes()->getAttributes();
        $errors = [];
        
        // Ensure "data" is an array of records
        $records = $payload['data'] ?? [];

        // Validate each record individually using the attributes.
        foreach ($records as $index => $record) {
            $result = $validator->validate($record, $attributes, $dbOp);
            // If the validator returns errors (not true), capture them.
            if ($result !== true) {
                $errors[$index] = $result;
            }
        }

        // Return validation errors if any
        if (!empty($errors)) {
            throw new RuntimeException($errors[0]);
        }

        return [
            'isValid' => true,
        ];
    }
}
