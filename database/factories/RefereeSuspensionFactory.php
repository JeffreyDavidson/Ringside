<?php

namespace Database\Factories;

use App\Models\Referee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RefereeSuspension>
 */
class RefereeSuspensionFactory extends Factory
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
            'started_at' => now()->toDateTimeString(),
        ];
    }

    public function started(Carbon $suspensionDate): static
    {
        return $this->state([
            'started_at' => $suspensionDate->toDateTimeString(),
        ]);
    }

    public function ended(Carbon $reinstateDate): static
    {
        return $this->state([
            'ended_at' => $reinstateDate->toDateTimeString(),
        ]);
    }
}
