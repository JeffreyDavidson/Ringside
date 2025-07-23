<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\MatchCompetitor;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchResult;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchCompetitor>
 */
class MatchWinnerFactory extends Factory
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
            'winner_type' => 'wrestler',
            'winner_id' => Wrestler::factory(),
        ];
    }
}
