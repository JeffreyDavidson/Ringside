<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\Matches\EventMatchResult;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Matches\EventMatchLoser>
 */
class EventMatchLoserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_match_result_id' => EventMatchResult::factory(),
            'loser_type' => Wrestler::class,
            'loser_id' => Wrestler::factory(),
        ];
    }

    /**
     * Configure the factory to use a wrestler as the loser.
     */
    public function wrestler(): static
    {
        return $this->state([
            'loser_type' => Wrestler::class,
            'loser_id' => Wrestler::factory(),
        ]);
    }

    /**
     * Configure the factory to use a tag team as the loser.
     */
    public function tagTeam(): static
    {
        return $this->state([
            'loser_type' => \App\Models\TagTeams\TagTeam::class,
            'loser_id' => \App\Models\TagTeams\TagTeam::factory(),
        ]);
    }
}