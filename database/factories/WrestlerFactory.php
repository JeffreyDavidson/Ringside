<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WrestlerStatus;
use App\Models\Employment;
use App\Models\Injury;
use App\Models\Retirement;
use App\Models\Suspension;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class WrestlerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => $this->faker->name(),
            'height' => $this->faker->numberBetween(60, 95),
            'weight' => $this->faker->numberBetween(180, 500),
            'hometown' => $this->faker->city().', '.$this->faker->state(),
            'signature_move' => null,
            'status' => WrestlerStatus::UNEMPLOYED,
            'current_tag_team_id' => null,
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Wrestler $wrestler) {
            $wrestler->save();
        });
    }

    public function bookable()
    {
        return $this->state(fn (array $attributes) => ['status' => WrestlerStatus::BOOKABLE])
            ->has(Employment::factory()->started(Carbon::yesterday()));
    }

    public function withFutureEmployment()
    {
        return $this->state(fn (array $attributes) => ['status' => WrestlerStatus::FUTURE_EMPLOYMENT])
            ->has(Employment::factory()->started(Carbon::tomorrow()));
    }

    public function unemployed()
    {
        return $this->state(fn (array $attributes) => ['status' => WrestlerStatus::UNEMPLOYED]);
    }

    public function retired()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(fn (array $attributes) => ['status' => WrestlerStatus::RETIRED])
            ->has(Employment::factory()->started($start)->ended($end))
            ->has(Retirement::factory()->started($end));
    }

    public function released()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(fn (array $attributes) => ['status' => WrestlerStatus::RELEASED])
            ->has(Employment::factory()->started($start)->ended($end));
    }

    public function suspended()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(fn (array $attributes) => ['status' => WrestlerStatus::SUSPENDED])
            ->has(Employment::factory()->started($start))
            ->has(Suspension::factory()->started($end));
    }

    public function injured()
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(fn (array $attributes) => ['status' => WrestlerStatus::INJURED])
            ->has(Employment::factory()->started($start))
            ->has(Injury::factory()->started($now));
    }
}
