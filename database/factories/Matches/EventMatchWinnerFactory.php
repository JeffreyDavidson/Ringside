<?php

namespace Database\Factories\Matches;

use App\Models\EventMatchCompetitor;
use App\Models\Matches\EventMatch;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventMatchCompetitor>
 */
class EventMatchWinnerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_match_id' => EventMatch::factory(),
            'competitor_type' => 'wrestler',
            'competitor_id' => Wrestler::factory(),
            'side_number' => fake()->randomDigitNotZero(),
        ];
    }
}
