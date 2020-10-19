<?php

namespace Database\Factories;

use App\Enums\RefereeStatus;
use App\Models\Referee;
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

    public function bookable(): self
    {
        return $this->state([
            'status' => RefereeStatus::BOOKABLE,
        ])->hasEmployments(1, ['started_at' => Carbon::yesterday()])
        ->afterCreating(function (Referee $referee) {
            $referee->save();
        });
    }

    public function withFutureEmployment(): self
    {
        return $this->state([
            'status' => RefereeStatus::FUTURE_EMPLOYMENT,
        ])->hasEmployments(1, ['started_at' => Carbon::tomorrow()])
        ->afterCreating(function (Referee $referee) {
            $referee->save();
        });
    }

    public function unemployed(): self
    {
        return $this->state([
            'status' => RefereeStatus::UNEMPLOYED,
        ])->afterCreating(function (Referee $referee) {
            $referee->save();
        });
    }

    public function retired(): self
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state([
            'status' => RefereeStatus::RETIRED,
        ])->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])
        ->hasRetirements(1, ['started_at' => $end])
        ->afterCreating(function (Referee $referee) {
            $referee->save();
        });
    }

    public function released(): self
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state([
            'status' => RefereeStatus::RELEASED,
        ])->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])
        ->afterCreating(function (Referee $referee) {
            $referee->save();
        });
    }

    public function suspended(): self
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state([
            'status' => RefereeStatus::SUSPENDED,
        ])->hasEmployments(1, ['started_at' => $start])
        ->hasSuspensions(1, ['started_at' => $end])
        ->afterCreating(function (Referee $referee) {
            $referee->save();
        });
    }

    public function injured(): self
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state([
            'status' => RefereeStatus::INJURED,
        ])->hasEmployments(1, ['started_at' => $start])
        ->hasInjuries(1, ['started_at' => $now])
        ->afterCreating(function (Referee $referee) {
            $referee->updateStatus();
        });
    }

    public function softDeleted(): self
    {
        return $this->state([
            'deleted_at' => now(),
        ])->afterCreating(function (Referee $referee) {
            $referee->save();
        });
    }
}
