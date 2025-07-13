<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\MatchType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchType>
 */
class MatchTypeFactory extends Factory
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
            'number_of_sides' => fake()->randomDigit(),
        ];
    }
}
