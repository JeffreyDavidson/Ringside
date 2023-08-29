<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => str(fake()->words(2, true))->title()->value(),
            'date' => null,
            'status' => EventStatus::Unscheduled,
            'venue_id' => null,
            'preview' => null,
        ];
    }

    /**
     * Define the model's unscheduled state.
     */
    public function unscheduled(): static
    {
        return $this->state([
            'status' => EventStatus::Unscheduled,
            'date' => null,
        ]);
    }

    /**
     * Define the model's scheduled state.
     */
    public function scheduled(): static
    {
        return $this->state([
            'status' => EventStatus::Scheduled,
            'date' => Carbon::tomorrow()->hour(19),
        ]);
    }

    /**
     * Define the model's past state.
     */
    public function past(): static
    {
        return $this->state([
            'status' => EventStatus::Past,
            'date' => Carbon::yesterday(),
        ]);
    }

    /**
     * Define the venue the event takes place at.
     */
    public function atVenue(Venue $venue): static
    {
        return $this->state(['venue_id' => $venue->id]);
    }

    /**
     * Define the event's date.
     */
    public function scheduledOn(string $date): static
    {
        return $this->state([
            'date' => $date,
            'venue_id' => Venue::factory(),
        ]);
    }

    /**
     * Define the event's preview.
     */
    public function withPreview(): static
    {
        return $this->state(['preview' => $this->faker->paragraphs(3, true)]);
    }

    /**
     * Define the event's preview.
     */
    public function withVenue(): static
    {
        return $this->state(['venue_id' => Venue::inRandomOrder()->first()]);
    }
}
