<?php

declare(strict_types=1);

namespace Database\Factories\Roster\TagTeams;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<TagTeamWrestler>
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
        // At least two days back, so a test that sets only `left_at` (yesterday, say) still ends after the start.
        $joinedAt = now()->subDays(fake()->numberBetween(2, 730));

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
            'joined_at' => now()->subDays(fake()->numberBetween(2, 365)),
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
                'left_at' => $joinedAt->copy()->addDays(fake()->numberBetween(0, (int) $joinedAt->diffInDays(now()))),
            ];
        });
    }
}
