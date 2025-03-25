<?php

namespace Database\Factories;

use App\Models\EventMatch;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventMatchCompetitor>
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
