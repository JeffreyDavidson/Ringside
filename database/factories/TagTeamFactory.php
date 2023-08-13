<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Models\Employment;
use App\Models\Retirement;
use App\Models\Suspension;
use App\Models\Wrestler;
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
            'status' => TagTeamStatus::UNEMPLOYED,
        ];
    }

    public function bookable(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);

        $wrestlers = Wrestler::factory()->count(2)
            ->has(Employment::factory()->started($employmentStartDate))
            ->create();

        return $this->state(fn () => ['status' => TagTeamStatus::BOOKABLE])
            ->has(Employment::factory()->started($employmentStartDate))
            ->withCurrentWrestlers($wrestlers, $employmentStartDate);
    }

    public function unbookable(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);
        $wrestlers = Wrestler::factory()->bookable()->count(1);

        return $this->state(fn () => ['status' => TagTeamStatus::UNBOOKABLE])
            ->has(Employment::factory()->started($employmentStartDate))
            ->hasAttached(Wrestler::factory()->injured(), ['joined_at' => $employmentStartDate])
            ->withCurrentWrestlers($wrestlers, $employmentStartDate);
    }

    public function withFutureEmployment(): static
    {
        $employmentStartDate = Carbon::tomorrow();
        $wrestlers = Wrestler::factory()->count(2)
            ->has(Employment::factory()->started($employmentStartDate))
            ->create();

        return $this->state(fn () => ['status' => TagTeamStatus::FUTURE_EMPLOYMENT])
            ->has(Employment::factory()->started($employmentStartDate))
            ->withCurrentWrestlers($wrestlers, Carbon::now());
    }

    public function suspended(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);
        $suspensionStartDate = $now->copy()->subDays(2);
        $wrestlers = Wrestler::factory()->count(2)
            ->state(fn () => ['status' => WrestlerStatus::SUSPENDED])
            ->has(Employment::factory()->started($employmentStartDate))
            ->has(Suspension::factory()->started($suspensionStartDate))
            ->create();

        return $this->state(fn () => ['status' => TagTeamStatus::SUSPENDED])
            ->has(Employment::factory()->started($employmentStartDate))
            ->has(Suspension::factory()->started($suspensionStartDate))
            ->withCurrentWrestlers($wrestlers, $employmentStartDate);
    }

    public function retired(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);
        $retirementStartDate = $now->copy()->subDays(2);
        $wrestlers = Wrestler::factory()->count(2)
            ->has(Employment::factory()->started($employmentStartDate)->ended($retirementStartDate))
            ->has(Retirement::factory()->started($retirementStartDate))
            ->create();

        return $this->state(fn () => ['status' => TagTeamStatus::RETIRED])
            ->has(Employment::factory()->started($employmentStartDate)->ended($retirementStartDate))
            ->has(Retirement::factory()->started($retirementStartDate))
            ->withCurrentWrestlers($wrestlers, $employmentStartDate);
    }

    public function unemployed(): static
    {
        return $this->state(fn () => ['status' => TagTeamStatus::UNEMPLOYED]);
    }

    public function released(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(2);
        $employmentEndDate = $now->copy()->subDays();
        $wrestlers = Wrestler::factory()->count(2)
            ->has(Employment::factory()->started($employmentStartDate)->ended($employmentEndDate))
            ->create();

        return $this->state(fn () => ['status' => TagTeamStatus::RELEASED])
            ->has(Employment::factory()->started($employmentStartDate)->ended($employmentEndDate))
            ->withCurrentWrestlers($wrestlers, $employmentStartDate);
    }

    public function withCurrentWrestlers($wrestler, $joinDate = null): static
    {
        $this->hasAttached($wrestler, ['joined_at' => $joinDate ?? now()]);

        return $this;
    }
}
