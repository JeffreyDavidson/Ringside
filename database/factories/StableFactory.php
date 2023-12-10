<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StableStatus;
use App\Models\Activation;
use App\Models\Employment;
use App\Models\Manager;
use App\Models\Retirement;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
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
            'status' => StableStatus::Unactivated,
        ];
    }

    public function withFutureActivation(): static
    {
        return $this->state(fn () => ['status' => StableStatus::FutureActivation])
            ->has(Activation::factory()->started(Carbon::tomorrow()))
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
        return $this->state(fn () => ['status' => StableStatus::Unactivated]);
    }

    public function active(): static
    {
        $activationDate = Carbon::yesterday();

        return $this->state(fn () => ['status' => StableStatus::Active])
            ->has(Activation::factory()->started($activationDate))
            ->hasAttached(Wrestler::factory()->has(Employment::factory()->started($activationDate)), ['joined_at' => $activationDate])
            ->hasAttached(TagTeam::factory()->has(Employment::factory()->started($activationDate)), ['joined_at' => $activationDate])
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

        return $this->state(fn () => ['status' => StableStatus::Inactive])
            ->has(Activation::factory()->started($start)->ended($end))
            ->hasAttached(Wrestler::factory()->has(Employment::factory()->started($start)), ['joined_at' => $start, 'left_at' => $end])
            ->hasAttached(TagTeam::factory()->has(Employment::factory()->started($start)), ['joined_at' => $start, 'left_at' => $end])
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

        return $this->state(fn () => ['status' => StableStatus::Retired])
            ->has(Activation::factory()->started($start)->ended($end))
            ->has(Retirement::factory()->started($end))
            ->hasAttached(Wrestler::factory()->has(Employment::factory()->started($start)->ended($end))->has(Retirement::factory()->started($end)), ['joined_at' => $start])
            ->hasAttached(TagTeam::factory()->has(Employment::factory()->started($start)->ended($end))->has(Retirement::factory()->started($end)), ['joined_at' => $start])
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
            ->hasAttached(Wrestler::factory()->has(Employment::factory()->started(Carbon::yesterday())), ['joined_at' => now()])
            ->hasAttached(TagTeam::factory()->has(Employment::factory()->started(Carbon::yesterday())), ['joined_at' => now()])
            ->hasAttached(Manager::factory()->has(Employment::factory()->started(Carbon::yesterday())), ['joined_at' => now()])
            ->afterCreating(function (Stable $stable) {
                $stable->save();
            });
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
