<?php

namespace LCSEngine\Schemas\Model\Attributes;

class Option
{
    private ?string $id = null;
    private ?string $const = null;
    private ?string $title = null;

    public function setId(string $id): void { $this->id = $id; }
    public function setConst(string $const): void { $this->const = $const; }
    public function setTitle(string $title): void { $this->title = $title; }
    public function getId(): ?string { return $this->id; }
    public function getConst(): ?string { return $this->const; }
    public function getTitle(): ?string { return $this->title; }
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'const' => $this->const,
            'title' => $this->title,
        ];
    }

    public static function fromArray(array $data): self
    {
        $option = new self();
        if (isset($data['id'])) {
            $option->setId($data['id']);
        }
        if (isset($data['const'])) {
            $option->setConst($data['const']);
        }
        if (isset($data['title'])) {
            $option->setTitle($data['title']);
        }
        return $option;
    }
} 