<?php

declare(strict_types=1);

namespace Database\Factories\Events;

use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventBudget> */
class EventBudgetFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['event_id' => Event::factory(), 'currency' => 'USD', 'timezone' => 'UTC', 'version' => 0];
    }
}
