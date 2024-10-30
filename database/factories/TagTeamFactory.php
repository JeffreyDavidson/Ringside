<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Models\Retirement;
use App\Models\Suspension;
use App\Models\TagTeamEmployment;
use App\Models\TagTeamRetirement;
use App\Models\Wrestler;
use App\Models\WrestlerEmployment;
use App\Models\WrestlerRetirement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TagTeam>
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
            'status' => TagTeamStatus::Unemployed,
        ];
    }

    public function bookable(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);

        $wrestlers = Wrestler::factory()->count(2)
            ->has(WrestlerEmployment::factory()->started($employmentStartDate), 'employments')
            ->create();

        return $this->state(fn () => ['status' => TagTeamStatus::Bookable])
            ->has(TagTeamEmployment::factory()->started($employmentStartDate), 'employments')
            ->withCurrentWrestlers($wrestlers, $employmentStartDate);
    }

    public function unbookable(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);
        $wrestlers = Wrestler::factory()->bookable()->count(1);

        return $this->state(fn () => ['status' => TagTeamStatus::Unbookable])
            ->has(TagTeamEmployment::factory()->started($employmentStartDate), 'employments')
            ->hasAttached(Wrestler::factory()->injured(), ['joined_at' => $employmentStartDate])
            ->withCurrentWrestlers($wrestlers, $employmentStartDate);
    }

    public function withFutureEmployment(): static
    {
        $employmentStartDate = Carbon::tomorrow();
        $wrestlers = Wrestler::factory()->count(2)
            ->has(WrestlerEmployment::factory()->started($employmentStartDate), 'employments')
            ->create();

        return $this->state(fn () => ['status' => TagTeamStatus::FutureEmployment])
            ->has(TagTeamEmployment::factory()->started($employmentStartDate), 'employments')
            ->withCurrentWrestlers($wrestlers, Carbon::now());
    }

    public function suspended(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);
        $suspensionStartDate = $now->copy()->subDays(2);
        $wrestlers = Wrestler::factory()->count(2)
            ->state(fn () => ['status' => WrestlerStatus::Suspended])
            ->has(WrestlerEmployment::factory()->started($employmentStartDate), 'employments')
            ->has(Suspension::factory()->started($suspensionStartDate))
            ->create();

        return $this->state(fn () => ['status' => TagTeamStatus::Suspended])
            ->has(TagTeamEmployment::factory()->started($employmentStartDate), 'employments')
            ->has(Suspension::factory()->started($suspensionStartDate))
            ->withCurrentWrestlers($wrestlers, $employmentStartDate);
    }

    public function retired(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);
        $retirementStartDate = $now->copy()->subDays(2);
        $wrestlers = Wrestler::factory()->count(2)
            ->has(WrestlerEmployment::factory()->started($employmentStartDate)->ended($retirementStartDate), 'employments')
            ->has(WrestlerRetirement::factory()->started($retirementStartDate))
            ->create();

        return $this->state(fn () => ['status' => TagTeamStatus::Retired])
            ->has(TagTeamEmployment::factory()->started($employmentStartDate)->ended($retirementStartDate), 'employments')
            ->has(TagTeamRetirement::factory()->started($retirementStartDate))
            ->withCurrentWrestlers($wrestlers, $employmentStartDate);
    }

    public function unemployed(): static
    {
        return $this->state(fn () => ['status' => TagTeamStatus::Unemployed]);
    }

    public function released(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(2);
        $employmentEndDate = $now->copy()->subDays();
        $wrestlers = Wrestler::factory()->count(2)
            ->has(WrestlerEmployment::factory()->started($employmentStartDate)->ended($employmentEndDate), 'employments')
            ->create();

        return $this->state(fn () => ['status' => TagTeamStatus::Released])
            ->has(TagTeamEmployment::factory()->started($employmentStartDate)->ended($employmentEndDate), 'employments')
            ->withCurrentWrestlers($wrestlers, $employmentStartDate);
    }

    public function withCurrentWrestlers($wrestler, $joinDate = null): static
    {
        $this->hasAttached($wrestler, ['joined_at' => $joinDate ?? now()]);

        return $this;
    }
}
