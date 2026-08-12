<?php

declare(strict_types=1);

namespace Database\Factories\Lifecycle;

use App\Models\Lifecycle\Suspension;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<Suspension> */
class SuspensionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'started_at' => Carbon::yesterday(),
            'ended_at' => null,
        ];
    }

    public function forRandomSuspendable(): static
    {
        $suspendableFactory = fake()->randomElement([
            Wrestler::factory(),
            Manager::factory(),
            Referee::factory(),
            TagTeam::factory(),
        ]);

        return $this->for($suspendableFactory, 'suspendable');
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
