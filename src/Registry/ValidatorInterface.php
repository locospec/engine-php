<?php

namespace Locospec\Engine\Registry;

interface ValidatorInterface
{
    /**
     * Validate input data against the provided JSON schema.
     *
     * @param array $input
     * @param array $schema
     * @param array $currentOperation
     * @return mixed True if validation passes, or errors collection.
     */
    public function validate(array $input, array $schema, string $currentOperation);
}