<?php

namespace LCSEngine\Registry;

use LCSEngine\Edge;
use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\Graph;
use LCSEngine\LCS;
use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Model\Relationships\BelongsTo;
use LCSEngine\Schemas\Model\Relationships\HasMany;
use LCSEngine\Schemas\Model\Relationships\HasOne;
use LCSEngine\Schemas\Model\Relationships\Relationship;
use LCSEngine\Tasks\Traits\PayloadPreparationHelpers;
use LCSEngine\Vertex;

/**
 * ModelRegistry manages the registration and relationship graphs of models.
 *
 * This class extends AbstractRegistry to provide specific functionality for managing
 * model definitions and their relationships. It maintains a graph representation
 * of model relationships that can be used for analysis and traversal.
 */
class ModelRegistry extends AbstractRegistry
{
    use PayloadPreparationHelpers;

    /** @var Graph|null The graph representing relationships between models */
    private ?Graph $relationshipGraph = null;

    /** @var array Simple map: [fromModel][toModel] => array of JOIN structures */
    private array $joinMap = [];

    /**
     * Get the registry type identifier.
     *
     * @return string Returns 'model' as the registry type
     */
    public function getType(): string
    {
        return 'model';
    }

    /**
     * Get the name identifier for a registry item.
     *
     * @param  mixed  $item  The item to get the name from
     * @return string The name of the item
     */
    protected function getItemName(mixed $item): string
    {
        return $item->getName();
    }

    /**
     * Get the relationship graph for all registered models.
     *
     * Lazily initializes the graph if it hasn't been built yet.
     *
     * @return Graph The graph representing all model relationships
     */
    public function getRelationshipGraph(): Graph
    {
        if ($this->relationshipGraph === null) {
            $this->buildRelationshipGraph();
        }

        return $this->relationshipGraph;
    }

    /**
     * Build the relationship graph from registered models.
     *
     * Creates a directed graph where vertices represent models and
     * edges represent relationships between them.
     */
    private function buildRelationshipGraph(): void
    {
        $this->relationshipGraph = new Graph(true); // Directed graph
        $this->addModelsAsVertices();
        $this->addRelationshipsAsEdges();
    }

    /**
     * Add all registered models as vertices in the graph.
     *
     * First pass of graph building: creates vertices for all models.
     */
    private function addModelsAsVertices(): void
    {
        /** @var Model $model */
        foreach ($this->items as $modelName => $model) {
            $vertex = new Vertex($modelName, $model);
            $this->relationshipGraph->addVertex($vertex);
        }
    }

    /**
     * Add all model relationships as edges in the graph.
     *
     * Second pass of graph building: creates edges for all relationships.
     */
    private function addRelationshipsAsEdges(): void
    {
        /** @var Model $model */
        foreach ($this->items as $sourceModelName => $model) {
            $sourceVertex = $this->relationshipGraph->getVertex($sourceModelName);
            if (! $sourceVertex) {
                continue;
            }

            foreach ($model->getRelationships() as $relationship) {
                $this->addRelationshipEdge($sourceVertex, $relationship);
            }
        }
    }

    /**
     * Add a single relationship as an edge in the graph.
     *
     * @param  Vertex  $sourceVertex  The source vertex representing the model
     * @param  Relationship  $relationship  The relationship to add as an edge
     *
     * @throws InvalidArgumentException If the target model doesn't exist
     */
    private function addRelationshipEdge(Vertex $sourceVertex, Relationship $relationship): void
    {
        $targetModelName = $relationship->getRelatedModelName();
        $targetVertex = $this->relationshipGraph->getVertex($targetModelName);

        if (! $targetVertex) {
            throw new InvalidArgumentException(
                "Cannot create relationship edge: Target model '$targetModelName' not found"
            );
        }

        $edge = new Edge(
            $sourceVertex,
            $targetVertex,
            $relationship->getType()->value,
            [
                'relationshipName' => $relationship->getRelationshipName(),
                'keys' => $this->getRelationshipKeys($relationship),
            ]
        );

        $this->relationshipGraph->addEdge($edge);
    }

    /**
     * Get relationship keys in a consistent format.
     *
     * @param  Relationship  $relationship  The relationship to get keys from
     * @return array<string, string> Array of relationship keys
     */
    private function getRelationshipKeys(Relationship $relationship): array
    {
        $keys = [];

        if ($relationship instanceof HasMany || $relationship instanceof HasOne) {
            $keys['foreignKey'] = $relationship->getForeignKey();
            $keys['localKey'] = $relationship->getLocalKey();
        } elseif ($relationship instanceof BelongsTo) {
            $keys['foreignKey'] = $relationship->getForeignKey();
            $keys['ownerKey'] = $relationship->getOwnerKey();
        }

        return $keys;
    }

    /**
     * Get an expansion graph starting from a specific model.
     *
     * @param  string  $modelName  The name of the model to start from
     * @return Graph|null The expansion graph, or null if it couldn't be created
     *
     * @throws InvalidArgumentException If the model doesn't exist
     */
    public function getExpansionGraph(string $modelName): ?Graph
    {
        $startVertex = $this->getRelationshipGraph()->getVertex($modelName);
        if (! $startVertex) {
            throw new InvalidArgumentException("Model '$modelName' not found in registry");
        }

        return $this->getRelationshipGraph()->createExpansionGraph($startVertex);
    }

    /**
     * Build relationship path discovery map using graph expansion.
     * For each model, find all reachable relationship paths.
     */
    public function buildJoinMap(RegistryManager $registryManager): void
    {
        $logger = LCS::getLogger();
        $this->joinMap = [];

        foreach ($this->items as $fromModelName => $fromModel) {
            $this->joinMap[$fromModelName] = [];

            // Get expansion graph to find all reachable models through relationships
            $startVertex = $this->getRelationshipGraph()->getVertex($fromModelName);
            if (! $startVertex) {
                continue;
            }

            $expansionGraph = $this->getExpansionGraph($fromModelName);
            if (! $expansionGraph) {
                continue;
            }

            // Find all paths from this model
            $paths = $this->extractPathsFromExpansion($expansionGraph, $fromModelName);

            foreach ($paths as $relationshipPath => $_) {
                $relationshipPathArray = explode('.', $relationshipPath);
                $relationshipsJoined = [];

                try {
                    $joins = $this->buildJoinsForRelationshipPath(
                        $relationshipPathArray,
                        $fromModel,
                        $registryManager,
                        $relationshipsJoined
                    );
                    $this->joinMap[$fromModelName][$relationshipPath] = $joins;
                } catch (\Exception) {
                    $this->joinMap[$fromModelName][$relationshipPath] = []; // Empty on error
                }
            }
        }

        // dd($this->joinMap);

        // $logger?->notice('joinMap', $this->joinMap);
    }

    /**
     * Extract all relationship paths from an expansion graph.
     * Returns paths in format: ['relationshipPath' => [array of edges]]
     */
    private function extractPathsFromExpansion(Graph $expansionGraph, string $startModel): array
    {
        $paths = [];

        // Get all vertices in the expansion graph
        $vertices = $expansionGraph->getVertices();

        foreach ($vertices as $targetVertex) {
            $targetModelName = $targetVertex->getId();
            if ($targetModelName === $startModel) {
                continue;
            }

            // Find the path from start to this target in the expansion graph
            $edges = $expansionGraph->findShortestPath($startModel, $targetModelName);
            if ($edges !== null) {
                // Build relationship path string
                $relationshipNames = [];
                foreach ($edges as $edge) {
                    $edgeData = $edge->getData();
                    $relationshipNames[] = $edgeData['relationshipName'] ?? 'unknown';
                }

                $relationshipPath = implode('.', $relationshipNames);
                $paths[$relationshipPath] = $edges;
            }
        }

        return $paths;
    }

    /**
     * Get JOIN structures for a relationship path.
     * Simple lookup of pre-built JOINs.
     */
    public function getPathJoins(string $fromModel, string $relationshipPath): ?array
    {
        return $this->joinMap[$fromModel][$relationshipPath] ?? null;
    }

    /**
     * Clear the registry and reset the graph and join map.
     */
    public function clear(): void
    {
        parent::clear();
        $this->relationshipGraph = null;
        $this->joinMap = [];
    }
}
