<?php

declare(strict_types=1);

namespace Database\Factories\Roster\Wrestlers;

use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Retirement;
use App\Models\Lifecycle\Suspension;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Wrestler>
 */
class WrestlerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'height' => fake()->numberBetween(60, 95),
            'weight' => fake()->numberBetween(180, 500),
            'hometown' => fake()->city().', '.fake()->state(),
            'signature_move' => null,
        ];
    }

    /**
     * Set the wrestler as employed.
     */
    public function employed(): static
    {
        return $this->has(Employment::factory()->started(Carbon::yesterday()), 'employments');
    }

    /**
     * Set the wrestler as bookable.
     */
    public function bookable(): static
    {
        return $this->employed();
    }

    /**
     * Set the wrestler as having a future employment.
     */
    public function withFutureEmployment(): static
    {
        return $this->has(Employment::factory()->started(Carbon::tomorrow()), 'employments');
    }

    /**
     * Set the wrestler as being unemployed.
     */
    public function unemployed(): static
    {
        return $this->state(fn () => []);
    }

    /**
     * Set the wrestler as retired.
     */
    public function retired(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->has(Employment::factory()->started($start)->ended($end), 'employments')
            ->has(Retirement::factory()->started($end), 'retirements');
    }

    /**
     * Set the wrestler as released.
     */
    public function released(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->has(Employment::factory()->started($start)->ended($end), 'employments');
    }

    /**
     * Set the wrestler as suspended.
     */
    public function suspended(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->has(Employment::factory()->started($start), 'employments')
            ->has(Suspension::factory()->started($end), 'suspensions');
    }

    /**
     * Set the wrestler as injured.
     */
    public function injured(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->has(Employment::factory()->started($start), 'employments')
            ->has(Injury::factory()->started($now), 'injuries');
    }

    /**
     * Add a wrestler to a tag team.
     */
    public function onCurrentTagTeam(?TagTeam $tagTeam = null): static
    {
        return $this->hasAttached(
            $tagTeam ?? TagTeam::factory(),
            ['joined_at' => now()->toDateTimeString()],
        );
    }
}
