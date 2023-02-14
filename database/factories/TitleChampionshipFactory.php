<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EventMatch;
use App\Models\Title;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TitleChampionshipFactory extends Factory
{
    /**
     * Indicate the date the title was won.
     */
    public function wonOn(string $date): Factory
    {
        return $this->state(['won_at' => $date]);
    }

    /**
     * Indicate the date the title was lost.
     */
    public function lostOn(?string $date): Factory
    {
        return $this->state(['lost_at' => $date]);
    }

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $wrestler = Wrestler::factory()->create();

        return [
            'title_id' => Title::factory(),
            'event_match_id' => EventMatch::factory(),
            'champion_id' => $wrestler->id,
            'champion_type' => get_class($wrestler),
            'won_at' => Carbon::yesterday(),
        ];
    }
}
