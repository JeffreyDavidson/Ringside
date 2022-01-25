<?php

namespace Tests\Factories;

use App\Models\Title;

class TitleRequestDataFactory
{
    private string $name = 'Example Title';

    private ?string $activated_at = null;

    public static function new(): self
    {
        return new self;
    }

    public function create(array $overrides = []): array
    {
        return array_replace([
            'name' => $this->name,
            'activated_at' => $this->activated_at,
        ], $overrides);
    }

    public function withTitle(Title $title): self
    {
        $clone = clone $this;

        $clone->name = $title->name;
        $clone->activated_at = $title->activatedAt?->toDateTimeString();

        return $clone;
    }
}
