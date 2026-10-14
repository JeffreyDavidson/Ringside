<?php

declare(strict_types=1);

namespace Database\Factories\Tournaments;

use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentStatus;
use App\Models\Promotions\Promotion;
use App\Models\Tournaments\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tournament> */
class TournamentFactory extends Factory
{
    /** @return array<string,mixed> */
    public function definition(): array
    {
        return ['promotion_id' => Promotion::factory(), 'name' => fake()->words(3, true), 'description' => null, 'entrant_kind' => EntrantKind::Wrestler, 'capacity' => 8, 'repeat_appearances' => false, 'status' => TournamentStatus::Draft, 'version' => 1];
    }
}
