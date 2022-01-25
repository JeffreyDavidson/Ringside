<?php

namespace Tests\Factories;

use App\Models\Referee;
use Carbon\Carbon;

class RefereeRequestDataFactory
{
    private string $first_name = 'James';

    private string $last_name = 'Williams';

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

    public function withStartDate(Carbon $startedAt): self
    {
        $clone = clone $this;

        $clone->started_at = $startedAt;

        return $clone;
    }

    public function withReferee(Referee $referee): self
    {
        $clone = clone $this;

        $clone->first_name = $referee->first_name;
        $clone->last_name = $referee->last_name;
        $clone->started_at = $referee->startedAt?->toDateTimeString();

        return $clone;
    }
}
