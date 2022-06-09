<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SuspensionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
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

    /**
     * @param  string|Carbon  $suspensionDate
     */
    public function started($suspensionDate = 'now'): self
    {
        return $this->state([
            'started_at' => $suspensionDate instanceof Carbon ? $suspensionDate : new Carbon($suspensionDate),
        ]);
    }

    /**
     * @param  string|Carbon  $reinstateDate
     */
    public function ended($reinstateDate = 'now'): self
    {
        return $this->state([
            'ended_at' => $reinstateDate instanceof Carbon ? $reinstateDate : new Carbon($reinstateDate),
        ]);
    }

    public function suspendable()
    {
        return $this->faker->randomElement([
            Manager::class,
            Referee::class,
            TagTeam::class,
            Wrestler::class,
        ]);
    }
}
