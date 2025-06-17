<?php

namespace LCSEngine\Schemas\Query\EntityLayout;

use Illuminate\Support\Collection;

class EntityLayoutBuilder
{
    protected Collection $sections; // of Section

    public function __construct()
    {
        $this->sections = collect();
    }

    public function addSection(Section $section): self
    {
        $this->sections->push($section);

        return $this;
    }

    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function toArray(): array
    {
        return $this->sections->map(fn($section) => $section->toArray())->all();
    }

    public static function fromArray(array $data): self
    {
        $builder = new self;
        foreach ($data as $sectionData) {
            $builder->addSection(Section::fromArray($sectionData));
        }

        return $builder;
    }

    public static function fromShorthand(array $shorthand): self
    {
        $builder = new self;

        foreach ($shorthand as $sectionData) {
            // If it's not an array, skip (invalid)
            if (! is_array($sectionData) || count($sectionData) === 0) {
                continue;
            }

            $first = $sectionData[0];
            // $sectionLabel = str_starts_with($first, '$') ? trim(substr($first, 1)) : null;
            $sectionLabel = is_string($first) && str_starts_with($first, '$')
                ? trim(substr($first, 1))
                : '';
            $section = new Section($sectionLabel);

            $innerItems = is_string($first) && str_starts_with($first, '$')
                ? array_slice($sectionData, 1)
                : $sectionData;

            foreach ($innerItems as $item) {
                if (is_array($item)) {
                    // nested section (array with "$ Title", "field1", ...)
                    $firstInner = $item[0] ?? '';
                    $isNestedSection = is_string($firstInner) && str_starts_with($firstInner, '$');

                    if ($isNestedSection) {
                        $nestedLabel = trim(substr($firstInner, 1));
                        $nestedSection = new Section($nestedLabel);

                        foreach (array_slice($item, 1) as $key) {
                            $nestedSection->addField(new Field($key, self::labelize($key)));
                        }

                        $section->addSection($nestedSection);
                    } else {
                        // anonymous group of fields (no label)
                        $anonymousGroup = new Section('');
                        foreach ($item as $key) {
                            $anonymousGroup->addField(new Field($key, self::labelize($key)));
                        }
                        $section->addSection($anonymousGroup);
                    }
                } elseif (is_string($item)) {
                    // flat field
                    $section->addField(new Field($item, self::labelize($item)));
                }
            }

            $builder->addSection($section);
        }

        return $builder;
    }

    private static function labelize(string $key): string
    {
        return ucfirst(str_replace('_', ' ', $key));
    }
}
