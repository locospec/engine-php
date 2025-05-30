<?php

namespace LCSEngine\Schemas\Model\Attributes;

class Option
{
    private string $id;
    private string $const;
    private string $title;

    public function __construct()
    {
        $this->id = uniqid('opt_');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setConst(string $const): self
    {
        $this->const = $const;
        return $this;
    }

    public function getConst(): string
    {
        return $this->const;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function toArray(): array
    {
        return [
            'const' => $this->const,
            'title' => $this->title,
        ];
    }
} 