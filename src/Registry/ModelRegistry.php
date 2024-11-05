<?php

namespace Locospec\EnginePhp\Registry;

use Locospec\EnginePhp\Edge;
use Locospec\EnginePhp\Graph;
use Locospec\EnginePhp\Vertex;

class ModelRegistry extends AbstractRegistry
{
    private ?Graph $graph = null;

    public function getType(): string
    {
        return 'model';
    }

    protected function getItemName(mixed $item): string
    {
        return $item->getName();
    }

    /**
     * Get or create the relationship graph
     */
    private function getGraph(): Graph
    {
        if ($this->graph === null) {
            $this->graph = new Graph(true);
            $this->buildGraphFromModels();
        }

        return $this->graph;
    }

    /**
     * Build the graph from registered models
     */
    private function buildGraphFromModels(): void
    {
        // First pass: Create vertices for all models
        foreach ($this->items as $modelName => $model) {
            $this->graph->addVertex(new Vertex($modelName, $model));
        }

        // Second pass: Create edges for relationships
        foreach ($this->items as $modelName => $model) {
            $relationships = $model->getRelationships() ?? [];
            $sourceVertex = $this->graph->getVertex($modelName);

            foreach ($relationships as $relationshipName => $relationship) {

                $targetModel = $relationship->getRelatedModel() ?? null;
                $type = $relationship->getType() ?? null;

                if ($targetModel && $type) {
                    $targetVertex = $this->graph->getVertex($targetModel);
                    if ($targetVertex) {
                        $edge = new Edge($sourceVertex, $targetVertex, $type, $relationship->getKeys());
                        $this->graph->addEdge($edge);
                    }
                }

                // new Edge($vertices['properties'], $vertices['sub_asset_types'], 'belongs_to'),
            }
        }
    }

    /**
     * Override clear to reset graph
     */
    public function clear(): void
    {
        parent::clear();
        $this->graph = null;
    }

    /**
     * Get expansion graph from a starting model
     */
    public function getExpansionGraph(string $modelName): ?Graph
    {
        $vertex = $this->getGraph()->getVertex($modelName);
        if (! $vertex) {
            return null;
        }

        return $this->getGraph()->createExpansionGraph($vertex);
    }
}
