<?php

declare(strict_types=1);

namespace Database\Factories\Stables;

use App\Models\Stables\Stable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stables\StableActivityPeriod>
 */
class StableActivityPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-2 years', 'now');

        return [
            'stable_id' => Stable::factory(),
            'started_at' => $startedAt,
            'ended_at' => null, // Active by default
        ];
    }

    /**
     * Configure the factory to create an ended activity period.
     */
    public function ended(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = Carbon::parse($attributes['started_at']);
            return [
                'ended_at' => fake()->dateTimeBetween($startedAt, 'now'),
            ];
        });
    }

    /**
     * Configure the factory to create a current active period.
     */
    public function current(): static
    {
        return $this->state([
            'started_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'ended_at' => null,
        ]);
    }
}