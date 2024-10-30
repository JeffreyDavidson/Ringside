<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ManagerStatus;
use App\Models\ManagerEmployment;
use App\Models\ManagerInjury;
use App\Models\Retirement;
use App\Models\Suspension;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Manager>
 */
class ManagerFactory extends Factory
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
            'status' => ManagerStatus::Unemployed,
        ];
    }

    public function available(): static
    {
        return $this->state(fn () => ['status' => ManagerStatus::Available])
            ->has(ManagerEmployment::factory()->started(Carbon::yesterday()), 'employments');
    }

    public function withFutureEmployment(): static
    {
        return $this->state(fn () => ['status' => ManagerStatus::FutureEmployment])
            ->has(ManagerEmployment::factory()->started(Carbon::tomorrow()), 'employments');
    }

    public function unemployed(): static
    {
        return $this->state(fn () => ['status' => ManagerStatus::Unemployed]);
    }

    public function retired(): static
    {
        $start = now()->subMonths();
        $end = now()->subDays(3);

        return $this->state(fn () => ['status' => ManagerStatus::Retired])
            ->has(ManagerEmployment::factory()->started($start)->ended($end), 'employments')
            ->has(Retirement::factory()->started($end));
    }

    public function released(): static
    {
        $start = now()->subMonths();
        $end = now()->subDays(3);

        return $this->state(fn () => ['status' => ManagerStatus::Released])
            ->has(ManagerEmployment::factory()->started($start)->ended($end), 'employments');
    }

    public function suspended(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => ManagerStatus::Suspended])
            ->has(ManagerEmployment::factory()->started($start), 'employments')
            ->has(Suspension::factory()->started($end));
    }

    public function injured(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(fn () => ['status' => ManagerStatus::Injured])
            ->has(ManagerEmployment::factory()->started($start), 'employments')
            ->has(ManagerInjury::factory()->started($now));
    }
}
