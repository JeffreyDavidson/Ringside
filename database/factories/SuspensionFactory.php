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
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Suspension>
 */
class SuspensionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $suspendable = $this->suspendable();

        return [
            'suspendable_id' => $suspendable::factory(),
            'suspendable_type' => $suspendable,
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

    public function suspendable(): mixed
    {
        return $this->faker->randomElement([
            Manager::class,
            Referee::class,
            TagTeam::class,
            Wrestler::class,
        ]);
    }
}
