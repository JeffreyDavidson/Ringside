<?php

declare(strict_types=1);

namespace App\Models\Events;

use App\Collections\EventBudgetMovementCollection;
use App\Enums\Events\BudgetMovementKind;
use App\Enums\Events\BudgetPaymentMethod;
use Database\Factories\Events\EventBudgetMovementFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $event_budget_line_id
 * @property BudgetMovementKind $kind
 * @property int $amount_cents
 * @property Carbon $effective_date
 * @property BudgetPaymentMethod $method
 * @property ?string $reference
 * @property ?string $notes
 * @property ?int $source_id
 * @property ?int $replaces_id
 * @property int $actor_id
 * @property string $actor_label
 * @property ?Carbon $voided_at
 * @property ?int $voided_by
 * @property ?string $void_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read EventBudgetLine $line
 */
#[CollectedBy(EventBudgetMovementCollection::class)]
#[Fillable('kind', 'amount_cents', 'effective_date', 'method', 'reference', 'notes')]
#[UseFactory(EventBudgetMovementFactory::class)]
class EventBudgetMovement extends Model
{
    /** @use HasFactory<EventBudgetMovementFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'kind' => BudgetMovementKind::class,
            'method' => BudgetPaymentMethod::class,
            'amount_cents' => 'integer',
            'effective_date' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<EventBudgetLine, $this> */
    public function line(): BelongsTo
    {
        return $this->belongsTo(EventBudgetLine::class, 'event_budget_line_id');
    }
}
