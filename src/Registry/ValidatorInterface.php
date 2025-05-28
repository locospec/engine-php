<?php

namespace Locospec\Engine\Registry;

interface ValidatorInterface
{
    /**
     * Validate input data against the provided JSON schema.
     *
     * @return mixed True if validation passes, or errors collection.
     */
    public function validate(array $input, array $attributes, array $options);

    /**
     * Validate input data against the provided custom rules.
     *
     * @param  string  $rule  The custom rule name (without the "custom:" prefix).
     * @param  mixed  $value  The value to validate.
     * @return mixed True if validation passes, false otherwise.
     */
    public function validateCustomRule(string $rule, $value, array $options);
}
