<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchResult;
use App\Models\Matches\MatchWinner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Matches\MatchWinner>
 */
class MatchWinnerFactory extends Factory
{
    protected $model = MatchWinner::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'match_result_id' => MatchResult::factory(),
            'match_competitor_id' => MatchCompetitor::factory(),
        ];
    }
}
