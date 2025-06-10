<?php

namespace LCSEngine\Schemas\Query;

class SerializeConfig
{
    private string $header;
    private AlignType $align;

    public function __construct(string $header, AlignType $align = AlignType::LEFT)
    {
        $this->header = $header;
        $this->align = $align;
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    public function setHeader(string $header): void
    {
        $this->header = $header;
    }

    public function getAlign(): AlignType
    {
        return $this->align;
    }

    public function setAlign(AlignType $align): void
    {
        $this->align = $align;
    }

    public function toArray(): array
    {
        $data = [
            'header' => $this->header
        ];

        if ($this->align !== AlignType::LEFT) {
            $data['align'] = $this->align->value;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['header'],
            isset($data['align']) ? AlignType::from($data['align']) : AlignType::LEFT
        );
    }
}
