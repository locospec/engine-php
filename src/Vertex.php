<?php

namespace Locospec\Engine;

use Locospec\Engine\Exceptions\InvalidArgumentException;

class Vertex
{
    /**
     * @var mixed The unique identifier for the vertex
     */
    private readonly mixed $id;

    /**
     * @var mixed|null The data payload associated with the vertex
     */
    private mixed $data;

    /**
     * Creates a new Vertex instance
     *
     * @param  mixed  $id  The unique identifier for the vertex
     * @param  mixed|null  $data  Optional payload data
     *
     * @throws InvalidArgumentException If id is null
     */
    public function __construct(mixed $id, mixed $data = null)
    {
        if ($id === null) {
            throw new InvalidArgumentException('Vertex ID cannot be null');
        }
        $this->id = $id;
        $this->data = $data;
    }

    /**
     * Returns the stored data payload
     *
     * @return mixed|null The data associated with this vertex
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Returns the vertex ID
     *
     * @return mixed The unique identifier of this vertex
     */
    public function getId(): mixed
    {
        return $this->id;
    }
}
