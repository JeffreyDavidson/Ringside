<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ManagerStatus;
use App\Models\Employment;
use App\Models\Injury;
use App\Models\Manager;
use App\Models\Retirement;
use App\Models\Suspension;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ManagerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'status' => ManagerStatus::UNEMPLOYED,
        ];
    }

    public function available()
    {
        return $this->state(fn (array $attributes) => ['status' => ManagerStatus::AVAILABLE])
            ->has(Employment::factory()->started(Carbon::yesterday()))
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }

    public function withFutureEmployment()
    {
        return $this->state(fn (array $attributes) => ['status' => ManagerStatus::FUTURE_EMPLOYMENT])
            ->has(Employment::factory()->started(Carbon::tomorrow()))
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }

    public function unemployed()
    {
        return $this->state(fn (array $attributes) => ['status' => ManagerStatus::UNEMPLOYED])
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }

    public function retired()
    {
        $start = now()->subMonths(1);
        $end = now()->subDays(3);

        return $this->state(fn (array $attributes) => ['status' => ManagerStatus::RETIRED])
            ->has(Employment::factory()->started($start)->ended($end))
            ->has(Retirement::factory()->started($end))
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }

    public function released()
    {
        $start = now()->subMonths(1);
        $end = now()->subDays(3);

        return $this->state(fn (array $attributes) => ['status' => ManagerStatus::RELEASED])
            ->has(Employment::factory()->started($start)->ended($end))
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }

    public function suspended()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(fn (array $attributes) => ['status' => ManagerStatus::SUSPENDED])
            ->has(Employment::factory()->started($start))
            ->has(Suspension::factory()->started($end))
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }

    public function injured()
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(fn (array $attributes) => ['status' => ManagerStatus::INJURED])
            ->has(Employment::factory()->started($start))
            ->has(Injury::factory()->started($now))
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }
}
