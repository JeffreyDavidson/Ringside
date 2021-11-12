<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $modelClass = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'date' => null,
            'status' => EventStatus::unscheduled(),
            'venue_id' => null,
            'preview' => null,
        ];
    }

    public function unscheduled(): self
    {
        return $this->state([
            'status' => EventStatus::unscheduled(),
            'date' => null,
        ])->afterCreating(function (Event $event) {
            $event->save();
        });
    }

    public function scheduled(): self
    {
        return $this->state([
            'status' => EventStatus::scheduled(),
            'date' => Carbon::tomorrow(),
        ])->afterCreating(function (Event $event) {
            $event->save();
        });
    }

    public function past(): self
    {
        return $this->state([
            'status' => EventStatus::past(),
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

    public function scheduledOn($date): self
    {
        return $this->state([
            'date' => $date,
        ])->afterCreating(function (Event $event) {
            $event->save();
        });
    }

    public function withName($name): self
    {
        return $this->state([
            'name' => $name,
        ])->afterCreating(function (Event $event) {
            $event->save();
        });
    }

    public function withPreview($preview): self
    {
        return $this->state([
            'preview' => $preview,
        ])->afterCreating(function (Event $event) {
            $event->save();
        });
    }

    public function softDeleted(): self
    {
        return $this->state([
            'deleted_at' => now(),
        ]);
    }
}
