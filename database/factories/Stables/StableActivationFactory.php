<?php

namespace Database\Factories\Stables;

use App\Models\Stables\Stable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<StableActivation>
 */
class StableActivationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stable_id' => Stable::factory(),
            'started_at' => now()->toDateTimeString(),
        ];
    }

    public function started(Carbon $activationDate): static
    {
        return $this->state([
            'started_at' => $activationDate->toDateTimeString(),
        ]);
    }

    public function ended(Carbon $deactivationDate): static
    {
        return $this->state([
            'ended_at' => $deactivationDate->toDateTimeString(),
        ]);
    }
}
