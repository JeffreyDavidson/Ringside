<?php

declare(strict_types=1);

namespace Database\Factories\Events;

use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetRevision;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EventBudgetRevision> */
class EventBudgetRevisionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['event_budget_id' => EventBudget::factory(), 'version' => 1, 'request_key' => (string) Str::uuid(), 'request_hash' => str_repeat('0', 64), 'operation' => 'save_line', 'target_id' => 1, 'actor_id' => 1, 'actor_label' => 'Test operator', 'before' => [], 'after' => []];
    }
}
