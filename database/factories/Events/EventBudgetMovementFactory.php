<?php

declare(strict_types=1);

namespace Database\Factories\Events;

use App\Models\Events\EventBudgetLine;
use App\Models\Events\EventBudgetMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventBudgetMovement> */
class EventBudgetMovementFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['event_budget_line_id' => EventBudgetLine::factory(), 'kind' => 'payment', 'amount_cents' => 20000, 'effective_date' => today(), 'method' => 'cash', 'actor_id' => 1, 'actor_label' => 'Test operator'];
    }
}
