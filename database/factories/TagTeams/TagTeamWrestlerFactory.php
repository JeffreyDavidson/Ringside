<?php

declare(strict_types=1);

namespace Database\Factories\TagTeams;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TagTeams\TagTeamWrestler>
 */
class TagTeamWrestlerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $joinedAt = fake()->dateTimeBetween('-2 years', 'now');

        return [
            'tag_team_id' => TagTeam::factory(),
            'wrestler_id' => Wrestler::factory(),
            'joined_at' => $joinedAt,
            'left_at' => null, // Active by default
        ];
    }

    /**
     * Configure the factory to create a current active membership.
     */
    public function current(): static
    {
        return $this->state([
            'joined_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'left_at' => null,
        ]);
    }

    /**
     * Configure the factory to create an ended membership.
     */
    public function ended(): static
    {
        return $this->state(function (array $attributes) {
            $joinedAt = Carbon::parse($attributes['joined_at']);

            return [
                'left_at' => fake()->dateTimeBetween($joinedAt, 'now'),
            ];
        });
    }
}
