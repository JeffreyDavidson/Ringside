<?php

declare(strict_types=1);

namespace Database\Factories\Roster\TagTeams;

use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Retirement;
use App\Models\Lifecycle\Suspension;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
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

        return $this->has(Employment::factory()->started($employmentStartDate), 'employments')
            ->withCurrentWrestlers(
                Wrestler::factory()->count(2)
                    ->has(Employment::factory()->started($employmentStartDate), 'employments'),
                $employmentStartDate,
            );
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

        return $this->has(Employment::factory()->started($employmentStartDate), 'employments')
            ->withCurrentWrestlers(
                Wrestler::factory()->count(2)
                    ->has(Employment::factory()->started($employmentStartDate), 'employments'),
                $employmentStartDate,
            );
    }

    public function futureEmployment(): static
    {
        return $this->withFutureEmployment();
    }

    public function suspended(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);
        $suspensionStartDate = $now->copy()->subDays(2);

        return $this->has(Employment::factory()->started($employmentStartDate), 'employments')
            ->has(Suspension::factory()->started($suspensionStartDate), 'suspensions')
            ->withCurrentWrestlers(
                Wrestler::factory()->count(2)
                    ->has(Employment::factory()->started($employmentStartDate), 'employments')
                    ->has(Suspension::factory()->started($suspensionStartDate), 'suspensions'),
                $employmentStartDate,
            );
    }

    public function retired(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(3);
        $retirementStartDate = $now->copy()->subDays(2);

        return $this->has(Employment::factory()->started($employmentStartDate)->ended($retirementStartDate), 'employments')
            ->has(Retirement::factory()->started($retirementStartDate), 'retirements')
            ->withCurrentWrestlers(
                Wrestler::factory()->count(2)
                    ->has(Employment::factory()->started($employmentStartDate)->ended($retirementStartDate), 'employments')
                    ->has(Retirement::factory()->started($retirementStartDate), 'retirements'),
                $employmentStartDate,
            );
    }

    public function unemployed(): static
    {
        return $this->withCurrentWrestlers(Wrestler::factory()->count(2));
    }

    public function released(): static
    {
        $now = now();
        $employmentStartDate = $now->copy()->subDays(2);
        $employmentEndDate = $now->copy()->subDays();

        return $this->has(Employment::factory()->started($employmentStartDate)->ended($employmentEndDate), 'employments')
            ->withCurrentWrestlers(
                Wrestler::factory()->count(2)
                    ->has(Employment::factory()->started($employmentStartDate)->ended($employmentEndDate), 'employments'),
                $employmentStartDate,
            );
    }

    public function withCurrentWrestlers($wrestlers, $joinDate = null): static
    {
        return $this->hasAttached($wrestlers, ['joined_at' => $joinDate ?? now(), 'left_at' => null]);
    }

    public function unactivated(): static
    {
        return $this->state(fn () => []);
    }
}
