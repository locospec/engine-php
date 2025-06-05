<?php

namespace LCSEngine\Schemas\Model\Relationships;

use LCSEngine\Registry\RegistryManager;
use InvalidArgumentException;

class HasOne extends Relationship
{
    protected string $localKey;

    public function setLocalKey(string $localKey): void
    {
        $this->localKey = $localKey;
    }

    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'foreignKey' => $this->foreignKey,
            'relatedModelName' => $this->relatedModelName,
            'currentModelName' => $this->currentModelName,
            'relationshipName' => $this->relationshipName,
            'localKey' => $this->localKey,
        ];
    }

    public static function fromArray(array $data, RegistryManager $registryManager): self
    {
        $relationship = new self();
        $inflector = StringInflector::getInstance();
       
        $relatedModelName = $data['model'] ?? $inflector->singular($data['relationshipName']);
        $relatedModel = $registryManager->get('model', $relatedModelName);
        $currentModel = $registryManager->get('model', $data['currentModelName']);

        if(!$relatedModel){
            throw new InvalidArgumentException("Related model '{$relatedModelName}' not found for relationship '{$data['relationshipName']}'");
        }

        $currentModelPrimaryKey = $currentModel->getPrimaryKey()->getName();
        $foreignKey = $data['foreignKey'] ?? $inflector->snake("{$data['currentModelName']}_{$currentModelPrimaryKey}");
        $localKey = $data['localKey'] ?? $currentModelPrimaryKey;

        $relationship->setType(Type::from($data['type'] ?? 'has_one'));
        $relationship->setForeignKey($foreignKey ?? '');
        $relationship->setRelatedModelName($relatedModelName ?? '');
        $relationship->setRelationshipName($data['relationshipName'] ?? '');
        $relationship->setCurrentModelName($data['currentModelName'] ?? '');
        $relationship->setLocalKey($localKey ?? '');
        
        return $relationship;
    }
} 