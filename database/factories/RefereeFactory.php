<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RefereeStatus;
use App\Models\RefereeEmployment;
use App\Models\RefereeInjury;
use App\Models\Retirement;
use App\Models\Suspension;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Referee>
 */
class RefereeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'status' => RefereeStatus::Unemployed,
        ];
    }

    public function bookable(): static
    {
        return $this->state(fn () => ['status' => RefereeStatus::Bookable])
            ->has(RefereeEmployment::factory()->started(Carbon::yesterday()), 'employments');
    }

    public function withFutureEmployment(): static
    {
        return $this->state(fn () => ['status' => RefereeStatus::FutureEmployment])
            ->has(RefereeEmployment::factory()->started(Carbon::tomorrow()), 'employments');
    }

    public function unemployed(): static
    {
        return $this->state(fn () => ['status' => RefereeStatus::Unemployed]);
    }

    public function retired(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => RefereeStatus::Retired])
            ->has(RefereeEmployment::factory()->started($start)->ended($end), 'employments')
            ->has(Retirement::factory()->started($end));
    }

    public function released(): static
    {
        $now = now();
        $start = $now->copy()->subWeeks(2);
        $end = $now->copy()->subWeeks();

        return $this->state(fn () => ['status' => RefereeStatus::Released])
            ->has(RefereeEmployment::factory()->started($start)->ended($end), 'employments');
    }

    public function suspended(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => RefereeStatus::Suspended])
            ->has(RefereeEmployment::factory()->started($start), 'employments')
            ->has(Suspension::factory()->started($end));
    }

    public function injured(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(fn () => ['status' => RefereeStatus::Injured])
            ->has(RefereeEmployment::factory()->started($start), 'employments')
            ->has(RefereeInjury::factory()->started($now));
    }
}
