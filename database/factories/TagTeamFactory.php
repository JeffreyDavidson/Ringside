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
    protected $modelClass = TagTeam::class;

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
        $this->state(['status' => TagTeamStatus::BOOKABLE])
            ->hasEmployments(1, ['started_at' => Carbon::yesterday()])
            ->hasAttached(Wrestler::factory()->count(2)->bookable(), ['joined_at' => Carbon::yesterday()])
            ->afterCreating(function (TagTeam $tagTeam) {
                // $tagTeam->save();
                // $tagTeam->currentWrestlers->each->update(['current_tag_team_id' => $tagTeam->id]);
                // $tagTeam->load('currentWrestlers');
            });

        return $this;
    }

    public function unbookable()
    {
        $this->state(['status' => TagTeamStatus::UNBOOKABLE])
            ->hasEmployments(1, ['started_at' => Carbon::yesterday()])
            ->hasAttached(Wrestler::factory()->count(2)->bookable(), ['joined_at' => Carbon::yesterday()])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
                $tagTeam->currentWrestlers->each->update(['current_tag_team_id' => $tagTeam->id]);
                $tagTeam->load('currentWrestlers');
            });

        return $this;
    }

    public function withFutureEmployment()
    {
        $this->state(['status' => TagTeamStatus::FUTURE_EMPLOYMENT])
            ->hasEmployments(1, ['started_at' => Carbon::tomorrow()])
            ->hasAttached(Wrestler::factory()->count(2)->withFutureEmployment(), ['joined_at' => Carbon::now()])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
                $tagTeam->load('employments');
                $tagTeam->load('currentWrestlers');
            });

        return $this;
    }

    public function suspended()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $this->state(['status' => TagTeamStatus::SUSPENDED])
            ->hasEmployments(1, ['started_at' => $start])
            ->hasSuspensions(1, ['started_at' => $end])
            ->hasAttached(Wrestler::factory()->count(2)->suspended(), ['joined_at' => $start])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
                $tagTeam->load('employments');
                $tagTeam->load('suspensions');
                $tagTeam->load('currentWrestlers');
            });

        return $this;
    }

    public function retired()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $this->state(['status' => TagTeamStatus::RETIRED])
            ->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])
            ->hasRetirements(1, ['started_at' => $end])
            ->hasAttached(Wrestler::factory()->count(2)->retired(), ['joined_at' => $start])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
                $tagTeam->currentWrestlers->each->save();
            });

        return $this;
    }

    public function unemployed()
    {
        $this->state(['status' => TagTeamStatus::UNEMPLOYED])
            ->hasAttached(Wrestler::factory()->count(2), ['joined_at' => Carbon::now()])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
                $tagTeam->currentWrestlers->each->save();
            });

        return $this;
    }

    public function released()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $this->state(['status' => TagTeamStatus::RELEASED])
            ->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])
            ->hasAttached(Wrestler::factory()->count(2)->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end]), ['joined_at' => $start])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
                $tagTeam->currentWrestlers->each->save();
            });

        return $this;
    }

    public function softDeleted()
    {
        $this->state(['deleted_at' => now()])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
            });

        return $this;
    }
}
