<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StableStatus;
use App\Models\Activation;
use App\Models\Employment;
use App\Models\Retirement;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class StableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => str($this->faker->words(2, true))->title(),
            'status' => StableStatus::UNACTIVATED,
        ];
    }

    public function withFutureActivation()
    {
        return $this->state(fn (array $attributes) => ['status' => StableStatus::FUTURE_ACTIVATION])
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

    public function unactivated()
    {
        return $this->state(fn (array $attributes) => ['status' => StableStatus::UNACTIVATED]);
    }

    public function active()
    {
        $activationDate = Carbon::yesterday();

        return $this->state(fn (array $attributes) => ['status' => StableStatus::ACTIVE])
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

    public function inactive()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(fn (array $attributes) => ['status' => StableStatus::INACTIVE])
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

    public function retired()
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        return $this->state(fn (array $attributes) => ['status' => StableStatus::RETIRED])
            ->hasActivations(1, ['started_at' => $start, 'ended_at' => $end])
            ->hasRetirements(1, ['started_at' => $end])
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

    public function withNoMembers()
    {
        return $this->afterCreating(function (Stable $stable) {
            $stable->save();
        });
    }

    public function withEmployedDefaultMembers()
    {
        return $this
            ->hasAttached(Wrestler::factory()->has(Employment::factory()->started(Carbon::yesterday())), ['joined_at' => now()])
            ->hasAttached(TagTeam::factory()->has(Employment::factory()->started(Carbon::yesterday())), ['joined_at' => now()])
            ->afterCreating(function (Stable $stable) {
                $stable->save();
            });
    }

    public function withUnemployedDefaultMembers()
    {
        return $this
            ->hasAttached(Wrestler::factory()->unemployed(), ['joined_at' => now()])
            ->hasAttached(TagTeam::factory()->unemployed(), ['joined_at' => now()])
            ->afterCreating(function (Stable $stable) {
                $stable->save();
            });
    }
}
