<?php

namespace Database\Factories;

use App\Models\Injury;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class InjuryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected string $modelClass = Injury::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
        ];
    }

    /**
     * @param string|Carbon $startDate
     */
    public function started($startDate = 'now'): self
    {
        return tap(clone $this)->overwriteDefaults([
            'started_at' => $startDate instanceof Carbon ? $startDate : new Carbon($startDate),
        ]);
    }

    /**
     * @param string|Carbon $endDate
     */
    public function ended($endDate = 'now'): self
    {
        return tap(clone $this)->overwriteDefaults([
            'ended_at' => $endDate instanceof Carbon ? $endDate : new Carbon($endDate),
        ]);
    }
}
