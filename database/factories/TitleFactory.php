<?php

namespace Database\Factories;

use App\Enums\TitleStatus;
use App\Models\Activation;
use App\Models\Retirement;
use App\Models\Title;
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
            'status' => TitleStatus::__default,
        ];
    }

    public function active()
    {
        $activationDate = Carbon::yesterday();

        return $this->state(function (array $attributes) {
            return ['status' => TitleStatus::ACTIVE];
        })
        ->has(Activation::factory()->started($activationDate))
        ->afterCreating(function (Title $title) {
            $title->updateStatus()->save();
        });
    }

    public function inactive()
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => TitleStatus::INACTIVE];
        })
        ->has(Activation::factory()->started($start)->ended($end))
        ->afterCreating(function (Title $title) {
            $title->updateStatus()->save();
        });
    }

    public function withFutureActivation()
    {
        return $this->state(function (array $attributes) {
            return ['status' => TitleStatus::FUTURE_ACTIVATION];
        })
        ->has(Activation::factory()->started(Carbon::tomorrow()))
        ->afterCreating(function (Title $title) {
            $title->updateStatus()->save();
        });
    }

    public function retired()
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => TitleStatus::RETIRED];
        })
        ->has(Activation::factory()->started($start)->ended($end))
        ->has(Retirement::factory()->started($end))
        ->afterCreating(function (Title $title) {
            $title->updateStatus()->save();
        });
    }

    public function unactivated()
    {
        return $this->state(function (array $attributes) {
            return ['status' => TitleStatus::UNACTIVATED];
        })->afterCreating(function (Title $title) {
            $title->updateStatus()->save();
        });
    }

    public function softDeleted($delete = true)
    {
        return $this->state(function (array $attributes) {
            return ['deleted_at' => now()];
        })->afterCreating(function (Title $title) {
            $title->updateStatus()->save();
        });
    }
}
