<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchDecision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Matches\EventMatchResult>
 */
class EventMatchResultFactory extends Factory
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
            'match_decision_id' => MatchDecision::factory(),
        ];
    }
}