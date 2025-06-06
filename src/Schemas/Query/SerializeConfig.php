<?php

namespace LCS\Engine\Schemas\Query;

class SerializeConfig
{
    public string $header;
    public AlignType $align;

    public function __construct(string $header, AlignType $align)
    {
        $this->header = $header;
        $this->align = $align;
    }

    public function setHeader(string $header): void
    {
        $this->header = $header;
    }

    public function setAlign(AlignType $align): void
    {
        $this->align = $align;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['header'],
            AlignType::from($data['align'])
        );
    }
} 