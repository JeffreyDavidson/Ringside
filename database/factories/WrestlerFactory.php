<?php

namespace Database\Factories;

use App\Enums\WrestlerStatus;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WrestlerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $modelClass = Wrestler::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'current_tag_team_id' => null,
            'name' => $this->faker->name,
            'height' => $this->faker->numberBetween(60, 95),
            'weight' => $this->faker->numberBetween(180, 500),
            'hometown' => $this->faker->city.', '.$this->faker->state,
            'signature_move' => Str::title($this->faker->words(3, true)),
            'status' => WrestlerStatus::__default,
        ];
    }

    public function bookable(): self
    {
        return $this->state([
            'status' => WrestlerStatus::BOOKABLE,
        ])->hasEmployments(1, ['started_at' => Carbon::yesterday()])
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
            $wrestler->load('employments');
        });
    }

    public function withFutureEmployment(): self
    {
        return $this->state([
            'status' => WrestlerStatus::FUTURE_EMPLOYMENT,
        ])->hasEmployments(1, ['started_at' => Carbon::tomorrow()])
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
            $wrestler->load('employments');
        });
    }

    public function unemployed(): self
    {
        return $this->state([
            'status' => WrestlerStatus::UNEMPLOYED,
        ])->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
        });
    }

    public function retired(): self
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state([
            'status' => WrestlerStatus::RETIRED,
        ])->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])
        ->hasRetirements(1, ['started_at' => $end])
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
            $wrestler->load('employments');
            $wrestler->load('retirements');
        });
    }

    public function released(): self
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state([
            'status' => WrestlerStatus::RELEASED,
        ])->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
            $wrestler->load('employments');
        });
    }

    public function suspended(): self
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state([
            'status' => WrestlerStatus::SUSPENDED,
        ])->hasEmployments(1, ['started_at' => $start])
        ->hasSuspensions(1, ['started_at' => $end])
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
            $wrestler->load('employments');
            $wrestler->load('suspensions');
        });
    }

    public function injured(): self
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state([
            'status' => WrestlerStatus::INJURED,
        ])->hasEmployments(1, ['started_at' => $start])
        ->hasInjuries(1, ['started_at' => $now])
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
            $wrestler->load('employments');
            $wrestler->load('injuries');
        });
    }

    public function softDeleted(): self
    {
        return $this->state([
            'deleted_at' => now(),
        ])->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
        });
    }
}
