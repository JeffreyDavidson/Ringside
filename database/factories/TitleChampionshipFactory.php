<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EventMatch;
use App\Models\Title;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TitleChampionship>
 */
class TitleChampionshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
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

    /**
     * Indicate the date the title was won.
     */
    public function wonOn(string $date): static
    {
        return $this->state(['won_at' => $date]);
    }

    /**
     * Indicate the date the title was lost.
     */
    public function lostOn(?string $date): static
    {
        return $this->state(['lost_at' => $date]);
    }
}
