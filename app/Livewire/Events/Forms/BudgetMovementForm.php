<?php

declare(strict_types=1);

namespace App\Livewire\Events\Forms;

use App\Enums\Events\BudgetMovementKind;
use App\Enums\Events\BudgetPaymentMethod;
use Illuminate\Validation\Rule;
use Livewire\Form;

class BudgetMovementForm extends Form
{
    public string $kind = 'payment';

    public string $amount = '';

    public string $effective_date = '';

    public string $method = 'cash';

    public string $reference = '';

    public string $notes = '';

    public ?int $source_id = null;

    public bool $acknowledge = false;

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(BudgetMovementKind::class)],
            'amount' => ['required', 'string', 'regex:/\A[0-9]{1,7}(?:\.[0-9]{1,2})?\z/'],
            'effective_date' => ['required', 'date_format:Y-m-d'],
            'method' => ['required', Rule::enum(BudgetPaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:2000'],
            'source_id' => ['nullable', 'integer', 'min:1'], 'acknowledge' => ['boolean'],
        ];
    }
}
