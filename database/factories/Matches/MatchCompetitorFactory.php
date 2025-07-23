<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\Matches\EventMatch;
use App\Models\Wrestlers\Wrestler;
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
            'competitor_type' => 'wrestler',
            'competitor_id' => Wrestler::factory(),
            'side_number' => fake()->numberBetween(1, 2), // Most matches have 2 sides
        ];
    }
}
