<?php

namespace Locospec\EnginePhp;

class DFTGraph extends TraversalGraph
{
    public function generateTree(Vertex $startVertex): TreeNode
    {
        $this->validateVertex($startVertex);

        return $this->dftHelper($startVertex, new \SplObjectStorage);
    }

    private function dftHelper(Vertex $vertex, \SplObjectStorage $currentPath): TreeNode
    {
        $node = new TreeNode($vertex);
        $currentPath->attach($vertex);

        $neighbors = $this->graph->getNeighbors($vertex->getId());
        foreach ($neighbors as $edge) {
            $neighborVertex = $edge->getTarget();

            if (! $currentPath->contains($neighborVertex)) {
                // Create new path for each neighbor by cloning current path
                $newPath = clone $currentPath;
                $childNode = $this->dftHelper($neighborVertex, $newPath);
                $node->children[] = $childNode;
            }
        }

        $currentPath->detach($vertex); // Backtrack

        return $node;
    }
}
