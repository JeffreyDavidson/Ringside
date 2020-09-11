<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected string $modelClass = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'status' => EventStatus::__default,
            'date' => $this->faker->dateTime(),
            'venue_id' => Venue::factory()->create()->id,
            'preview' => $this->faker->paragraph(),
        ];
    }

    public function scheduled(): self
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => EventStatus::SCHEDULED,
            'date' => Carbon::tomorrow()->toDateTimeString(),
        ]);
    }

    public function past(): self
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => EventStatus::PAST,
            'date' => Carbon::yesterday()->toDateTimeString(),
        ]);
    }

    public function atVenue($venue)
    {
        return tap(clone $this)->overwriteDefaults([
            'venue_id' => $venue->id,
        ]);
    }

    public function softDeleted(): self
    {
        $clone = clone $this;
        $clone->softDeleted = true;

        return $clone;
    }
}
