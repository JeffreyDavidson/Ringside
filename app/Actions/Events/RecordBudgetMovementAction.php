<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\BudgetMovementData;
use App\Enums\Events\BudgetMovementKind;
use App\Enums\Events\BudgetOperation;
use App\Enums\Events\BudgetPaymentMethod;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RecordBudgetMovementAction
{
    public function __construct(private readonly MutateBudgetAction $mutate, private readonly PersistBudgetMovementAction $persist) {}

    /** @param array<string, mixed> $input */
    public function data(array $input): BudgetMovementData
    {
        /** @var array{kind: string, amount: string, effective_date: string, method: string, reference?: ?string, notes?: ?string, source_id?: ?int, acknowledge?: bool} $validated */
        $validated = Validator::make($input, [
            'kind' => ['required', Rule::enum(BudgetMovementKind::class)],
            'amount' => ['required', 'string', 'regex:/\A[0-9]{1,7}(?:\.[0-9]{1,2})?\z/'],
            'effective_date' => ['required', 'date_format:Y-m-d'], 'method' => ['required', Rule::enum(BudgetPaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:2000'],
            'source_id' => ['nullable', 'integer', 'min:1'], 'acknowledge' => ['sometimes', 'boolean'],
        ])->validate();

        return BudgetMovementData::fromValidated($validated);
    }

    /** @param array<string, mixed> $input */
    public function handle(Event $event, int $version, string $key, int $lineId, array $input): EventBudget
    {
        $data = $this->data($input);

        return $this->mutate->handle($event, $version, $key, BudgetOperation::RecordMovement, ['line_id' => $lineId, ...(array) $data], function (EventBudget $budget) use ($lineId, $data): array {
            $line = $budget->lines()->whereKey($lineId)->lockForUpdate()->firstOrFail();
            $movement = $this->persist->handle($budget, $line, $data);

            return ['target' => $movement->id, 'before' => [], 'after' => $movement->attributesToArray()];
        });
    }
}
