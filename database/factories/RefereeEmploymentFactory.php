<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Referee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RefereeEmployment>
 */
class RefereeEmploymentFactory extends Factory
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
            'ended_at' => null,
        ];
    }

    public function started(Carbon $employmentDate): static
    {
        return $this->state([
            'started_at' => $employmentDate->toDateTimeString(),
        ]);
    }

    public function ended(Carbon $releaseDate): static
    {
        return $this->state([
            'ended_at' => $releaseDate->toDateTimeString(),
        ]);
    }
}
