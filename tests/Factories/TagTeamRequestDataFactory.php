<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Models\TagTeam;

class TagTeamRequestDataFactory
{
    private string $name = 'Example Tag Team Name';

    private ?string $signature_move = null;

    private ?string $started_at = null;

    private array $wrestlers = [];

    public static function new(): self
    {
        return new self;
    }

    public function create(array $overrides = []): array
    {
        return array_replace([
            'name' => $this->name,
            'signature_move' => $this->signature_move,
            'started_at' => $this->started_at,
            'wrestlers' => $overrides['wrestlers'] ?? $this->wrestlers,
        ], $overrides);
    }

    public function withTagTeam(TagTeam $tagTeam): self
    {
        $clone = clone $this;

        $clone->name = $tagTeam->name;
        $clone->signature_move = $tagTeam->signature_move;
        $clone->started_at = $tagTeam->startedAt?->toDateTimeString();

        return $clone;
    }
}
