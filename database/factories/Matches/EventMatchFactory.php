<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\Events\Event;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchType;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Matches\MatchCompetitorFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventMatch>
 */
class EventMatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'match_number' => fake()->randomDigitNotZero(),
            'match_type_id' => MatchType::factory(),
            'preview' => null,
        ];
    }

    public function withReferees($referees): static
    {
        if (is_int($referees)) {
            return $this->hasAttached(\App\Models\Referees\Referee::factory()->count($referees), [], 'referees');
        }
        
        return $this->hasAttached($referees, [], 'referees');
    }

    public function withTitles($titles): static
    {
        $this->hasAttached($titles);

        return $this;
    }

    public function withCompetitors($competitors): static
    {
        $this->hasAttached($competitors, ['side_number' => 0]);

        return $this;
    }

    // Phase 2 systematic factory state methods
    public function complete(): static
    {
        return $this->state(function (array $attributes) {
            return [];
        });
    }

    public function singles(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'match_type_id' => MatchType::factory()->singles(),
            ];
        })->has(MatchCompetitorFactory::new()->state([
            'competitor_type' => Wrestler::class,
            'competitor_id' => Wrestler::factory(),
            'side_number' => 0,
        ]), 'competitors')
        ->has(MatchCompetitorFactory::new()->state([
            'competitor_type' => Wrestler::class,
            'competitor_id' => Wrestler::factory(),
            'side_number' => 1,
        ]), 'competitors');
    }

    public function tagTeam(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'match_type_id' => MatchType::factory()->tagTeam(),
            ];
        })->has(MatchCompetitorFactory::new()->state([
            'competitor_type' => TagTeam::class,
            'competitor_id' => TagTeam::factory(),
            'side_number' => 0,
        ]), 'competitors')
        ->has(MatchCompetitorFactory::new()->state([
            'competitor_type' => TagTeam::class,
            'competitor_id' => TagTeam::factory(),
            'side_number' => 1,
        ]), 'competitors');
    }

    public function tripleThreat(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'match_type_id' => MatchType::factory()->tripleThread(),
            ];
        })->has(MatchCompetitorFactory::new()->state([
            'competitor_type' => Wrestler::class,
            'competitor_id' => Wrestler::factory(),
            'side_number' => 0,
        ]), 'competitors')
        ->has(MatchCompetitorFactory::new()->state([
            'competitor_type' => Wrestler::class,
            'competitor_id' => Wrestler::factory(),
            'side_number' => 1,
        ]), 'competitors')
        ->has(MatchCompetitorFactory::new()->state([
            'competitor_type' => Wrestler::class,
            'competitor_id' => Wrestler::factory(),
            'side_number' => 2,
        ]), 'competitors');
    }

    public function fatalFourWay(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'match_type_id' => MatchType::factory()->fatal4Way(),
            ];
        })->has(MatchCompetitorFactory::new()->state([
            'competitor_type' => Wrestler::class,
            'competitor_id' => Wrestler::factory(),
            'side_number' => 0,
        ]), 'competitors')
        ->has(MatchCompetitorFactory::new()->state([
            'competitor_type' => Wrestler::class,
            'competitor_id' => Wrestler::factory(),
            'side_number' => 1,
        ]), 'competitors')
        ->has(MatchCompetitorFactory::new()->state([
            'competitor_type' => Wrestler::class,
            'competitor_id' => Wrestler::factory(),
            'side_number' => 2,
        ]), 'competitors')
        ->has(MatchCompetitorFactory::new()->state([
            'competitor_type' => Wrestler::class,
            'competitor_id' => Wrestler::factory(),
            'side_number' => 3,
        ]), 'competitors');
    }

    public function battleRoyal(int $competitorCount = 8): static
    {
        $factory = $this->state(function (array $attributes) {
            return [
                'match_type_id' => MatchType::factory()->battleRoyal(),
            ];
        });

        for ($i = 0; $i < $competitorCount; $i++) {
            $competitorType = fake()->randomElement([Wrestler::class, TagTeam::class]);
            $factory = $factory->has(MatchCompetitorFactory::new()->state([
                'competitor_type' => $competitorType,
                'competitor_id' => $competitorType::factory(),
                'side_number' => $i,
            ]), 'competitors');
        }

        return $factory;
    }

    public function titleMatch($title = null): static
    {
        $titleToUse = $title ?: \App\Models\Titles\Title::factory();
        
        return $this->state(function (array $attributes) {
            return [];
        })->hasAttached($titleToUse, [], 'titles');
    }

    public function titleDefense(): static
    {
        return $this->state(function (array $attributes) {
            return [];
        });
    }

    public function tagTeamTitleMatch(): static
    {
        return $this->state(function (array $attributes) {
            return [];
        });
    }

    public function forEvent($event): static
    {
        return $this->state(function (array $attributes) use ($event) {
            return [
                'event_id' => $event->id ?? $event,
            ];
        });
    }

    public function withMatchType($matchType): static
    {
        return $this->state(function (array $attributes) use ($matchType) {
            return [
                'match_type_id' => $matchType->id ?? $matchType,
            ];
        });
    }

    public function withMatchNumber($matchNumber): static
    {
        return $this->state(function (array $attributes) use ($matchNumber) {
            return [
                'match_number' => $matchNumber,
            ];
        });
    }
}
