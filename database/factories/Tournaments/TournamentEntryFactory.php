<?php

declare(strict_types=1);

namespace Database\Factories\Tournaments;

use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TournamentEntry> */
class TournamentEntryFactory extends Factory
{
    /** @return array<string,mixed> */
    public function definition(): array
    {
        return ['tournament_id' => Tournament::factory(), 'promotion_id' => fn (array $attributes): int => Tournament::query()->findOrFail($attributes['tournament_id'])->promotion_id, 'seed' => 1, 'competitor_type' => (new Wrestler)->getMorphClass(), 'competitor_id' => fn (array $attributes): int => Wrestler::factory()->employed()->create(['promotion_id' => $attributes['promotion_id']])->id, 'withdrawn' => false];
    }
}
