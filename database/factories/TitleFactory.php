<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TitleStatus;
use App\Models\Activation;
use App\Models\Retirement;
use App\Models\Title;
use App\Models\TitleChampionship;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TitleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => str($this->faker->unique()->words(2, true))->title().' Title',
            'status' => TitleStatus::UNACTIVATED,
        ];
    }

    public function active()
    {
        $activationDate = Carbon::yesterday();

        return $this->state(fn (array $attributes) => ['status' => TitleStatus::ACTIVE])
            ->has(Activation::factory()->started($activationDate))
            ->afterCreating(function (Title $title) {
                $title->save();
            });
    }

    public function inactive()
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        return $this->state(fn (array $attributes) => ['status' => TitleStatus::INACTIVE])
            ->has(Activation::factory()->started($start)->ended($end))
            ->afterCreating(function (Title $title) {
                $title->save();
            });
    }

    public function withFutureActivation()
    {
        return $this->state(fn (array $attributes) => ['status' => TitleStatus::FUTURE_ACTIVATION])
            ->has(Activation::factory()->started(Carbon::tomorrow()))
            ->afterCreating(function (Title $title) {
                $title->save();
            });
    }

    public function retired()
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        return $this->state(fn (array $attributes) => ['status' => TitleStatus::RETIRED])
            ->has(Activation::factory()->started($start)->ended($end))
            ->has(Retirement::factory()->started($end))
            ->afterCreating(function (Title $title) {
                $title->save();
            });
    }

    public function unactivated()
    {
        return $this->state(fn (array $attributes) => ['status' => TitleStatus::UNACTIVATED])->afterCreating(function (Title $title) {
            $title->save();
        });
    }

    public function withChampion($champion)
    {
        return $this->has(
            TitleChampionship::factory()->for($champion, 'champion'),
            'championships'
        );
    }

    public function nonActive()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => $this->faker->randomElement([
                    TitleStatus::INACTIVE,
                    TitleStatus::RETIRED,
                    TitleStatus::FUTURE_ACTIVATION,
                    TitleStatus::UNACTIVATED,
                ]),
            ];
        });
    }
}
