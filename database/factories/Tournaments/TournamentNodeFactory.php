<?php

declare(strict_types=1);

namespace Database\Factories\Tournaments;

use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentNode;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TournamentNode> */
class TournamentNodeFactory extends Factory
{
    /** @return array<string,mixed> */
    public function definition(): array
    {
        return ['tournament_id' => Tournament::factory(), 'promotion_id' => fn (array $attributes): int => Tournament::query()->findOrFail($attributes['tournament_id'])->promotion_id, 'round' => 1, 'position' => 1, 'first_seed' => 1, 'second_seed' => 8];
    }
}
