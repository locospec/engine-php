<?php

namespace Locospec\EnginePhp;

use Locospec\EnginePhp\Exceptions\DuplicateVertexException;
use Locospec\EnginePhp\Exceptions\VertexNotFoundException;

class Graph
{
    /**
     * @var array<mixed, Vertex> Map of vertex IDs to Vertex instances
     */
    private array $vertices = [];

    /**
     * @var array<mixed, Edge[]> Map of vertex IDs to arrays of outgoing edges
     */
    private array $adjacencyList = [];

    /**
     * @var bool Whether the graph is directed
     */
    private readonly bool $directed;

    /**
     * Creates a new Graph instance
     *
     * @param bool $directed Whether the graph is directed (default: false)
     */
    public function __construct(bool $directed = false)
    {
        $this->directed = $directed;
    }

    /**
     * Adds a vertex to the graph
     *
     * @param Vertex $vertex The vertex to add
     * @throws DuplicateVertexException If vertex with same ID already exists
     */
    public function addVertex(Vertex $vertex): void
    {
        $vertexId = $vertex->getId();

        if ($this->hasVertex($vertexId)) {
            throw new DuplicateVertexException("Vertex with ID {$vertexId} already exists");
        }

        $this->vertices[$vertexId] = $vertex;
        $this->adjacencyList[$vertexId] = [];
    }

    /**
     * Checks if an edge already exists between source and target vertices
     *
     * @param mixed $sourceId Source vertex ID
     * @param mixed $targetId Target vertex ID
     * @return bool
     */
    private function hasEdge(mixed $sourceId, mixed $targetId): bool
    {
        if (!isset($this->adjacencyList[$sourceId])) {
            return false;
        }

        foreach ($this->adjacencyList[$sourceId] as $edge) {
            if ($edge->getTarget()->getId() === $targetId) {
                return true;
            }
        }
        return false;
    }


    /**
     * Adds an edge to the graph
     *
     * @param Edge $edge The edge to add
     * @throws VertexNotFoundException If either source or target vertex doesn't exist
     */
    public function addEdge(Edge $edge): void
    {
        $sourceId = $edge->getSource()->getId();
        $targetId = $edge->getTarget()->getId();

        if (!$this->hasVertex($sourceId)) {
            throw new VertexNotFoundException("Source vertex with ID {$sourceId} not found");
        }
        if (!$this->hasVertex($targetId)) {
            throw new VertexNotFoundException("Target vertex with ID {$targetId} not found");
        }

        // Only add edge if it doesn't already exist
        if (!$this->hasEdge($sourceId, $targetId)) {
            $this->adjacencyList[$sourceId][] = $edge;
        }

        if (!$this->directed && !$this->hasEdge($targetId, $sourceId)) {
            // For undirected graphs, create reverse edge if it doesn't exist
            $reverseEdge = new Edge(
                $edge->getTarget(),
                $edge->getSource(),
                $edge->getType(),
                $edge->getData()
            );
            $this->adjacencyList[$targetId][] = $reverseEdge;
        }
    }

    /**
     * Returns all edges connected to the given vertex
     *
     * @param mixed $vertexId The ID of the vertex
     * @return Edge[] Array of connected edges
     * @throws VertexNotFoundException If vertex doesn't exist
     */
    public function getNeighbors(mixed $vertexId): array
    {
        if (!$this->hasVertex($vertexId)) {
            throw new VertexNotFoundException("Vertex with ID {$vertexId} not found");
        }

        return $this->adjacencyList[$vertexId];
    }

    /**
     * Checks if a vertex exists in the graph
     *
     * @param mixed $vertexId The ID to check
     * @return bool True if vertex exists, false otherwise
     */
    public function hasVertex(mixed $vertexId): bool
    {
        return isset($this->vertices[$vertexId]);
    }

    /**
     * Returns a vertex by its ID
     *
     * @param mixed $vertexId The ID of the vertex to get
     * @return Vertex|null The vertex if found, null otherwise
     */
    public function getVertex(mixed $vertexId): ?Vertex
    {
        return $this->vertices[$vertexId] ?? null;
    }

    /**
     * Returns all vertices in the graph
     *
     * @return array<mixed, Vertex>
     */
    public function getVertices(): array
    {
        return $this->vertices;
    }

    /**
     * Returns the complete adjacency list
     *
     * @return array<mixed, Edge[]>
     */
    public function getAdjacencyList(): array
    {
        return $this->adjacencyList;
    }

    /**
     * Returns whether the graph is directed
     */
    public function isDirected(): bool
    {
        return $this->directed;
    }
}
