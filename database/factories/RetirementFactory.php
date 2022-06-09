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

class RetirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
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

    /**
     * @param  string|Carbon  $retirementDate
     */
    public function started($retirementDate = 'now')
    {
        return $this->state([
            'started_at' => $retirementDate instanceof Carbon ? $retirementDate : new Carbon($retirementDate),
        ]);
    }

    /**
     * @param  string|Carbon  $unretireDate
     */
    public function ended($unretireDate = 'now')
    {
        return $this->state([
            'ended_at' => $unretireDate instanceof Carbon ? $unretireDate : new Carbon($unretireDate),
        ]);
    }

    public function retirable()
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
