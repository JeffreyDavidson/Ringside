<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\Events\BudgetCategory;
use App\Enums\Events\BudgetDirection;
use App\Enums\Events\BudgetOperation;
use App\Exceptions\Events\BudgetException;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetLine;
use App\Services\Events\BudgetSettlementService;
use App\ValueObjects\UsdAmount;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SaveBudgetLineAction
{
    public function __construct(private readonly MutateBudgetAction $mutate, private readonly BudgetSettlementService $settlement) {}

    /** @param array<string, mixed> $input */
    public function handle(Event $event, int $version, string $key, ?int $lineId, array $input): EventBudget
    {
        /** @var array{direction: string, category: string, description: string, counterparty: string, planned?: ?string, confirmed?: ?string, due_date?: ?string, notes?: ?string, reason?: ?string, acknowledge?: bool} $data */
        $data = Validator::make($input, [
            'direction' => ['required', Rule::enum(BudgetDirection::class)],
            'category' => ['required', Rule::enum(BudgetCategory::class)],
            'description' => ['required', 'string', 'max:255'], 'counterparty' => ['required', 'string', 'max:255'],
            'planned' => ['nullable', 'string', 'regex:/\A[0-9]{1,7}(?:\.[0-9]{1,2})?\z/'],
            'confirmed' => ['nullable', 'string', 'regex:/\A[0-9]{1,7}(?:\.[0-9]{1,2})?\z/'],
            'due_date' => ['nullable', 'date_format:Y-m-d'], 'notes' => ['nullable', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:1000'], 'acknowledge' => ['sometimes', 'boolean'],
        ])->validate();
        $direction = BudgetDirection::from($data['direction']);
        $category = BudgetCategory::from($data['category']);
        if (! $category->accepts($direction)) {
            throw BudgetException::categoryDirection();
        }
        $attributes = ['direction' => $direction, 'category' => $category,
            'description' => trim($data['description']), 'counterparty' => trim($data['counterparty']),
            'planned_cents' => filled($data['planned'] ?? null) ? UsdAmount::parse($data['planned']) : null,
            'confirmed_cents' => filled($data['confirmed'] ?? null) ? UsdAmount::parse($data['confirmed']) : null,
            'due_date' => filled($data['due_date'] ?? null) ? $data['due_date'] : null, 'notes' => $data['notes'] ?? null];
        $reason = filled($data['reason'] ?? null) ? trim($data['reason']) : null;

        return $this->mutate->handle($event, $version, $key, BudgetOperation::SaveLine, ['line_id' => $lineId, ...$attributes, 'acknowledge' => (bool) ($data['acknowledge'] ?? false)], function (EventBudget $budget) use ($lineId, $attributes, $reason, $data): ?array {
            $line = $lineId === null ? new EventBudgetLine : $budget->lines()->whereKey($lineId)->lockForUpdate()->firstOrFail();
            if ($line->archived_at !== null) {
                throw BudgetException::archived();
            }
            if ($lineId === null && $budget->lines()->count() >= 500) {
                throw BudgetException::lineLimit();
            }
            $before = $line->exists ? $line->attributesToArray() : [];
            $line->setRelation('movements', $line->movements()->orderBy('id')->lockForUpdate()->get());
            if ($line->exists && ($line->direction !== $attributes['direction']) && $line->movements->isNotEmpty()) {
                throw BudgetException::directionLocked();
            }
            $confirmedChanged = $line->exists && $line->confirmed_cents !== $attributes['confirmed_cents'];
            if ($confirmedChanged && $reason === null) {
                throw BudgetException::reasonRequired();
            }
            if (($confirmedChanged || ! $line->exists) && $attributes['confirmed_cents'] !== null && $this->settlement->net($line) > $attributes['confirmed_cents'] && ! ($data['acknowledge'] ?? false)) {
                throw BudgetException::excess();
            }
            $line->forceFill($attributes);
            if ($line->exists && ! $line->isDirty()) {
                return null;
            }
            $line->event_budget_id = $budget->id;
            $line->save();

            return ['target' => $line->id, 'before' => $before, 'after' => $line->attributesToArray()];
        }, $reason);
    }
}
