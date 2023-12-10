<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WrestlerStatus;
use App\Models\Employment;
use App\Models\Injury;
use App\Models\Retirement;
use App\Models\Suspension;
use App\Models\TagTeam;
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
            'status' => WrestlerStatus::Unemployed,
        ];
    }

    /**
     * Set the wrestler as bookable.
     */
    public function bookable(): static
    {
        return $this->state(fn () => ['status' => WrestlerStatus::Bookable])
            ->has(Employment::factory()->started(Carbon::yesterday()));
    }

    /**
     * Set the wrestler as having a future employment.
     */
    public function withFutureEmployment(): static
    {
        return $this->state(fn () => ['status' => WrestlerStatus::FutureEmployment])
            ->has(Employment::factory()->started(Carbon::tomorrow()));
    }

    /**
     * Set the wrestler as being unemployed.
     */
    public function unemployed(): static
    {
        return $this->state(fn () => ['status' => WrestlerStatus::Unemployed]);
    }

    /**
     * Set the wrestler as retired.
     */
    public function retired(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => WrestlerStatus::Retired])
            ->has(Employment::factory()->started($start)->ended($end))
            ->has(Retirement::factory()->started($end));
    }

    /**
     * Set the wrestler as released.
     */
    public function released(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => WrestlerStatus::Released])
            ->has(Employment::factory()->started($start)->ended($end));
    }

    /**
     * Set the wrestler as suspended.
     */
    public function suspended(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => WrestlerStatus::Suspended])
            ->has(Employment::factory()->started($start))
            ->has(Suspension::factory()->started($end));
    }

    /**
     * Set the wrestler as injured.
     */
    public function injured(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(fn () => ['status' => WrestlerStatus::Injured])
            ->has(Employment::factory()->started($start))
            ->has(Injury::factory()->started($now));
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
