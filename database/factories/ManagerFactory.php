<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ManagerStatus;
use App\Models\Employment;
use App\Models\Injury;
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
            'status' => ManagerStatus::UNEMPLOYED,
        ];
    }

    public function available(): static
    {
        return $this->state(fn () => ['status' => ManagerStatus::AVAILABLE])
            ->has(Employment::factory()->started(Carbon::yesterday()));
    }

    public function withFutureEmployment(): static
    {
        return $this->state(fn () => ['status' => ManagerStatus::FUTURE_EMPLOYMENT])
            ->has(Employment::factory()->started(Carbon::tomorrow()));
    }

    public function unemployed(): static
    {
        return $this->state(fn () => ['status' => ManagerStatus::UNEMPLOYED]);
    }

    public function retired(): static
    {
        $start = now()->subMonths();
        $end = now()->subDays(3);

        return $this->state(fn () => ['status' => ManagerStatus::RETIRED])
            ->has(Employment::factory()->started($start)->ended($end))
            ->has(Retirement::factory()->started($end));
    }

    public function released(): static
    {
        $start = now()->subMonths();
        $end = now()->subDays(3);

        return $this->state(fn () => ['status' => ManagerStatus::RELEASED])
            ->has(Employment::factory()->started($start)->ended($end));
    }

    public function suspended(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => ManagerStatus::SUSPENDED])
            ->has(Employment::factory()->started($start))
            ->has(Suspension::factory()->started($end));
    }

    public function injured(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(fn () => ['status' => ManagerStatus::INJURED])
            ->has(Employment::factory()->started($start))
            ->has(Injury::factory()->started($now));
    }
}
