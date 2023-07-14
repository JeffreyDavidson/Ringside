<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Retirement>
 */
class RetirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $retiree = $this->retirable();

        return [
            'retiree_id' => $retiree::factory(),
            'retiree_type' => $retiree,
            'started_at' => now()->toDateTimeString(),
        ];
    }

    public function started(Carbon $retirementDate): static
    {
        return $this->state([
            'started_at' => $retirementDate->toDateTimeString(),
        ]);
    }

    public function ended(Carbon $unretireDate): static
    {
        return $this->state([
            'ended_at' => $unretireDate->toDateTimeString(),
        ]);
    }

    public function retirable(): mixed
    {
        return $this->faker->randomElement([
            Manager::class,
            Referee::class,
            Stable::class,
            TagTeam::class,
            Title::class,
            Wrestler::class,
        ]);
    }
}
