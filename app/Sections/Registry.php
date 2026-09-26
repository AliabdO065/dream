<?php

namespace App\Sections;

/** All section types known to the platform. Core types come from config; modules add their own. */
class Registry
{
    /** @var array<string, SectionType> */
    private array $types = [];

    public function register(SectionType $type): void
    {
        $this->types[$type->key()] = $type;
    }

    public function get(string $key): ?SectionType
    {
        return $this->types[$key] ?? null;
    }

    /** @return array<string, SectionType> */
    public function all(): array
    {
        return $this->types;
    }
}
