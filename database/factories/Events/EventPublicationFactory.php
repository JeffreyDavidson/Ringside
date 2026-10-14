<?php

declare(strict_types=1);

namespace Database\Factories\Events;

use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Promotions\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EventPublication> */
class EventPublicationFactory extends Factory
{
    protected $model = EventPublication::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['event_id' => Event::factory()->for(Promotion::factory()),
            'promotion_id' => fn (array $attributes): int => Event::query()->findOrFail($attributes['event_id'])->promotion_id,
            'public_id' => (string) Str::uuid(), 'slug' => 'harbor-night', 'version' => 1,
            'draft' => ['announcement' => 'scheduled', 'summary' => null, 'notice' => null, 'address' => false, 'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null, 'matches' => [], 'results' => []]];
    }
}
