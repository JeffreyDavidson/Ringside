<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Stable;
use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activation>
 */
class ActivationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activatable = $this->activatable();

        return [
            'activatable_id' => $activatable::factory(),
            'activatable_type' => $activatable,
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

    public function activatable(): mixed
    {
        return fake()->randomElement([
            Stable::class,
            Title::class,
        ]);
    }
}
