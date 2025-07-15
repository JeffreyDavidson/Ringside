<?php

declare(strict_types=1);

namespace Database\Factories\Titles;

use App\Models\Titles\Title;
use App\Models\Titles\TitleActivityPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<TitleActivityPeriod>
 */
class TitleActivationFactory extends Factory
{
    protected $model = TitleActivityPeriod::class;
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
