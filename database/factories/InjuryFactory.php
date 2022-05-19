<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class InjuryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
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
     * @param string|Carbon $injureDate
     */
    public function started($injureDate = 'now')
    {
        return $this->state([
            'started_at' => $injureDate instanceof Carbon ? $injureDate : new Carbon($injureDate),
        ]);
    }

    /**
     * @param string|Carbon $recoveryDate
     */
    public function ended($recoveryDate = 'now'): self
    {
        return $this->state([
            'ended_at' => $recoveryDate instanceof Carbon ? $recoveryDate : new Carbon($recoveryDate),
        ]);
    }

    public function injurable()
    {
        return $this->faker->randomElement([
            Manager::class,
            Referee::class,
            Wrestler::class,
        ]);
    }
}
