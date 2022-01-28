<?php

namespace Database\Factories;

use App\Enums\TitleStatus;
use App\Models\Activation;
use App\Models\Retirement;
use App\Models\Title;
use App\Models\TitleChampionship;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TitleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $modelClass = Title::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => Str::title($this->faker->unique()->words(2, true)).' Title',
            'status' => TitleStatus::unactivated(),
        ];
    }

    public function active()
    {
        $activationDate = Carbon::yesterday();

        return $this->state(fn (array $attributes) => ['status' => TitleStatus::active()])
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

        return $this->state(fn (array $attributes) => ['status' => TitleStatus::inactive()])
            ->has(Activation::factory()->started($start)->ended($end))
            ->afterCreating(function (Title $title) {
                $title->save();
            });
    }

    public function withFutureActivation()
    {
        return $this->state(fn (array $attributes) => ['status' => TitleStatus::future_activation()])
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

        return $this->state(fn (array $attributes) => ['status' => TitleStatus::retired()])
            ->has(Activation::factory()->started($start)->ended($end))
            ->has(Retirement::factory()->started($end))
            ->afterCreating(function (Title $title) {
                $title->save();
            });
    }

    public function unactivated()
    {
        return $this->state(fn (array $attributes) => ['status' => TitleStatus::unactivated()])->afterCreating(function (Title $title) {
            $title->save();
        });
    }

    public function softDeleted($delete = true)
    {
        return $this->state(fn (array $attributes) => ['deleted_at' => now()])->afterCreating(function (Title $title) {
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
                    TitleStatus::inactive(),
                    TitleStatus::retired(),
                    TitleStatus::future_activation(),
                    TitleStatus::unactivated(),
                ]),
            ];
        });
    }
}
