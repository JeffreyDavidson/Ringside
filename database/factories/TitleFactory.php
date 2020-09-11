<?php

namespace Database\Factories;

use App\Enums\TitleStatus;
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
    protected string $modelClass = Title::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => Str::title($this->faker->unique()->words(2, true)).' Title',
            'status' => TitleStatus::__default,
        ];
    }

    public function active(): self
    {
        return $this->state([
            'status' => TitleStatus::ACTIVE,
        ])->hasActivations(1, ['started_at' => Carbon::yesterday()])
        ->afterCreating(function (Title $title) {
            $title->save();
        });
    }

    public function inactive(): self
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        return $this->state([
            'status' => TitleStatus::INACTIVE,
        ])->hasActivations(1, ['started_at' => Carbon::yesterday(), 'ended_at' => $end])
        ->afterCreating(function (Title $title) {
            $title->save();
        });
    }

    public function withFutureActivation(): self
    {
        return $this->state([
            'status' => TitleStatus::FUTURE_ACTIVATION,
        ])->hasActivations(1, ['started_at' => Carbon::tomorrow()])
        ->afterCreating(function (Title $title) {
            $title->save();
        });
    }

    public function retired(): self
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        return $this->state([
            'status' => TitleStatus::RETIRED,
        ])->hasActivations(1, ['started_at' => $start, 'ended_at' => $end])
        ->hasRetirements(1, ['started_at' => $end])
        ->afterCreating(function (Title $title) {
            $title->save();
        });
    }

    public function unactivated(): self
    {
        return $this->state([
            'status' => TitleStatus::UNACTIVATED,
        ])->afterCreating(function (Title $title) {
            $title->save();
        });
    }

    public function softDeleted($delete = true)
    {
        return $this->state([
            'deleted_at' => now(),
        ])->afterCreating(function (Title $title) {
            $title->save();
        });
    }
}
