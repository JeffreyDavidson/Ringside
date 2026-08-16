<?php

declare(strict_types=1);

namespace Database\Factories\Titles;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use LogicException;

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
        $champion = fake()->boolean()
            ? Wrestler::factory()->create()
            : TagTeam::factory()->create();

        return [
            'title_id' => Title::factory(),
            'champion_type' => $champion->getMorphClass(),
            'champion_id' => $champion->id,
            'won_match_id' => null,
            'lost_match_id' => null,
            'won_at' => Carbon::yesterday(),
            'lost_at' => null,
        ];
    }

    /**
     * Configure the factory for a wrestler champion.
     */
    public function forWrestler(?Wrestler $wrestler = null): static
    {
        $wrestler = $wrestler ?? Wrestler::factory()->create();

        return $this->state(function () use ($wrestler) {
            return [
                'champion_type' => $wrestler->getMorphClass(),
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
                'champion_type' => $tagTeam->getMorphClass(),
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
        $eventMatch ??= EventMatch::factory()
            ->for(Event::factory()->past())
            ->create();

        return $this->state([
            'won_match_id' => $eventMatch->id,
            'won_at' => $this->eventDate($eventMatch),
        ]);
    }

    public function lostAtEventMatch(?EventMatch $lostEventMatch = null, ?EventMatch $wonEventMatch = null): static
    {
        $lostEventMatch ??= EventMatch::factory()
            ->for(Event::factory()->past())
            ->create();

        $lostAt = $this->eventDate($lostEventMatch);

        $wonEventMatch ??= EventMatch::factory()
            ->for(Event::factory()->state(['date' => $lostAt->copy()->subMonth()]))
            ->create();

        return $this->state([
            'won_match_id' => $wonEventMatch->id,
            'won_at' => $this->eventDate($wonEventMatch),
            'lost_match_id' => $lostEventMatch->id,
            'lost_at' => $lostAt,
        ]);
    }

    private function eventDate(EventMatch $eventMatch): Carbon
    {
        return $eventMatch->event->date
            ?? throw new LogicException('A championship event match must belong to a scheduled event.');
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
