<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchSide>
 */
class MatchSideFactory extends Factory
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
            'position' => 1,
        ];
    }
}
