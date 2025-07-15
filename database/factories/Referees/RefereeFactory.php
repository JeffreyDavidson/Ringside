<?php

declare(strict_types=1);

namespace Database\Factories\Referees;

use App\Enums\Shared\EmploymentStatus;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeEmployment;
use App\Models\Referees\RefereeInjury;
use App\Models\Referees\RefereeRetirement;
use App\Models\Referees\RefereeSuspension;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Referee>
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
            // Status is now computed from employment relationships
        ];
    }

    /**
     * Set the referee as employed.
     */
    public function employed(): static
    {
        return $this->has(RefereeEmployment::factory()->started(Carbon::yesterday()), 'employments');
    }

    public function bookable(): static
    {
        return $this->employed();
    }

    public function withFutureEmployment(): static
    {
        return $this->has(RefereeEmployment::factory()->started(Carbon::tomorrow()), 'employments');
    }

    public function unemployed(): static
    {
        return $this->state(fn () => []);
    }

    public function retired(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->has(RefereeEmployment::factory()->started($start)->ended($end), 'employments')
            ->has(RefereeRetirement::factory()->started($end), 'retirements');
    }

    public function released(): static
    {
        $now = now();
        $start = $now->copy()->subWeeks(2);
        $end = $now->copy()->subWeeks();

        return $this->has(RefereeEmployment::factory()->started($start)->ended($end), 'employments');
    }

    public function suspended(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->has(RefereeEmployment::factory()->started($start), 'employments')
            ->has(RefereeSuspension::factory()->started($end), 'suspensions');
    }

    public function injured(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->has(RefereeEmployment::factory()->started($start), 'employments')
            ->has(RefereeInjury::factory()->started($now), 'injuries');
    }
}
