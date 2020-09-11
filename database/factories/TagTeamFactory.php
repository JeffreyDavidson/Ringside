<?php

namespace Database\Factories;

use App\Enums\TagTeamStatus;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagTeamFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected string $modelClass = TagTeam::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => Str::title($this->faker->words(2, true)),
            'signature_move' => Str::title($this->faker->words(4, true)),
            'status' => TagTeamStatus::__default,
        ];
    }

    public function bookable()
    {
        $clone = $this->state([
            'status' => TagTeamStatus::BOOKABLE,
        ]);

        $clone = $clone->hasEmployments(1, ['started_at' => Carbon::yesterday()]);
        $clone = $clone->hasAttached(Wrestler::factory(), ['started_at' => Carbon::yesterday()]);

        return $clone;
    }

    public function withFutureEmployment()
    {
        $clone = $this->state([
            'status' => TagTeamStatus::FUTURE_EMPLOYMENT,
        ]);

        $clone = $clone->hasEmployments(1, ['started_at' => Carbon::tomorrow()]);
        $clone = $clone->hasAttached(Wrestler::factory(), ['started_at' => Carbon::yesterday()]);

        return $clone;
    }

    public function suspended(): self
    {
        $clone = $this->state([
            'status' => TagTeamStatus::SUSPENDED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone = $clone->hasEmployments(1, ['started_at' => $start]);
        $clone = $clone->hasAttached(Wrestler::factory(), ['started_at' => Carbon::yesterday()]);
        $clone = $clone->hasSuspensions(1, ['started_at' => $end]);

        return $clone;
    }

    public function retired(): self
    {
        $clone = $this->state([
            'status' => TagTeamStatus::RETIRED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone = $clone->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end]);
        $clone = $clone->hasRetirements(1, ['started_at' => $end]);

        return $clone;
    }

    public function unemployed(): self
    {
        return $this->state([
            'status' => TagTeamStatus::UNEMPLOYED,
        ]);
    }

    public function released(): self
    {
        $clone = $this->state([
            'status' => TagTeamStatus::RELEASED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone = $clone->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end]);

        return $clone;
    }
}
