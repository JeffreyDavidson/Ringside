<?php

namespace Database\Factories;

use App\Models\Manager;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ManagerRetirement>
 */
class ManagerRetirementFactory extends Factory
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
