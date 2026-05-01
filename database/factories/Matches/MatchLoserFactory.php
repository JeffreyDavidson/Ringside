<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchLoser;
use App\Models\Matches\MatchResult;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Matches\MatchLoser>
 */
#[UseModel(MatchLoser::class)]
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
            'match_competitor_id' => MatchCompetitor::factory(),
        ];
    }
}
