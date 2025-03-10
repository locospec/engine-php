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

        $validation = $validator->validateOperation($input['preparedPayload']);

        if (!$validation['isValid']) {
            throw new RuntimeException(
                'Invalid operation: '.json_encode($validation['errors'])
            );
        }

        return [...$input, 'validated' => $validation['isValid']];
    }
}
