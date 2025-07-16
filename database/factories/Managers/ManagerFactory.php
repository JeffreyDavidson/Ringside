<?php

declare(strict_types=1);

namespace Database\Factories\Managers;

use App\Models\Managers\ManagerEmployment;
use App\Models\Managers\ManagerInjury;
use App\Models\Managers\ManagerRetirement;
use App\Models\Managers\ManagerSuspension;
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
            // Status is now computed from employment relationships
        ];
    }

    public function employed(): static
    {
        return $this->has(ManagerEmployment::factory()->started(Carbon::yesterday()), 'employments');
    }

    public function withFutureEmployment(): static
    {
        return $this->has(ManagerEmployment::factory()->started(Carbon::tomorrow()), 'employments');
    }

    public function unemployed(): static
    {
        return $this->state(fn () => []);
    }

    public function retired(): static
    {
        $start = now()->subMonths();
        $end = now()->subDays(3);

        return $this->has(ManagerEmployment::factory()->started($start)->ended($end), 'employments')
            ->has(ManagerRetirement::factory()->started($end), 'retirements');
    }

    public function released(): static
    {
        $start = now()->subMonths();
        $end = now()->subDays(3);

        return $this->has(ManagerEmployment::factory()->started($start)->ended($end), 'employments');
    }

    public function suspended(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->has(ManagerEmployment::factory()->started($start), 'employments')
            ->has(ManagerSuspension::factory()->started($end), 'suspensions');
    }

    public function injured(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->has(ManagerEmployment::factory()->started($start), 'employments')
            ->has(ManagerInjury::factory()->started($now), 'injuries');
    }
}
