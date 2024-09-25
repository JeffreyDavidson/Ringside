<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TitleActivation>
 */
class TitleActivationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title_id' => Title::factory(),
            'started_at' => now()->toDateTimeString(),
            'ended_at' => null,
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
