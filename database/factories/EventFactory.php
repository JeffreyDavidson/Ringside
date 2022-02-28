<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $modelClass = Event::class;

    /**
     * Configure the model factory.
     *
     * @return static
     */
    public function configure()
    {
        return $this->afterCreating(function (Event $event) {
            $event->save();
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
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

    /**
     * Define the model's unscheduled state.
     *
     * @return static
     */
    public function unscheduled()
    {
        return $this->state([
            'status' => EventStatus::unscheduled(),
            'date' => null,
        ]);
    }

    /**
     * Define the model's scheduled state.
     *
     * @return static
     */
    public function scheduled()
    {
        return $this->state([
            'status' => EventStatus::scheduled(),
            'date' => Carbon::tomorrow(),
        ]);
    }

    /**
     * Define the model's past state.
     *
     * @return static
     */
    public function past()
    {
        return $this->state([
            'status' => EventStatus::past(),
            'date' => Carbon::yesterday(),
        ]);
    }

    /**
     * Define the venue the event takes place at.
     *
     * @param  \App\Models\Venue $venue
     *
     * @return static
     */
    public function atVenue(Venue $venue)
    {
        return $this->state(['venue_id' => $venue->id]);
    }

    /**
     * Define the event's date.
     *
     * @param  string $date
     *
     * @return static
     */
    public function scheduledOn(string $date)
    {
        return $this->state(['date' => $date]);
    }

    /**
     * Define the event's name.
     *
     * @param  string $name
     *
     * @return static
     */
    public function withName(string $name)
    {
        return $this->state(['name' => $name]);
    }

    /**
     * Define the event's preview.
     *
     * @param  string $preview
     *
     * @return static
     */
    public function withPreview(string $preview)
    {
        return $this->state(['preview' => $preview]);
    }

    /**
     * Define the event's soft deleted state.
     *
     * @return static
     */
    public function softDeleted()
    {
        return $this->state(['deleted_at' => now()]);
    }
}
