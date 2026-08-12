<?php

declare(strict_types=1);

namespace Database\Factories\Lifecycle;

use App\Models\Lifecycle\Retirement;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<Retirement> */
class RetirementFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'started_at' => Carbon::yesterday(),
            'ended_at' => null,
        ];
    }

    public function forRandomRetirable(): static
    {
        $retirableFactory = fake()->randomElement([
            Wrestler::factory(),
            Manager::factory(),
            Referee::factory(),
            TagTeam::factory(),
            Stable::factory(),
            Title::factory(),
        ]);

        return $this->for($retirableFactory, 'retirable');
    }

    public function current(): static
    {
        return $this->state(fn (): array => [
            'started_at' => Carbon::yesterday(),
            'ended_at' => null,
        ]);
    }

    public function started(Carbon $startedAt): static
    {
        return $this->state(fn (): array => ['started_at' => $startedAt]);
    }

    public function ended(Carbon $endedAt): static
    {
        return $this->state(fn (): array => ['ended_at' => $endedAt]);
    }
}
