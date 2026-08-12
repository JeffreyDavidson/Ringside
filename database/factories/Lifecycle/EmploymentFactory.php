<?php

declare(strict_types=1);

namespace Database\Factories\Lifecycle;

use App\Models\Lifecycle\Employment;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Employment>
 */
class EmploymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'started_at' => Carbon::yesterday(),
            'ended_at' => null,
        ];
    }

    public function forRandomEmployable(): static
    {
        $employableFactory = fake()->randomElement([
            Wrestler::factory(),
            Manager::factory(),
            Referee::factory(),
            TagTeam::factory(),
        ]);

        return $this->for($employableFactory, 'employable');
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
