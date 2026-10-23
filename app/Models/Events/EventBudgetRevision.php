<?php

declare(strict_types=1);

namespace App\Models\Events;

use App\Enums\Events\BudgetOperation;
use Database\Factories\Events\EventBudgetRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $event_budget_id
 * @property int $version
 * @property string $request_key
 * @property string $request_hash
 * @property BudgetOperation $operation
 * @property int $target_id
 * @property int $actor_id
 * @property string $actor_label
 * @property ?string $reason
 * @property array<string, mixed> $before
 * @property array<string, mixed> $after
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read EventBudget $budget
 */
#[Fillable([])]
#[UseFactory(EventBudgetRevisionFactory::class)]
class EventBudgetRevision extends Model
{
    /** @use HasFactory<EventBudgetRevisionFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'operation' => BudgetOperation::class,
            'before' => 'array',
            'after' => 'array',
        ];
    }

    /** @return BelongsTo<EventBudget, $this> */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(EventBudget::class, 'event_budget_id');
    }
}
