<?php

declare(strict_types=1);

namespace Database\Factories\Events;

use App\Models\Events\Event;
use App\Models\Events\EventPack;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventPack> */
class EventPackFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(), 'version' => 1, 'card_fingerprint' => str_repeat('0', 64),
            'content' => ['timezone' => 'UTC', 'doors_at' => null, 'revision' => null, 'master' => null,
                'notes' => null, 'follow_up' => null, 'matches' => [], 'segments' => []],
        ];
    }
}
