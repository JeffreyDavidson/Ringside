<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\Matches\MatchResult;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Matches\MatchLoser>
 */
class MatchLoserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'match_result_id' => MatchResult::factory(),
            'loser_type' => 'wrestler',
            'loser_id' => Wrestler::factory(),
        ];
    }

    /**
     * Configure the factory to use a wrestler as the loser.
     */
    public function wrestler(): static
    {
        return $this->state([
            'loser_type' => 'wrestler',
            'loser_id' => Wrestler::factory(),
        ]);
    }

    /**
     * Configure the factory to use a tag team as the loser.
     */
    public function tagTeam(): static
    {
        return $this->state([
            'loser_type' => 'tagTeam',
            'loser_id' => TagTeam::factory(),
        ]);
    }
}
