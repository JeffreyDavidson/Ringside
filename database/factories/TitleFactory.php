<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TitleStatus;
use App\Models\Activation;
use App\Models\Retirement;
use App\Models\TitleChampionship;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Title>
 */
class TitleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => str(fake()->unique()->words(2, true))->title().' Title',
            'status' => TitleStatus::UNACTIVATED,
        ];
    }

    public function active(): static
    {
        $activationDate = Carbon::yesterday();

        return $this->state(fn () => ['status' => TitleStatus::ACTIVE])
            ->has(Activation::factory()->started($activationDate));
    }

    public function inactive(): static
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => TitleStatus::INACTIVE])
            ->has(Activation::factory()->started($start)->ended($end));
    }

    public function withFutureActivation(): static
    {
        return $this->state(fn () => ['status' => TitleStatus::FUTURE_ACTIVATION])
            ->has(Activation::factory()->started(Carbon::tomorrow()));
    }

    public function retired(): static
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => TitleStatus::RETIRED])
            ->has(Activation::factory()->started($start)->ended($end))
            ->has(Retirement::factory()->started($end));
    }

    public function unactivated(): static
    {
        return $this->state(fn () => ['status' => TitleStatus::UNACTIVATED]);
    }

    public function withChampion($champion): static
    {
        return $this->has(
            TitleChampionship::factory()->for($champion, 'champion'),
            'championships'
        );
    }

    public function nonActive(): static
    {
        return $this->state(function () {
            return [
                'status' => fake()->randomElement([
                    TitleStatus::INACTIVE,
                    TitleStatus::RETIRED,
                    TitleStatus::FUTURE_ACTIVATION,
                    TitleStatus::UNACTIVATED,
                ]),
            ];
        });
    }
}
