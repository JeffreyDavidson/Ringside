<?php

declare(strict_types=1);

namespace Database\Factories\Titles;

use App\Enums\Titles\TitleType;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Lifecycle\Retirement;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Title>
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
            'name' => $this->generateTitleName($titleType),
            'type' => $titleType,
        ];
    }

    public function active(): static
    {
        $activationDate = Carbon::yesterday();

        return $this
            ->has(ActivityPeriod::factory()->started($activationDate), 'activityPeriods');
    }

    public function inactive(): static
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays();

        return $this
            ->has(ActivityPeriod::factory()->started($start)->ended($end), 'activityPeriods');
    }

    public function withFutureActivation(): static
    {
        return $this
            ->has(ActivityPeriod::factory()->started(Carbon::tomorrow()), 'activityPeriods');
    }

    public function retired(): static
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays();

        return $this
            ->has(ActivityPeriod::factory()->started($start)->ended($end), 'activityPeriods')
            ->has(Retirement::factory()->started($end), 'retirements');
    }

    public function unactivated(): static
    {
        return $this;
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
        return $this->state(fn () => [
            'name' => $this->generateTitleName(TitleType::Singles),
            'type' => TitleType::Singles,
        ]);
    }

    public function tagTeam(): static
    {
        return $this->state(fn () => [
            'name' => $this->generateTitleName(TitleType::TagTeam),
            'type' => TitleType::TagTeam,
        ]);
    }

    public function undebuted(): static
    {
        return $this;
    }

    public function withFutureDebut(): static
    {
        return $this->withFutureActivation();
    }

    public function withCurrentChampion($champion): static
    {
        return $this->withChampion($champion);
    }

    public function withActivationPeriod($startDate = null, $endDate = null): static
    {
        $startDate = $startDate ?? Carbon::yesterday();

        if ($endDate) {
            return $this
                ->has(ActivityPeriod::factory()->started($startDate)->ended($endDate), 'activityPeriods');
        }

        return $this
            ->has(ActivityPeriod::factory()->started($startDate), 'activityPeriods');
    }

    private function generateTitleName(TitleType $titleType): string
    {
        return str(fake()->unique()->words(2, true))
            ->title()
            ->append($titleType === TitleType::Singles ? ' Title' : ' Titles')
            ->toString();
    }
}
