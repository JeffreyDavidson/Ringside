<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\MatchType;
use App\Models\Referee;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventMatch>
 */
class EventMatchFactory extends Factory
{
    /**
     * Undocumented function.
     *
     * @return static
     */
    public function configure()
    {
        $this->hasAttached(Wrestler::factory()->bookable(), ['side_number' => 0], 'wrestlers');
        $this->hasAttached(Wrestler::factory()->bookable(), ['side_number' => 1], 'wrestlers');

        return $this;
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'event_id' => Event::factory(),
            'match_type_id' => MatchType::first()->id,
            'referees' => Referee::factory(),
            'titles' => [],
            'competitors' => '',
            'preview' => null,
        ];
    }
}
