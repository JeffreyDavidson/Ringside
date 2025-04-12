<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\MatchType;
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
            'match_type_id' => MatchType::first()->id,
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
}
