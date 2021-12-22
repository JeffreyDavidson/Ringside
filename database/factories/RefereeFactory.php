<?php

namespace Database\Factories;

use App\Enums\RefereeStatus;
use App\Models\Employment;
use App\Models\Injury;
use App\Models\Referee;
use App\Models\Retirement;
use App\Models\Suspension;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class RefereeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $modelClass = Referee::class;

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
            'status' => RefereeStatus::unemployed(),
        ];
    }

    public function bookable()
    {
        return $this->state(fn (array $attributes) => ['status' => RefereeStatus::bookable()])
        ->has(Employment::factory()->started(Carbon::yesterday()))
        ->afterCreating(function (Referee $referee) {
            $referee->save();
        });
    }

    public function withFutureEmployment()
    {
        return $this->state(fn (array $attributes) => ['status' => RefereeStatus::future_employment()])
        ->has(Employment::factory()->started(Carbon::tomorrow()))
        ->afterCreating(function (Referee $referee) {
            $referee->save();
        });
    }

    public function unemployed()
    {
        return $this->state(fn (array $attributes) => ['status' => RefereeStatus::unemployed()])
        ->afterCreating(function (Referee $referee) {
            $referee->save();
        });
    }

    public function retired()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(fn (array $attributes) => ['status' => RefereeStatus::retired()])
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

        return $this->state(fn (array $attributes) => ['status' => RefereeStatus::released()])
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

        return $this->state(fn (array $attributes) => ['status' => RefereeStatus::suspended()])
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

        return $this->state(fn (array $attributes) => ['status' => RefereeStatus::injured()])
        ->has(Employment::factory()->started($start))
        ->has(Injury::factory()->started($now))
        ->afterCreating(function (Referee $referee) {
            $referee->save();
        });
    }

    public function softDeleted()
    {
        return $this->state(fn (array $attributes) => ['deleted_at' => now()])
        ->afterCreating(function (Referee $referee) {
            $referee->save();
        });
    }
}
