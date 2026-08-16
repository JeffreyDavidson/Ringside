<?php

declare(strict_types=1);

namespace Database\Factories\Roster\Stables;

use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Retirement;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Roster\Stables\Stable>
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
            'name' => str(fake()->unique()->words(2, true))->title()->value(),
            // Status is now computed from activity periods and retirement state
        ];
    }

    public function withFutureActivation(): static
    {
        return $this->has(ActivityPeriod::factory()->started(Carbon::tomorrow()), 'activityPeriods')
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

        return $this->has(ActivityPeriod::factory()->started($activationDate), 'activityPeriods')
            ->hasAttached(
                Wrestler::factory()->count(2)->has(Employment::factory()->started($activationDate), 'employments'),
                ['joined_at' => $activationDate]
            )
            ->hasAttached(
                TagTeam::factory()
                    ->has(Employment::factory()->started($activationDate), 'employments')
                    ->afterCreating(function (TagTeam $tagTeam) use ($activationDate) {
                        // Attach wrestlers to the tag team to ensure it has active wrestlers
                        $wrestlers = Wrestler::factory()->count(2)
                            ->has(Employment::factory()->started($activationDate), 'employments')
                            ->create();
                        $tagTeam->wrestlers()->attach($wrestlers->pluck('id'), ['joined_at' => $activationDate]);
                    }),
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

        return $this->has(ActivityPeriod::factory()->started($start)->ended($end), 'activityPeriods')
            ->hasAttached(
                Wrestler::factory()->count(2)->has(Employment::factory()->started($start), 'employments'),
                ['joined_at' => $start, 'left_at' => $end]
            )
            ->hasAttached(
                TagTeam::factory()
                    ->has(Employment::factory()->started($start), 'employments')
                    ->afterCreating(function (TagTeam $tagTeam) use ($start) {
                        // Attach wrestlers to the tag team to ensure it has active wrestlers
                        $wrestlers = Wrestler::factory()->count(2)
                            ->has(Employment::factory()->started($start), 'employments')
                            ->create();
                        $tagTeam->wrestlers()->attach($wrestlers->pluck('id'), ['joined_at' => $start]);
                    }),
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

        // Members "left" the stable when it retired but stayed employed and
        // available — that's what makes them eligible "former members" for an
        // unretire / reunite scenario.
        return $this->has(ActivityPeriod::factory()->started($start)->ended($end), 'activityPeriods')
            ->has(Retirement::factory()->started($end), 'retirements')
            ->hasAttached(
                Wrestler::factory()->count(2)
                    ->has(Employment::factory()->started($start), 'employments'),
                ['joined_at' => $start, 'left_at' => $end]
            )
            ->hasAttached(
                TagTeam::factory()
                    ->has(Employment::factory()->started($start), 'employments')
                    ->afterCreating(function (TagTeam $tagTeam) use ($start) {
                        $wrestlers = Wrestler::factory()->count(2)
                            ->has(Employment::factory()->started($start), 'employments')
                            ->create();
                        $tagTeam->wrestlers()->attach($wrestlers->pluck('id'), ['joined_at' => $start]);
                    }),
                ['joined_at' => $start, 'left_at' => $end]
            )
            ->afterCreating(function (Stable $stable) {
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
                Wrestler::factory()->count(2)
                    ->has(Employment::factory()->started(Carbon::yesterday()), 'employments'),
                ['joined_at' => now()]
            )
            ->hasAttached(
                TagTeam::factory()
                    ->has(Employment::factory()->started(Carbon::yesterday()), 'employments')
                    ->afterCreating(function (TagTeam $tagTeam) {
                        // Attach wrestlers to the tag team to ensure it has active wrestlers
                        $wrestlers = Wrestler::factory()->count(2)
                            ->has(Employment::factory()->started(Carbon::yesterday()), 'employments')
                            ->create();
                        $tagTeam->wrestlers()->attach($wrestlers->pluck('id'), ['joined_at' => Carbon::yesterday()]);
                    }),
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
