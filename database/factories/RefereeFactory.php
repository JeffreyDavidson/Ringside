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
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'status' => RefereeStatus::__default,
        ];
    }

    public function bookable()
    {
        return $this->state(function (array $attributes) {
            return ['status' => RefereeStatus::BOOKABLE];
        })
        ->has(Employment::factory()->started(Carbon::yesterday()))
        ->afterCreating(function (Referee $referee) {
            $referee->updateStatus()->save();
        });
    }

    public function withFutureEmployment()
    {
        return $this->state(function (array $attributes) {
            return ['status' => RefereeStatus::FUTURE_EMPLOYMENT];
        })
        ->has(Employment::factory()->started(Carbon::tomorrow()))
        ->afterCreating(function (Referee $referee) {
            $referee->updateStatus()->save();
        });
    }

    public function employed()
    {
        return $this->state(function (array $attributes) {
            return ['status' => RefereeStatus::BOOKABLE];
        })
        ->has(Employment::factory()->started(Carbon::yesterday()))
        ->afterCreating(function (Referee $referee) {
            $referee->updateStatus()->save();
            $referee->load('employments');
        });
    }

    public function unemployed()
    {
        return $this->state(function (array $attributes) {
            return ['status' => RefereeStatus::UNEMPLOYED];
        })
        ->afterCreating(function (Referee $referee) {
            $referee->updateStatus()->save();
        });
    }

    public function retired()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => RefereeStatus::RETIRED];
        })
        ->has(Employment::factory()->started($start)->ended($end))
        ->has(Retirement::factory()->started($end))
        ->afterCreating(function (Referee $referee) {
            $referee->updateStatus()->save();
        });
    }

    public function released()
    {
        $now = now();
        $start = $now->copy()->subWeeks(2);
        $end = $now->copy()->subWeeks(1);

        return $this->state(function (array $attributes) {
            return ['status' => RefereeStatus::RELEASED];
        })
        ->has(Employment::factory()->started($start)->ended($end))
        ->afterCreating(function (Referee $referee) {
            $referee->updateStatus()->save();
        });
    }

    public function suspended()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => RefereeStatus::SUSPENDED];
        })
        ->has(Employment::factory()->started($start))
        ->has(Suspension::factory()->started($end))
        ->afterCreating(function (Referee $referee) {
            $referee->updateStatus()->save();
        });
    }

    public function injured()
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(function (array $attributes) {
            return ['status' => RefereeStatus::INJURED];
        })
        ->has(Employment::factory()->started($start))
        ->has(Injury::factory()->started($now))
        ->afterCreating(function (Referee $referee) {
            $referee->updateStatus()->save();
        });
    }

    public function softDeleted()
    {
        return $this->state(function (array $attributes) {
            return ['deleted_at' => now()];
        })
        ->afterCreating(function (Referee $referee) {
            $referee->updateStatus()->save();
        });
    }
}
