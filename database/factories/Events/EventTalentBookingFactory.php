<?php

declare(strict_types=1);

namespace Database\Factories\Events;

use App\Enums\Events\TalentBookingStatus;
use App\Models\Events\Event;
use App\Models\Events\EventTalentBooking;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventTalentBooking> */
class EventTalentBookingFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['event_id' => Event::factory(), 'person_type' => (new Wrestler)->getMorphClass(), 'person_id' => Wrestler::factory(),
            'person_name' => 'Recorded talent', 'person_role' => 'Wrestler', 'status' => TalentBookingStatus::Proposed,
            'version' => 1, 'arrival_timezone' => 'UTC', 'currency' => 'USD'];
    }
}
