<?php

namespace Locospec\Engine\Registry;

interface GeneratorInterface
{
    /**
     * Generate a value based on the given type and options.
     *
     * @param  string  $type  The type of generator.
     * @param  array  $options  Additional options for generation.
     * @return mixed The generated value.
     */
    public function generate(string $type, array $options = []);
}
