<?php

namespace Database\Factories\Referees;

use App\Models\Referees\Referee;
use App\Models\Referees\RefereeRetirement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<RefereeRetirement>
 */
class RefereeRetirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'referee_id' => Referee::factory(),
            'started_at' => now()->toDateTimeString()
        ];
    }

    public function started(Carbon $retirementDate): static
    {
        return $this->state([
            'started_at' => $retirementDate->toDateTimeString(),
        ]);
    }

    public function ended(Carbon $unretireDate): static
    {
        return $this->state([
            'ended_at' => $unretireDate->toDateTimeString(),
        ]);
    }
}
