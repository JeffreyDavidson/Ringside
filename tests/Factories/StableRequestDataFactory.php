<?php

namespace Tests\Factories;

use App\Models\Stable;
use Carbon\Carbon;

class StableRequestDataFactory
{
    private string $name = 'Example Stable Name';
    private ?string $started_at = null;
    private array $wrestlers = [];
    private array $tagTeams = [];

    public static function new(): self
    {
        return new self();
    }

    public function create(array $overrides = []): array
    {
        return array_replace([
            'name' => $this->name,
            'started_at' => $this->started_at,
            'wrestlers' => $this->wrestlers,
            'tag_teams' => $this->tagTeams,
        ], $overrides);
    }

    public function withStartDate(string | Carbon $startedAt): self
    {
        $clone = clone $this;

        $clone->started_at = $startedAt instanceof Carbon
            ? $startedAt->format('Y-m-d H:i:a')
            : $startedAt;

        return $clone;
    }

    public function withWrestlers(array $wrestlers): self
    {
        $clone = clone $this;

        $clone->wrestlers = $wrestlers;

        return $clone;
    }

    public function withTagTeams(array $tagTeams): self
    {
        $clone = clone $this;

        $clone->tagTeams = $tagTeams;

        return $clone;
    }

    public function withStable(Stable $stable): self
    {
        $clone = clone $this;

        $this->name = $stable->name;

        return $clone;
    }
}
