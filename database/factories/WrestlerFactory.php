<?php

namespace Database\Factories;

use App\Enums\WrestlerStatus;
use App\Models\Employment;
use App\Models\Injury;
use App\Models\Retirement;
use App\Models\Suspension;
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

    public function employed()
    {
        return $this->state(function (array $attributes) {
            return ['status' => WrestlerStatus::BOOKABLE];
        })
        ->has(Employment::factory()->started(Carbon::yesterday()))
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
            $wrestler->load('employments');
        });
    }

    public function bookable()
    {
        return $this->state(function (array $attributes) {
            return ['status' => WrestlerStatus::BOOKABLE];
        })
        ->has(Employment::factory()->started(Carbon::yesterday()))
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
            $wrestler->load('employments');
        });
    }

    public function withFutureEmployment()
    {
        return $this->state(function (array $attributes) {
            return ['status' => WrestlerStatus::FUTURE_EMPLOYMENT];
        })
        ->has(Employment::factory()->started(Carbon::tomorrow()))
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
            $wrestler->load('employments');
        });
    }

    public function unemployed()
    {
        return $this->state(function (array $attributes) {
            return ['status' => WrestlerStatus::UNEMPLOYED];
        })
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
        });
    }

    public function retired()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => WrestlerStatus::RETIRED];
        })
        ->has(Employment::factory()->started($start)->ended($end))
        ->has(Retirement::factory()->started($end))
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
            $wrestler->load('employments');
            $wrestler->load('retirements');
        });
    }

    public function released()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => WrestlerStatus::RELEASED];
        })
        ->has(Employment::factory()->started($start)->ended($end))
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
            $wrestler->load('employments');
        });
    }

    public function suspended()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => WrestlerStatus::SUSPENDED];
        })
        ->has(Employment::factory()->started($start))
        ->has(Suspension::factory()->started($end))
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
            $wrestler->load('employments');
            $wrestler->load('suspensions');
        });
    }

    public function injured()
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(function (array $attributes) {
            return ['status' => WrestlerStatus::INJURED];
        })
        ->has(Employment::factory()->started($start))
        ->has(Injury::factory()->started($now))
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
            $wrestler->load('employments');
            $wrestler->load('injuries');
        });
    }

    public function softDeleted()
    {
        return $this->state(function (array $attributes) {
            return ['deleted_at' => now()];
        })
        ->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
        });
    }
}
