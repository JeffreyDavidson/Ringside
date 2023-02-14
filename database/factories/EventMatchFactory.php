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
    public function withReferees($referees)
    {
        $this->hasAttached($referees);
    }

    public function withTitles($titles)
    {
        $this->hasAttached($titles);
    }

    public function withCompetitors($competitors)
    {
        $this->hasAttached($competitors, ['side_number' => 0]);
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'match_type_id' => MatchType::first()->id,
            'preview' => null,
        ];
    }
}
