<?php

declare(strict_types=1);

namespace Database\Factories\Titles;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Exception;
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
        $type = fake()->randomElement(['wrestler', 'tagTeam']);

        $champion = match ($type) {
            'wrestler' => $wrestler = Wrestler::factory()->create(),
            'tagTeam' => $tagTeam = TagTeam::factory()->create(),
            default => throw new Exception('Invalid champion type'),
        };

        return [
            'title_id' => Title::factory(),
            'champion_type' => $type, // Use morph map key instead of full class name
            'champion_id' => $champion->id,
            'won_event_match_id' => null,
            'lost_event_match_id' => null,
            'won_at' => Carbon::yesterday(),
            'lost_at' => null,
        ];
    }

    /**
     * Configure the factory for a tag team champion.
     */
    public function forWrestler(?Wrestler $wrestler = null): static
    {
        $wrestler = $wrestler ?? Wrestler::factory()->create();

        return $this->state(function () use ($wrestler) {
            return [
                'champion_type' => 'wrestler',
                'champion_id' => $wrestler->id,
            ];
        });
    }

    /**
     * Configure the factory for a tag team champion.
     */
    public function forTagTeam(?TagTeam $tagTeam = null): static
    {
        $tagTeam = $tagTeam ?? TagTeam::factory()->create();

        return $this->state(function () use ($tagTeam) {
            return [
                'champion_type' => 'tagTeam',
                'champion_id' => $tagTeam->id,
            ];
        });
    }

    /**
     * Indicate the date the title was won.
     */
    public function wonOn(string $date): static
    {
        return $this->state([
            'won_at' => $date,
        ]);
    }

    /**
     * Indicate the date the title was lost.
     */
    public function lostOn(?string $date): static
    {
        return $this->state([
            'lost_at' => $date,
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

    /**
     * Indicate that this is a current championship (not lost yet).
     */
    public function current(): static
    {
        return $this->state([
            'lost_at' => null,
        ]);
    }

    /**
     * Indicate that this championship has ended.
     */
    public function ended(): static
    {
        return $this->state([
            'lost_at' => Carbon::yesterday(),
        ]);
    }
}
