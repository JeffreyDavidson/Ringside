<?php

declare(strict_types=1);

namespace Database\Factories\Stables;

use App\Enums\Stables\StableStatus;
use App\Models\Stables\Stable;
use App\Models\Stables\StableActivation;
use App\Models\Stables\StableRetirement;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamEmployment;
use App\Models\TagTeams\TagTeamRetirement;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerEmployment;
use App\Models\Wrestlers\WrestlerRetirement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stable>
 */
class StableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => str(fake()->words(2, true))->title()->value(),
            // Status is now computed from activity periods and retirement state
        ];
    }

    public function withFutureActivation(): static
    {
        return $this->has(StableActivation::factory()->started(Carbon::tomorrow()), 'activations')
            ->afterCreating(function (Stable $stable) {
                $stable->currentWrestlers->each(function ($wrestler) {
                    $wrestler->save();
                });
                $stable->currentTagTeams->each(function ($tagTeam) {
                    $tagTeam->save();
                });
                $stable->save();
            });
    }

    public function unactivated(): static
    {
        return $this->state(fn () => []);
    }

    public function active(): static
    {
        $activationDate = Carbon::yesterday();

        return $this->has(StableActivation::factory()->started($activationDate), 'activations')
            ->hasAttached(
                Wrestler::factory()->has(WrestlerEmployment::factory()->started($activationDate), 'employments'),
                ['joined_at' => $activationDate]
            )
            ->hasAttached(
                TagTeam::factory()->has(TagTeamEmployment::factory()->started($activationDate), 'employments'),
                ['joined_at' => $activationDate]
            )
            ->afterCreating(function (Stable $stable) {
                $stable->currentWrestlers->each(function ($wrestler) {
                    $wrestler->save();
                });
                $stable->currentTagTeams->each(function ($tagTeam) {
                    $tagTeam->save();
                });
                $stable->save();
            });
    }

    public function inactive(): static
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays();

        return $this->has(StableActivation::factory()->started($start)->ended($end), 'activations')
            ->hasAttached(
                Wrestler::factory()->has(WrestlerEmployment::factory()->started($start), 'employments'),
                ['joined_at' => $start, 'left_at' => $end]
            )
            ->hasAttached(
                TagTeam::factory()->has(TagTeamEmployment::factory()->started($start), 'employments'),
                ['joined_at' => $start, 'left_at' => $end]
            )
            ->afterCreating(function (Stable $stable) {
                $stable->currentWrestlers->each(function ($wrestler) {
                    $wrestler->save();
                });
                $stable->currentTagTeams->each(function ($tagTeam) {
                    $tagTeam->save();
                });
                $stable->save();
            });
    }

    public function retired(): static
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays();

        return $this->has(StableActivation::factory()->started($start)->ended($end), 'activations')
            ->has(StableRetirement::factory()->started($end), 'retirements')
            ->hasAttached(
                Wrestler::factory()
                    ->has(WrestlerEmployment::factory()->started($start)->ended($end), 'employments')
                    ->has(WrestlerRetirement::factory()->started($end), 'retirements'),
                ['joined_at' => $start]
            )
            ->hasAttached(
                TagTeam::factory()
                    ->has(TagTeamEmployment::factory()->started($start)->ended($end), 'employments')
                    ->has(TagTeamRetirement::factory()->started($end), 'retirements'),
                ['joined_at' => $start]
            )
            ->afterCreating(function (Stable $stable) {
                $stable->currentWrestlers->each(function ($wrestler) {
                    $wrestler->save();
                });
                $stable->currentTagTeams->each(function ($tagTeam) {
                    $tagTeam->save();
                });
                $stable->save();
            });
    }

    public function withNoMembers(): static
    {
        return $this->afterCreating(function (Stable $stable) {
            $stable->save();
        });
    }

    public function withEmployedDefaultMembers(): static
    {
        return $this
            ->hasAttached(
                Wrestler::factory()
                    ->has(WrestlerEmployment::factory()->started(Carbon::yesterday()), 'employments'),
                ['joined_at' => now()]
            )
            ->hasAttached(
                TagTeam::factory()
                    ->has(TagTeamEmployment::factory()->started(Carbon::yesterday()), 'employments'),
                ['joined_at' => now()]
            )
            ->afterCreating(function (Stable $stable) {
                $stable->save();
            });
    }

    public function disbanded(): static
    {
        return $this->inactive();
    }

    public function withUnemployedDefaultMembers(): static
    {
        return $this
            ->hasAttached(Wrestler::factory()->unemployed(), ['joined_at' => now()])
            ->hasAttached(TagTeam::factory()->unemployed(), ['joined_at' => now()])
            ->afterCreating(function (Stable $stable) {
                $stable->save();
            });
    }
}
