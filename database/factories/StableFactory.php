<?php

namespace Database\Factories;

use App\Enums\StableStatus;
use App\Models\Activation;
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
        $this->state(['status' => StableStatus::FUTURE_ACTIVATION])
            ->hasActivations(1, ['started_at' => Carbon::tomorrow()]);

        return $this;
    }

    public function unactivated()
    {
        $this->state(['status' => StableStatus::UNACTIVATED]);

        return $this;
    }

    public function active()
    {
        $activationDate = Carbon::yesterday();

        $this->state(['status' => StableStatus::ACTIVE])
            ->hasAttached(Activation::factory(), ['started_at' => $activationDate->toDateTimeString()])
            ->hasActivations(1, ['started_at' => $activationDate->toDateTimeString()])
            ->hasAttached(Wrestler::factory()->bookable()->hasEmployments(1, ['started_at' => $activationDate])->count(1), ['joined_at' => $activationDate])
            ->hasAttached(TagTeam::factory()->bookable()->hasEmployments(1, ['started_at' => $activationDate])->count(1), ['joined_at' => $activationDate])
            ->afterCreating(function (Stable $stable) {
                $stable->updateStatusAndSave();
            });

        return $this;
    }

    public function inactive()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(['status' => StableStatus::INACTIVE])
            ->has(Activation::factory()->started($start)->ended($end))
            ->hasActivations(1, ['started_at' => $start, 'ended_at' => $end])
            ->hasAttached(Wrestler::factory()->bookable()->count(1)->hasEmployments(1, ['started_at' => $start]), ['joined_at' => $start, 'left_at' => $end])
            ->hasAttached(TagTeam::factory()->bookable()->count(1)->hasEmployments(1, ['started_at' => $start]), ['joined_at' => $start, 'left_at' => $end])
            ->afterCreating(function (Stable $stable) {
                $stable->updateStatusAndSave();
            });
    }

    public function retired()
    {
        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        $this->state(['status' => StableStatus::RETIRED])
            ->hasActivations(1, ['started_at' => $start, 'ended_at' => $end])
            ->hasRetirements(1, ['started_at' => $end])
            ->hasAttached(Wrestler::factory()->count(1)->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])->hasRetirements(1, ['started_at' => $end]), ['joined_at' => $start])
            ->hasAttached(TagTeam::factory()->count(1)->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])->hasRetirements(1, ['started_at' => $end]), ['joined_at' => $start]);

        return $this;
    }

    public function softDeleted($delete = true)
    {
        $this->state(['deleted_at' => now()])
            ->afterCreating(function (Stable $stable) {
                $stable->save();
            });

        return $this;
    }
}
