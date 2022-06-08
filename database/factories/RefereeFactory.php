<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RefereeStatus;
use App\Models\Employment;
use App\Models\Injury;
use App\Models\Referee;
use App\Models\Retirement;
use App\Models\Suspension;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class RefereeFactory extends Factory
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
            'status' => RefereeStatus::UNEMPLOYED,
        ];
    }

    public function bookable()
    {
        return $this->state(fn (array $attributes) => ['status' => RefereeStatus::BOOKABLE])
            ->has(Employment::factory()->started(Carbon::yesterday()))
            ->afterCreating(function (Referee $referee) {
                $referee->save();
            });
    }

    public function withFutureEmployment()
    {
        return $this->state(fn (array $attributes) => ['status' => RefereeStatus::FUTURE_EMPLOYMENT])
            ->has(Employment::factory()->started(Carbon::tomorrow()))
            ->afterCreating(function (Referee $referee) {
                $referee->save();
            });
    }

    public function unemployed()
    {
        return $this->state(fn (array $attributes) => ['status' => RefereeStatus::UNEMPLOYED])
            ->afterCreating(function (Referee $referee) {
                $referee->save();
            });
    }

    public function retired()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(fn (array $attributes) => ['status' => RefereeStatus::RETIRED])
            ->has(Employment::factory()->started($start)->ended($end))
            ->has(Retirement::factory()->started($end))
            ->afterCreating(function (Referee $referee) {
                $referee->save();
            });
    }

    public function released()
    {
        $now = now();
        $start = $now->copy()->subWeeks(2);
        $end = $now->copy()->subWeeks(1);

        return $this->state(fn (array $attributes) => ['status' => RefereeStatus::RELEASED])
            ->has(Employment::factory()->started($start)->ended($end))
            ->afterCreating(function (Referee $referee) {
                $referee->save();
            });
    }

    public function suspended()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(fn (array $attributes) => ['status' => RefereeStatus::SUSPENDED])
            ->has(Employment::factory()->started($start))
            ->has(Suspension::factory()->started($end))
            ->afterCreating(function (Referee $referee) {
                $referee->save();
            });
    }

    public function injured()
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(fn (array $attributes) => ['status' => RefereeStatus::INJURED])
            ->has(Employment::factory()->started($start))
            ->has(Injury::factory()->started($now))
            ->afterCreating(function (Referee $referee) {
                $referee->save();
            });
    }
}
