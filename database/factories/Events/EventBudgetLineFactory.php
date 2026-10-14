<?php

declare(strict_types=1);

namespace Database\Factories\Events;

use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventBudgetLine> */
class EventBudgetLineFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['event_budget_id' => EventBudget::factory(), 'direction' => 'expense', 'category' => 'talent', 'description' => 'Talent fee', 'counterparty' => 'Casey', 'planned_cents' => 50000, 'confirmed_cents' => 50000];
    }
}
