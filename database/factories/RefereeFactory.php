<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RefereeStatus;
use App\Models\Employment;
use App\Models\Injury;
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
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'status' => RefereeStatus::UNEMPLOYED,
        ];
    }

    public function bookable(): static
    {
        return $this->state(fn () => ['status' => RefereeStatus::BOOKABLE])
            ->has(Employment::factory()->started(Carbon::yesterday()));
    }

    public function withFutureEmployment(): static
    {
        return $this->state(fn () => ['status' => RefereeStatus::FUTURE_EMPLOYMENT])
            ->has(Employment::factory()->started(Carbon::tomorrow()));
    }

    public function unemployed(): static
    {
        return $this->state(fn () => ['status' => RefereeStatus::UNEMPLOYED]);
    }

    public function retired(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => RefereeStatus::RETIRED])
            ->has(Employment::factory()->started($start)->ended($end))
            ->has(Retirement::factory()->started($end));
    }

    public function released(): static
    {
        $now = now();
        $start = $now->copy()->subWeeks(2);
        $end = $now->copy()->subWeeks();

        return $this->state(fn () => ['status' => RefereeStatus::RELEASED])
            ->has(Employment::factory()->started($start)->ended($end));
    }

    public function suspended(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => RefereeStatus::SUSPENDED])
            ->has(Employment::factory()->started($start))
            ->has(Suspension::factory()->started($end));
    }

    public function injured(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(fn () => ['status' => RefereeStatus::INJURED])
            ->has(Employment::factory()->started($start))
            ->has(Injury::factory()->started($now));
    }
}
