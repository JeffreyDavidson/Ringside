<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Models\Manager;

class ManagerRequestDataFactory
{
    private string $first_name = 'John';

    private string $last_name = 'Smith';

    private ?string $started_at = null;

    public static function new(): self
    {
        return new self;
    }

    public function create(array $overrides = []): array
    {
        return array_replace([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'started_at' => $this->started_at,
        ], $overrides);
    }

    public function withManager(Manager $manager): self
    {
        $clone = clone $this;

        $clone->first_name = $manager->first_name;
        $clone->last_name = $manager->last_name;
        $clone->started_at = $manager->startedAt?->toDateTimeString();

        return $clone;
    }
}
