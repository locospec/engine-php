<?php

namespace Locospec\LCS;

use Locospec\LCS\Exceptions\InvalidArgumentException;

class Edge
{
    private readonly Vertex $source;

    private readonly Vertex $target;

    private readonly ?string $type;

    private mixed $data;

    /**
     * Creates a new Edge instance
     *
     * @param  Vertex  $source  The source vertex
     * @param  Vertex  $target  The target vertex
     * @param  string|null  $type  Optional type of the edge
     * @param  mixed|null  $data  Optional payload data
     *
     * @throws InvalidArgumentException If source or target is invalid, or if type is provided but not a string
     */
    public function __construct(
        Vertex $source,
        Vertex $target,
        ?string $type = null,
        mixed $data = null
    ) {
        if (! $source instanceof Vertex) {
            throw new InvalidArgumentException('Source must be a valid Vertex instance');
        }
        if (! $target instanceof Vertex) {
            throw new InvalidArgumentException('Target must be a valid Vertex instance');
        }

        $this->source = $source;
        $this->target = $target;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Returns the source vertex
     */
    public function getSource(): Vertex
    {
        return $this->source;
    }

    /**
     * Returns the target vertex
     */
    public function getTarget(): Vertex
    {
        return $this->target;
    }

    /**
     * Returns the edge type
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Returns the edge data
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Sets new data for the edge
     */
    public function setData(mixed $data): void
    {
        $this->data = $data;
    }
}
