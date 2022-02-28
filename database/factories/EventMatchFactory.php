<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventMatch;
use App\Models\MatchType;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventMatch>
 */
class EventMatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EventMatch::class;

    /**
     * Undocumented function
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
            'match_type_id' => MatchType::factory(),
            'preview' => null,
        ];
    }
}
