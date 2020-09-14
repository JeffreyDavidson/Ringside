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
            'venue_id' => Venue::factory()->create()->id,
            'preview' => $this->faker->paragraph(),
        ];
    }

    public function unscheduled(): self
    {
        return $this->state([
            'status' => EventStatus::UNSCHEDULED,
        ])->afterCreating(function (Event $event) {
            $event->save();
        });
    }

    public function scheduled(): self
    {
        return $this->state([
            'status' => EventStatus::SCHEDULED,
            'date' => Carbon::tomorrow(),
        ])->afterCreating(function (Event $event) {
            $event->save();
        });
    }

    public function past(): self
    {
        return $this->state([
            'status' => EventStatus::PAST,
            'date' => Carbon::yesterday(),
        ])->afterCreating(function (Event $event) {
            $event->save();
        });
    }

    public function atVenue($venue)
    {
        return $this->state([
            'venue_id' => $venue->id,
        ]);
    }

    public function softDeleted(): self
    {
        return $this->state([
            'deleted_at' => now(),
        ]);
    }
}
