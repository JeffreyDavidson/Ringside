<?php

namespace Database\Factories;

use App\Enums\StableStatus;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StableFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $modelClass = Stable::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => Str::title($this->faker->words(2, true)),
            'status' => StableStatus::__default,
        ];
    }

    public function withFutureActivation(): self
    {
        return $this->state([
            'status' => StableStatus::FUTURE_ACTIVATION,
        ])->hasActivations(1, ['started_at' => Carbon::tomorrow()])
        ->afterCreating(function (Stable $stable) {
            $stable->save();
        });
    }

    public function unactivated()
    {
        return $this->state([
            'status' => StableStatus::UNACTIVATED,
        ])->afterCreating(function (Stable $stable) {
            $stable->save();
        });
    }

    public function active(): self
    {
        $start = Carbon::yesterday();

        return $this->state([
            'status' => StableStatus::ACTIVE,
        ])->hasActivations(1, ['started_at' => $start])
        // ->hasAttached(
        //     Wrestler::factory()
        //         ->bookable()
        //         ->count(1)
        //         ->hasEmployments(
        //             1,
        //             ['started_at' => $start]
        //         ),
        //     ['joined_at' => $start]
        // )
        ->hasAttached(
            TagTeam::factory()
                ->bookable()
                ->count(1)
                ->hasEmployments(
                    1,
                    ['started_at' => $start]
                ),
            ['joined_at' => $start]
        )
        ->afterCreating(function (Stable $stable) {
            // $stable->currentTagTeams->each->update(['current_stable_id' => $stable->id]);
            // $stable->currentWrestlers->each->update(['current_stable_id' => $stable->id]);
            $stable->updateStatusAndSave();
        });
    }

    public function inactive(): self
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state([
            'status' => StableStatus::INACTIVE,
        ])->hasActivations(1, ['started_at' => $start, 'ended_at' => $end])
        ->hasAttached(
            Wrestler::factory()
                ->bookable()
                ->count(1)
                ->hasEmployments(
                    1,
                    ['started_at' => $start]
                ),
            ['joined_at' => $start, 'left_at' => $end]
        )
        ->hasAttached(
            TagTeam::factory()
                ->bookable()
                ->count(1)
                ->hasEmployments(
                    1,
                    ['started_at' => $start]
                ),
            ['joined_at' => $start, 'left_at' => $end]
        )
        ->afterCreating(function (Stable $stable) {
            $stable->save();
        });
    }

    public function retired(): self
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        return $this->state([
            'status' => StableStatus::RETIRED,
        ])->hasActivations(1, ['started_at' => $start, 'ended_at' => $end])
        ->hasRetirements(1, ['started_at' => $end])
        ->hasAttached(
            Wrestler::factory()
                ->count(1)
                ->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])
                ->hasRetirements(1, ['started_at' => $end]),
            ['joined_at' => $start]
        )
        ->hasAttached(
            TagTeam::factory()
                ->count(1)
                ->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])
                ->hasRetirements(1, ['started_at' => $end]),
            ['joined_at' => $start]
        )
        ->afterCreating(function (Stable $stable) {
            $stable->save();
        });
    }

    public function softDeleted($delete = true)
    {
        return $this->state([
            'deleted_at' => now(),
        ])->afterCreating(function (Stable $stable) {
            $stable->save();
        });
    }
}
