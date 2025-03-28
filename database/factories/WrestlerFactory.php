<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Models\TagTeam;
use App\Models\WrestlerEmployment;
use App\Models\WrestlerInjury;
use App\Models\WrestlerRetirement;
use App\Models\WrestlerSuspension;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wrestler>
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
            'user_id' => null,
            'name' => fake()->name(),
            'height' => fake()->numberBetween(60, 95),
            'weight' => fake()->numberBetween(180, 500),
            'hometown' => fake()->city().', '.fake()->state(),
            'signature_move' => null,
            'status' => EmploymentStatus::Unemployed,
        ];
    }

    /**
     * Set the wrestler as bookable.
     */
    public function bookable(): static
    {
        return $this->state(fn () => ['status' => EmploymentStatus::Bookable])
            ->has(WrestlerEmployment::factory()->started(Carbon::yesterday()), 'employments');
    }

    /**
     * Set the wrestler as having a future employment.
     */
    public function withFutureEmployment(): static
    {
        return $this->state(fn () => ['status' => EmploymentStatus::FutureEmployment])
            ->has(WrestlerEmployment::factory()->started(Carbon::tomorrow()), 'employments');
    }

    /**
     * Set the wrestler as being unemployed.
     */
    public function unemployed(): static
    {
        return $this->state(fn () => ['status' => EmploymentStatus::Unemployed]);
    }

    /**
     * Set the wrestler as retired.
     */
    public function retired(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => EmploymentStatus::Retired])
            ->has(WrestlerEmployment::factory()->started($start)->ended($end), 'employments')
            ->has(WrestlerRetirement::factory()->started($end), 'retirements');
    }

    /**
     * Set the wrestler as released.
     */
    public function released(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => EmploymentStatus::Released])
            ->has(WrestlerEmployment::factory()->started($start)->ended($end), 'employments');
    }

    /**
     * Set the wrestler as suspended.
     */
    public function suspended(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => EmploymentStatus::Suspended])
            ->has(WrestlerEmployment::factory()->started($start), 'employments')
            ->has(WrestlerSuspension::factory()->started($end), 'suspensions');
    }

    /**
     * Set the wrestler as injured.
     */
    public function injured(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(fn () => ['status' => EmploymentStatus::Injured])
            ->has(WrestlerEmployment::factory()->started($start), 'employments')
            ->has(WrestlerInjury::factory()->started($now), 'injuries');
    }

    /**
     * Add a wrestler to a tag team.
     */
    public function onCurrentTagTeam(?TagTeam $tagTeam = null): static
    {
        $tagTeam ??= TagTeam::factory()->create();

        return $this->hasAttached($tagTeam, ['joined_at' => now()->toDateTimeString()]);
    }
}
