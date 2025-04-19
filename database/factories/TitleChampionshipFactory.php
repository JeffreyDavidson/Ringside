<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventMatch;
use App\Models\Title;
use App\Models\TitleChampionship;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<TitleChampionship>
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
            'champion_type' => get_class($wrestler),
            'champion_id' => $wrestler->id,
            'won_event_match_id' => null,
            'lost_event_match_id' => null,
            'won_at' => Carbon::yesterday(),
            'lost_at' => null,
        ];
    }

    /**
     * Indicate the date the title was won.
     */
    public function wonOn(string $date): static
    {
        return $this->state([
            'won_at' => $date
        ]);
    }

    /**
     * Indicate the date the title was lost.
     */
    public function lostOn(?string $date): static
    {
        return $this->state([
            'lost_at' => $date
        ]);
    }

    public function wonAtEventMatch(?EventMatch $eventMatch = null): static
    {
        return $this->state([
            'won_event_match_id' => $eventMatch->id,
            'won_at' => $eventMatch->event->date,
        ]);
    }

    public function lostAtEventMatch(?EventMatch $lostEventMatch = null, ?EventMatch $wonEventMatch = null): static
    {
        $lostEventMatch ?? EventMatch::factory()->for(Event::factory())->create();
        $wonEventMatch ?? EventMatch::factory()->for(Event::factory()->state(['date' => $lostEventMatch->event->date->subMonth(1)]))->create();

        return $this->state([
            'lost_event_match_id' => $lostEventMatch->id,
            'lost_at' => $lostEventMatch->event->date,
        ]);
    }
}
