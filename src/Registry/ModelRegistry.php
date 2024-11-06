<?php

namespace Locospec\EnginePhp\Registry;

use Locospec\EnginePhp\Graph;
use Locospec\EnginePhp\Vertex;
use Locospec\EnginePhp\Edge;
use Locospec\EnginePhp\Models\ModelDefinition;
use Locospec\EnginePhp\Models\Relationships\Relationship;
use Locospec\EnginePhp\Exceptions\InvalidArgumentException;

class ModelRegistry extends AbstractRegistry
{
    private ?Graph $relationshipGraph = null;

    public function getType(): string
    {
        return 'model';
    }

    protected function getItemName(mixed $item): string
    {
        return $item->getName();
    }

    /**
     * Get the relationship graph for all models
     */
    public function getRelationshipGraph(): Graph
    {
        if ($this->relationshipGraph === null) {
            $this->buildRelationshipGraph();
        }

        return $this->relationshipGraph;
    }

    /**
     * Build the relationship graph from registered models
     */
    private function buildRelationshipGraph(): void
    {
        $this->relationshipGraph = new Graph(true); // Directed graph
        $this->addModelsAsVertices();
        $this->addRelationshipsAsEdges();
    }

    /**
     * First pass: Add all models as vertices
     */
    private function addModelsAsVertices(): void
    {
        /** @var ModelDefinition $model */
        foreach ($this->items as $modelName => $model) {
            $vertex = new Vertex($modelName, $model);
            $this->relationshipGraph->addVertex($vertex);
        }
    }

    /**
     * Second pass: Add all relationships as edges
     */
    private function addRelationshipsAsEdges(): void
    {
        /** @var ModelDefinition $model */
        foreach ($this->items as $sourceModelName => $model) {
            $sourceVertex = $this->relationshipGraph->getVertex($sourceModelName);
            if (!$sourceVertex) {
                continue;
            }

            foreach ($model->getRelationships() as $relationship) {
                $this->addRelationshipEdge($sourceVertex, $relationship);
            }
        }
    }

    /**
     * Add a single relationship as an edge in the graph
     */
    private function addRelationshipEdge(Vertex $sourceVertex, Relationship $relationship): void
    {
        $targetModelName = $relationship->getRelatedModelName();
        $targetVertex = $this->relationshipGraph->getVertex($targetModelName);

        if (!$targetVertex) {
            throw new InvalidArgumentException(
                "Cannot create relationship edge: Target model '$targetModelName' not found"
            );
        }

        $edge = new Edge(
            $sourceVertex,
            $targetVertex,
            $relationship->getType(),
            [
                'relationshipName' => $relationship->getRelationshipName(),
                'keys' => $this->getRelationshipKeys($relationship)
            ]
        );

        $this->relationshipGraph->addEdge($edge);
    }

    /**
     * Get relationship keys in a consistent format
     */
    private function getRelationshipKeys(Relationship $relationship): array
    {
        $keys = [];

        if (method_exists($relationship, 'getForeignKey')) {
            $keys['foreignKey'] = $relationship->getForeignKey();
        }

        if (method_exists($relationship, 'getLocalKey')) {
            $keys['localKey'] = $relationship->getLocalKey();
        }

        if (method_exists($relationship, 'getOwnerKey')) {
            $keys['ownerKey'] = $relationship->getOwnerKey();
        }

        return $keys;
    }

    /**
     * Get an expansion graph starting from a specific model
     */
    public function getExpansionGraph(string $modelName): ?Graph
    {
        $startVertex = $this->getRelationshipGraph()->getVertex($modelName);
        if (!$startVertex) {
            throw new InvalidArgumentException("Model '$modelName' not found in registry");
        }

        return $this->getRelationshipGraph()->createExpansionGraph($startVertex);
    }

    /**
     * Clear the registry and reset the graph
     */
    public function clear(): void
    {
        parent::clear();
        $this->relationshipGraph = null;
    }
}
