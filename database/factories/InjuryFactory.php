<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Injury>
 */
class InjuryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $injurable = $this->injurable();

        return [
            'injurable_id' => $injurable::factory(),
            'injurable_type' => $injurable,
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

    public function injurable(): mixed
    {
        return fake()->randomElement([
            Manager::class,
            Referee::class,
            Wrestler::class,
        ]);
    }
}
