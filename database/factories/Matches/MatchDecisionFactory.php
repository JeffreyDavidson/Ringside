<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\MatchDecision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factories\Factory<MatchDecision>
 */
class MatchDecisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => str($name)->title()->value(),
            'slug' => str($name)->slug()->value(),
        ];
    }
}
