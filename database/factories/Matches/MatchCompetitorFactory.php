<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Matches\MatchCompetitor>
 */
class MatchCompetitorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'match_id' => EventMatch::factory(),
            'match_side_id' => fn (array $attributes) => MatchSide::factory()->create([
                'match_id' => $attributes['match_id'],
            ]),
            'competitor_type' => (new Wrestler())->getMorphClass(),
            'competitor_id' => Wrestler::factory(),
        ];
    }
}
