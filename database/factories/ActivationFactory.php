<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Stable;
use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ActivationFactory extends Factory
{
    /**
     * Define the model's default state.
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

    /**
     * @param  string|Carbon  $activationDate
     */
    public function started($activationDate = 'now')
    {
        return $this->state([
            'started_at' => $activationDate instanceof Carbon ? $activationDate : new Carbon($activationDate),
        ]);
    }

    /**
     * @param  string|Carbon  $deactivationDate
     */
    public function ended($deactivationDate = 'now')
    {
        return $this->state([
            'ended_at' => $deactivationDate instanceof Carbon ? $deactivationDate : new Carbon($deactivationDate),
        ]);
    }

    public function activatable()
    {
        return $this->faker->randomElement([
            Stable::class,
            Title::class,
        ]);
    }
}
