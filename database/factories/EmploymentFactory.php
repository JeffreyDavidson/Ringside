<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employment>
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
        $employable = $this->employable();

        return [
            'employable_id' => $employable::factory(),
            'employable_type' => $employable,
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

    public function employable(): mixed
    {
        return $this->faker->randomElement([
            Manager::class,
            Referee::class,
            TagTeam::class,
            Wrestler::class,
        ]);
    }
}
