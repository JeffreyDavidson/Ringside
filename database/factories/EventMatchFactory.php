<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventMatchCompetitor;
use App\Models\MatchType;
use App\Models\Referee;
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
            'match_type_id' => MatchType::inRandomOrder()->value('id'),
            'preview' => null,
        ];
    }

    public function withReferees($referees): static
    {
        $this->hasAttached($referees);

        return $this;
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

    /**
     * Define the match's preview.
     */
    public function withPreview(): static
    {
        return $this->state([
            'preview' => $this->faker->paragraphs(3, true)
        ]);
    }

    public function assigned(): static
    {
        return $this
            ->state([
                'match_type_id' => $matchType = MatchType::inRandomOrder()->value('id')
            ])
            ->has(EventMatchCompetitor::factory()->count($matchType->sides))
            ->hasAttached(Referee::factory())
            ->withPreview();
    }
}
