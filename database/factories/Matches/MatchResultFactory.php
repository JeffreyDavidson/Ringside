<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchDecision;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Matches\EventMatchResult>
 */
class MatchResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $winnerType = fake()->randomElement(['wrestler', 'tagTeam']);
        
        $winner = match ($winnerType) {
            'wrestler' => Wrestler::factory()->create(),
            'tagTeam' => TagTeam::factory()->create(),
            default => throw new \InvalidArgumentException("Unknown winner type: {$winnerType}"),
        };

        return [
            'match_id' => EventMatch::factory(),
            'match_decision_id' => MatchDecision::factory(),
            'winner_type' => $winnerType,
            'winner_id' => $winner->id,
        ];
    }
}
