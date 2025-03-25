<?php

namespace Database\Factories;

use App\Models\Manager;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ManagerInjury>
 */
class ManagerInjuryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'manager_id' => Manager::factory(),
            'started_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Set the start date of the injury.
     */
    public function started(Carbon $injureDate): static
    {
        return $this->state([
            'started_at' => $injureDate->toDateTimeString(),
        ]);
    }

    /**
     * Set the end date of the injury.
     */
    public function ended(Carbon $recoveryDate): static
    {
        return $this->state([
            'ended_at' => $recoveryDate->toDateTimeString(),
        ]);
    }
}
