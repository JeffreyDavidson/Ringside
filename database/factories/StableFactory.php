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
    protected string $modelClass = Stable::class;

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
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => StableStatus::FUTURE_ACTIVATION,
        ]);

        $clone = $clone->withFactory(ActivationFactory::new()->started(Carbon::tomorrow()), 'activations', 1);

        return $clone;
    }

    public function unactivated()
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => StableStatus::UNACTIVATED,
        ]);
    }

    public function active(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => StableStatus::ACTIVE,
        ]);

        $clone = $clone->withFactory(ActivationFactory::new()->started(Carbon::yesterday()), 'activations', 1);
        $clone->withMembers();

        return $clone;
    }

    public function inactive(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => StableStatus::INACTIVE,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone = $clone->withFactory(ActivationFactory::new()->started($start)->ended($end), 'activations', 1);

        return $clone;
    }

    public function retired(): self
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        return $this->state([
            'status' => StableStatus::RETIRED,
        ])->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])
        ->hasRetirements(1, ['started_at' => $end])
        ->hasAttached(
            Wrestler::factory()->count(1)
                ->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])
                ->hasRetirements(1, ['started_at' => $end]),
            ['joined_at' => $start]
        )
        ->hasAttached(
            TagTeam::factory()->count(1)
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
