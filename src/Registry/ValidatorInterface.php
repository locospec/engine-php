<?php

namespace Locospec\Engine\Registry;

interface ValidatorInterface
{
    /**
     * Validate input data against the provided JSON schema.
     *
     * @return mixed True if validation passes, or errors collection.
     */
    public function validate(array $input, array $schema);
}
