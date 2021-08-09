<?php

namespace Database\Factories;

use App\Enums\StableStatus;
use App\Models\Activation;
use App\Models\Employment;
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

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this;
            // ->hasAttached(Wrestler::factory()->count(1), ['joined_at' => now()])
            // ->hasAttached(TagTeam::factory()->count(1), ['joined_at' => now()]);
    }

    public function withFutureActivation()
    {
        return $this->state(function (array $attributes) {
            return ['status' => StableStatus::FUTURE_ACTIVATION];
        })
        ->has(Activation::factory()->started(Carbon::tomorrow()));
    }

    public function unactivated()
    {
        return $this->state(function (array $attributes) {
            return ['status' => StableStatus::UNACTIVATED];
        });
    }

    public function active()
    {
        $activationDate = Carbon::yesterday();

        return $this->state(function (array $attributes) {
            return ['status' => StableStatus::ACTIVE];
        })
        ->has(Activation::factory()->started($activationDate))
        ->hasAttached(Wrestler::factory()->bookable()->has(Employment::factory()->started($activationDate)), ['joined_at' => $activationDate])
        ->hasAttached(TagTeam::factory()->bookable()->has(Employment::factory()->started($activationDate)), ['joined_at' => $activationDate])
        ->afterCreating(function (Stable $stable) {
            $stable->updateStatusAndSave();
        });
    }

    public function inactive()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => StableStatus::INACTIVE];
        })
        ->has(Activation::factory()->started($start)->ended($end))
        ->hasAttached(Wrestler::factory()->bookable()->has(Employment::factory()->started($start)), ['joined_at' => $start, 'left_at' => $end])
        ->hasAttached(TagTeam::factory()->bookable()->has(Employment::factory()->started($start)), ['joined_at' => $start, 'left_at' => $end])
        ->afterCreating(function (Stable $stable) {
            $stable->updateStatusAndSave();
            $stable->currentWrestlers->each->updateStatusAndSave();
        });
    }

    public function retired()
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => StableStatus::RETIRED];
        })

        ->hasActivations(1, ['started_at' => $start, 'ended_at' => $end])
        ->hasRetirements(1, ['started_at' => $end])
        ->hasAttached(Wrestler::factory()->count(1)->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])->hasRetirements(1, ['started_at' => $end]), ['joined_at' => $start])
        ->hasAttached(TagTeam::factory()->count(1)->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])->hasRetirements(1, ['started_at' => $end]), ['joined_at' => $start]);
    }

    public function softDeleted()
    {
        return $this->state(function (array $attributes) {
            return ['deleted_at' => now()];
        })
        ->afterCreating(function (Stable $stable) {
            $stable->save();
        });
    }
}
