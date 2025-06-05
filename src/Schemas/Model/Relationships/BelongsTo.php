<?php

namespace LCSEngine\Schemas\Model\Relationships;

use InvalidArgumentException;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Support\StringInflector;

class BelongsTo extends Relationship
{
    protected string $ownerKey;

    public function setOwnerKey(string $ownerKey): void
    {
        $this->ownerKey = $ownerKey;
    }

    public function getOwnerKey(): string
    {
        return $this->ownerKey;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'foreignKey' => $this->foreignKey,
            'relatedModelName' => $this->relatedModelName,
            'currentModelName' => $this->currentModelName,
            'relationshipName' => $this->relationshipName,
            'ownerKey' => $this->ownerKey,
        ];
    }

    public static function fromArray(array $data, RegistryManager $registryManager): self
    {
        $relationship = new self;
        $inflector = StringInflector::getInstance();

        $relatedModelName = $data['model'] ?? $inflector->singular($data['relationshipName']);
        $relatedModel = $registryManager->get('model', $relatedModelName);
        $currentModel = $registryManager->get('model', $data['currentModelName']);

        if (! $relatedModel) {
            throw new InvalidArgumentException("Related model '{$relatedModelName}' not found for relationship '{$data['relationshipName']}'");
        }

        $relatedModelPrimaryKey = $relatedModel->getPrimaryKey()->getName();
        $foreignKey = $data['foreignKey'] ?? $inflector->snake("{$relatedModelName}_{$relatedModelPrimaryKey}");
        $ownerKey = $data['ownerKey'] ?? $relatedModelPrimaryKey;

        $relationship->setType(Type::from($data['type'] ?? 'belongs_to'));
        $relationship->setForeignKey($foreignKey ?? '');
        $relationship->setRelatedModelName($relatedModelName ?? '');
        $relationship->setCurrentModelName($data['currentModelName'] ?? '');
        $relationship->setRelationshipName($data['relationshipName'] ?? '');
        $relationship->setOwnerKey($ownerKey ?? '');

        return $relationship;
    }
}
