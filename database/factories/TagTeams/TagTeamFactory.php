<?php

declare(strict_types=1);

namespace Database\Factories\TagTeams;

use App\Enums\Shared\EmploymentStatus;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamEmployment;
use App\Models\TagTeams\TagTeamRetirement;
use App\Models\TagTeams\TagTeamSuspension;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerEmployment;
use App\Models\Wrestlers\WrestlerRetirement;
use App\Models\Wrestlers\WrestlerSuspension;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<TagTeam>
 */
class TagTeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Str::title(fake()->words(2, true)),
            'signature_move' => null,
            // Status is now computed from employment relationships
        ];
    }

    /**
     * Set the tag team as employed.
     */
    public function employed(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);

        $wrestlers = Wrestler::factory()->count(2)
            ->has(WrestlerEmployment::factory()->started($employmentStartDate), 'employments')
            ->create();

        return $this->has(TagTeamEmployment::factory()->started($employmentStartDate), 'employments')
            ->withCurrentWrestlers($wrestlers, $employmentStartDate);
    }

    public function bookable(): static
    {
        return $this->employed();
    }

    public function unbookable(): static
    {
        // Create unbookable tag team with no employment history
        return $this->state(fn () => []);
    }

    public function withFutureEmployment(): static
    {
        $employmentStartDate = Carbon::tomorrow();
        $wrestlers = Wrestler::factory()->count(2)
            ->has(WrestlerEmployment::factory()->started($employmentStartDate), 'employments')
            ->create();

        return $this->has(TagTeamEmployment::factory()->started($employmentStartDate), 'employments')
            ->withCurrentWrestlers($wrestlers, Carbon::now());
    }

    public function suspended(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);
        $suspensionStartDate = $now->copy()->subDays(2);
        $wrestlers = Wrestler::factory()->count(2)
            ->has(WrestlerEmployment::factory()->started($employmentStartDate), 'employments')
            ->has(WrestlerSuspension::factory()->started($suspensionStartDate), 'suspensions')
            ->create();

        return $this->has(TagTeamEmployment::factory()->started($employmentStartDate), 'employments')
            ->has(TagTeamSuspension::factory()->started($suspensionStartDate), 'suspensions')
            ->withCurrentWrestlers($wrestlers, $employmentStartDate);
    }

    public function retired(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);
        $retirementStartDate = $now->copy()->subDays(2);
        $wrestlers = Wrestler::factory()->count(2)
            ->has(WrestlerEmployment::factory()->started($employmentStartDate)->ended($retirementStartDate), 'employments')
            ->has(WrestlerRetirement::factory()->started($retirementStartDate), 'retirements')
            ->create();

        return $this->has(TagTeamEmployment::factory()->started($employmentStartDate)->ended($retirementStartDate), 'employments')
            ->has(TagTeamRetirement::factory()->started($retirementStartDate), 'retirements')
            ->withCurrentWrestlers($wrestlers, $employmentStartDate);
    }

    public function unemployed(): static
    {
        return $this->state(fn () => []);
    }

    public function released(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(2);
        $employmentEndDate = $now->copy()->subDays();
        $wrestlers = Wrestler::factory()->count(2)
            ->has(WrestlerEmployment::factory()->started($employmentStartDate)->ended($employmentEndDate), 'employments')
            ->create();

        return $this->has(TagTeamEmployment::factory()->started($employmentStartDate)->ended($employmentEndDate), 'employments')
            ->withCurrentWrestlers($wrestlers, $employmentStartDate);
    }


    public function withCurrentWrestlers($wrestler, $joinDate = null): static
    {
        $this->hasAttached($wrestler, ['joined_at' => $joinDate ?? now(), 'left_at' => null]);

        return $this;
    }

    public function unactivated(): static
    {
        return $this->state(fn () => []);
    }
}
