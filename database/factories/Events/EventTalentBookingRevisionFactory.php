<?php

declare(strict_types=1);

namespace Database\Factories\Events;

use App\Models\Events\EventTalentBooking;
use App\Models\Events\EventTalentBookingRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventTalentBookingRevision> */
class EventTalentBookingRevisionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['booking_id' => EventTalentBooking::factory(), 'version' => 1, 'operation' => 'proposed',
            'snapshot' => ['status' => 'proposed'], 'actor_name' => 'Recorded editor'];
    }
}
