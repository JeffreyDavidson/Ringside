<?php

declare(strict_types=1);

namespace Database\Factories\Events;

use App\Enums\Events\LiveShowStatus;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventLiveRun> */
class EventLiveRunFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['event_id' => Event::factory(), 'version' => 1, 'pack_version' => 1,
            'card_fingerprint' => str_repeat('0', 64), 'status' => LiveShowStatus::Prepared,
            'prepared_at' => now(), 'completed_at' => null,
            'content' => ['event_date' => now()->toIso8601String(), 'event_name' => 'Show',
                'venue' => null, 'timezone' => 'UTC', 'revision' => null, 'items' => [], 'roster' => []]];
    }
}
