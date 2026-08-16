<?php

declare(strict_types=1);

namespace Database\Factories\Lifecycle;

use App\Models\Lifecycle\Injury;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Database\Factories\Concerns\HasLifecyclePeriodStates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<Injury> */
class InjuryFactory extends Factory
{
    use HasLifecyclePeriodStates;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'started_at' => Carbon::yesterday(),
            'ended_at' => null,
        ];
    }

    public function forRandomInjurable(): static
    {
        $injurableFactory = fake()->randomElement([
            Wrestler::factory(),
            Manager::factory(),
            Referee::factory(),
        ]);

        return $this->for($injurableFactory, 'injurable');
    }
}
