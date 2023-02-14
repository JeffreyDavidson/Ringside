<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Configure the model factory.
     *
     * @return static
     */
    public function configure(): static
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
            'name' => str($this->faker->words(2, true))->title()->value(),
            'date' => null,
            'status' => EventStatus::UNSCHEDULED,
            'venue_id' => null,
            'preview' => null,
        ];
    }

    /**
     * Define the model's unscheduled state.
     *
     * @return static
     */
    public function unscheduled(): static
    {
        return $this->state([
            'status' => EventStatus::UNSCHEDULED,
            'date' => null,
        ]);
    }

    /**
     * Define the model's scheduled state.
     *
     * @return static
     */
    public function scheduled(): static
    {
        return $this->state([
            'status' => EventStatus::SCHEDULED,
            'date' => Carbon::tomorrow()->hour(19),
        ]);
    }

    /**
     * Define the model's past state.
     *
     * @return static
     */
    public function past(): static
    {
        return $this->state([
            'status' => EventStatus::PAST,
            'date' => Carbon::yesterday(),
        ]);
    }

    /**
     * Define the venue the event takes place at.
     *
     * @param  \App\Models\Venue  $venue
     * @return static
     */
    public function atVenue(Venue $venue): static
    {
        return $this->state(['venue_id' => $venue->id]);
    }

    /**
     * Define the event's date.
     *
     * @param  string  $date
     * @return static
     */
    public function scheduledOn(string $date): static
    {
        return $this->state(['date' => $date]);
    }

    /**
     * Define the event's preview.
     *
     * @param  string  $preview
     * @return static
     */
    public function withPreview(): static
    {
        return $this->state(['preview' => $this->faker->paragraphs(3, true)]);
    }

    /**
     * Define the event's preview.
     *
     * @param  string  $preview
     * @return static
     */
    public function withVenue(): static
    {
        return $this->state(['venue_id' => Venue::inRandomOrder()->first()]);
    }
}
