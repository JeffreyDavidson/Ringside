<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivationStatus;
use App\Enums\TitleType;
use App\Models\TitleActivation;
use App\Models\TitleChampionship;
use App\Models\TitleRetirement;
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
        $titleType = fake()->randomElement(TitleType::cases());

        return [
            'name' => str(fake()->unique()->words(2, true))->title()->append($titleType->value === 'singles' ? ' Title' : ' Titles'),
            'status' => ActivationStatus::Unactivated,
            'type' => $titleType,
        ];
    }

    public function active(): static
    {
        $activationDate = Carbon::yesterday();

        return $this->state(fn () => ['status' => ActivationStatus::Active])
            ->has(TitleActivation::factory()->started($activationDate), 'activations');
    }

    public function inactive(): static
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => ActivationStatus::Inactive])
            ->has(TitleActivation::factory()->started($start)->ended($end), 'activations');
    }

    public function withFutureActivation(): static
    {
        return $this->state(fn () => ['status' => ActivationStatus::FutureActivation])
            ->has(TitleActivation::factory()->started(Carbon::tomorrow()), 'activations');
    }

    public function retired(): static
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays();

        return $this->state(fn () => ['status' => ActivationStatus::Retired])
            ->has(TitleActivation::factory()->started($start)->ended($end), 'activations')
            ->has(TitleRetirement::factory()->started($end), 'retirements');
    }

    public function unactivated(): static
    {
        return $this->state(fn () => ['status' => ActivationStatus::Unactivated]);
    }

    public function withChampion($champion): static
    {
        return $this->has(
            TitleChampionship::factory()->for($champion, 'champion'),
            'championships'
        );
    }

    public function singles(): static
    {
        return $this->state(fn () => ['type' => TitleType::Singles]);
    }

    public function tagTeam(): static
    {
        return $this->state(fn () => ['type' => TitleType::TagTeam]);
    }
}
