<?php

declare(strict_types=1);

namespace App\Models\Events;

use App\Enums\Events\BudgetCategory;
use App\Enums\Events\BudgetDirection;
use Database\Factories\Events\EventBudgetLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_budget_id
 * @property BudgetDirection $direction
 * @property BudgetCategory $category
 * @property string $description
 * @property string $counterparty
 * @property ?int $planned_cents
 * @property ?int $confirmed_cents
 * @property ?Carbon $due_date
 * @property ?string $notes
 * @property ?Carbon $archived_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read EventBudget $budget
 * @property-read Collection<int, EventBudgetMovement> $movements
 */
#[Fillable([])]
#[UseFactory(EventBudgetLineFactory::class)]
class EventBudgetLine extends Model
{
    /** @use HasFactory<EventBudgetLineFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['direction' => BudgetDirection::class, 'category' => BudgetCategory::class, 'planned_cents' => 'integer', 'confirmed_cents' => 'integer', 'due_date' => 'date', 'archived_at' => 'datetime'];
    }

    /** @return BelongsTo<EventBudget, $this> */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(EventBudget::class, 'event_budget_id');
    }

    /** @return HasMany<EventBudgetMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(EventBudgetMovement::class)->orderBy('id');
    }
}
