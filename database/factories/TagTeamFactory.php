<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TagTeamStatus;
use App\Models\Employment;
use App\Models\Retirement;
use App\Models\Suspension;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TagTeamFactory extends Factory
{
    private $wrestlerA;

    private $wrestlerB;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => Str::title($this->faker->words(2, true)),
            'signature_move' => null,
            'status' => TagTeamStatus::UNEMPLOYED,
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->save();
        });
    }

    public function bookable()
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);

        $wrestlers = Wrestler::factory()->count(2)
            ->has(Employment::factory()->started($employmentStartDate))
            ->create();

        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::BOOKABLE])
            ->has(Employment::factory()->started($employmentStartDate))
            ->hasAttached($wrestlers, ['joined_at' => $employmentStartDate])
            ->afterCreating(function (TagTeam $tagTeam) use ($wrestlers) {
                $tagTeam->save();

                foreach ($wrestlers as $wrestler) {
                    $wrestler->update(['current_tag_team_id' => $tagTeam->id]);
                }
            });
    }

    public function unbookable()
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);

        return $this->state(fn () => ['status' => TagTeamStatus::UNBOOKABLE])
            ->has(Employment::factory()->started($employmentStartDate))
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
            });
    }

    public function withFutureEmployment()
    {
        $employmentStartDate = Carbon::tomorrow();
        $wrestlers = Wrestler::factory()->count(2)
            ->has(Employment::factory()->started($employmentStartDate))->create();

        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::FUTURE_EMPLOYMENT])
            ->has(Employment::factory()->started($employmentStartDate))
            ->hasAttached($wrestlers, ['joined_at' => Carbon::now()])
            ->afterCreating(function (TagTeam $tagTeam) use ($wrestlers) {
                $tagTeam->save();

                foreach ($wrestlers as $wrestler) {
                    $wrestler->update(['current_tag_team_id' => $tagTeam->id]);
                }
            });
    }

    public function suspended()
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);
        $suspensionStartDate = $now->copy()->subDays(2);
        $wrestlers = Wrestler::factory()->count(2)
            ->has(Employment::factory()->started($employmentStartDate))
            ->has(Suspension::factory()->started($suspensionStartDate))
            ->create();

        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::UNBOOKABLE])
            ->has(Employment::factory()->started($employmentStartDate))
            ->has(Suspension::factory()->started($suspensionStartDate))
            ->hasAttached($wrestlers, ['joined_at' => $employmentStartDate])
            ->afterCreating(function (TagTeam $tagTeam) use ($wrestlers) {
                $tagTeam->save();

                foreach ($wrestlers as $wrestler) {
                    $wrestler->update(['current_tag_team_id' => $tagTeam->id]);
                }
            });
    }

    public function retired()
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);
        $retirementStartDate = $now->copy()->subDays(2);
        $wrestlers = Wrestler::factory()->count(2)
            ->has(Employment::factory()->started($employmentStartDate))
            ->has(Retirement::factory()->started($retirementStartDate))
            ->create();

        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::RETIRED])
            ->has(Employment::factory()->started($employmentStartDate)->ended($retirementStartDate))
            ->has(Retirement::factory()->started($retirementStartDate))
            ->hasAttached($wrestlers, ['joined_at' => $employmentStartDate])
            ->afterCreating(function (TagTeam $tagTeam) use ($wrestlers) {
                $tagTeam->save();

                foreach ($wrestlers as $wrestler) {
                    $wrestler->update(['current_tag_team_id' => $tagTeam->id]);
                }
            });
    }

    public function unemployed()
    {
        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::UNEMPLOYED])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
            });
    }

    public function released()
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(2);
        $employmentEndDate = $now->copy()->subDays(1);
        $wrestlers = Wrestler::factory()->count(2)
            ->has(Employment::factory()->started($employmentStartDate)->ended($employmentEndDate))
            ->create();

        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::RELEASED])
            ->has(Employment::factory()->started($employmentStartDate)->ended($employmentEndDate))
            ->hasAttached($wrestlers, ['joined_at' => $employmentStartDate])
            ->afterCreating(function (TagTeam $tagTeam) use ($wrestlers) {
                $tagTeam->save();

                foreach ($wrestlers as $wrestler) {
                    $wrestler->update(['current_tag_team_id' => $tagTeam->id]);
                }
            });
    }

    public function withWrestler($wrestler, $joinDate = null)
    {
        return $this->hasAttached($wrestler, ['joined_at' => $joinDate ?? now()]);
    }
}
