<?php

namespace Database\Factories;

use App\Enums\StableStatus;
use App\Models\Activation;
use App\Models\Employment;
use App\Models\Retirement;
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
            'status' => StableStatus::unactivated(),
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
    }

    public function withFutureActivation()
    {
        return $this->state(fn (array $attributes) => ['status' => StableStatus::future_activation()])
        ->has(Activation::factory()->started(Carbon::tomorrow()))
        ->hasAttached(Wrestler::factory()->has(Employment::factory()->started(Carbon::tomorrow())), ['joined_at' => now()])
        ->hasAttached(TagTeam::factory()->has(Employment::factory()->started(Carbon::tomorrow())), ['joined_at' => now()])
        ->afterCreating(function (Stable $stable) {
            $stable->currentWrestlers->each(function ($wrestler) {
                $wrestler->updateStatus()->save();
            });
            $stable->currentTagTeams->each(function ($tagTeam) {
                $tagTeam->updateStatus()->save();
            });
            $stable->updateStatus()->save();
        });
    }

    public function unactivated()
    {
        return $this->state(fn (array $attributes) => ['status' => StableStatus::unactivated()])
        ->hasAttached(Wrestler::factory()->unemployed(), ['joined_at' => now()])
        ->hasAttached(TagTeam::factory()->unemployed(), ['joined_at' => now()]);
    }

    public function active()
    {
        $activationDate = Carbon::yesterday();

        return $this->state(fn (array $attributes) => ['status' => StableStatus::active()])
        ->has(Activation::factory()->started($activationDate))
        ->hasAttached(Wrestler::factory()->has(Employment::factory()->started($activationDate)), ['joined_at' => $activationDate])
        ->hasAttached(TagTeam::factory()->has(Employment::factory()->started($activationDate)), ['joined_at' => $activationDate])
        ->afterCreating(function (Stable $stable) {
            $stable->currentWrestlers->each(function ($wrestler) {
                $wrestler->updateStatus()->save();
            });
            $stable->currentTagTeams->each(function ($tagTeam) {
                $tagTeam->updateStatus()->save();
            });
            $stable->updateStatus()->save();
        });
    }

    public function inactive()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(fn (array $attributes) => ['status' => StableStatus::inactive()])
        ->has(Activation::factory()->started($start)->ended($end))
        ->hasAttached(Wrestler::factory()->has(Employment::factory()->started($start)), ['joined_at' => $start, 'left_at' => $end])
        ->hasAttached(TagTeam::factory()->has(Employment::factory()->started($start)), ['joined_at' => $start, 'left_at' => $end])
        ->afterCreating(function (Stable $stable) {
            $stable->currentWrestlers->each(function ($wrestler) {
                $wrestler->updateStatus()->save();
            });
            $stable->currentTagTeams->each(function ($tagTeam) {
                $tagTeam->updateStatus()->save();
            });
            $stable->updateStatus()->save();
        });
    }

    public function retired()
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        return $this->state(fn (array $attributes) => ['status' => StableStatus::retired()])
        ->hasActivations(1, ['started_at' => $start, 'ended_at' => $end])
        ->hasRetirements(1, ['started_at' => $end])
        ->hasAttached(Wrestler::factory()->has(Employment::factory()->started($start)->ended($end))->has(Retirement::factory()->started($end)), ['joined_at' => $start])
        ->hasAttached(TagTeam::factory()->has(Employment::factory()->started($start)->ended($end))->has(Retirement::factory()->started($end)), ['joined_at' => $start])
        ->afterCreating(function (Stable $stable) {
            $stable->currentWrestlers->each(function ($wrestler) {
                $wrestler->updateStatus()->save();
            });
            $stable->currentTagTeams->each(function ($tagTeam) {
                $tagTeam->updateStatus()->save();
            });
            $stable->updateStatus()->save();
        });
    }

    public function softDeleted()
    {
        return $this->state(fn (array $attributes) => ['deleted_at' => now()])
        ->afterCreating(function (Stable $stable) {
            $stable->save();
        });
    }
}
