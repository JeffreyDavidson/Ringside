<?php

declare(strict_types=1);

namespace App\Livewire\Events\Forms;

use App\Enums\Events\BudgetCategory;
use App\Enums\Events\BudgetDirection;
use Illuminate\Validation\Rule;
use Livewire\Form;

class BudgetLineForm extends Form
{
    public string $direction = 'expense';

    public string $category = 'talent';

    public string $description = '';

    public string $counterparty = '';

    public string $planned = '';

    public string $confirmed = '';

    public string $due_date = '';

    public string $notes = '';

    public string $reason = '';

    public bool $acknowledge = false;

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'direction' => ['required', Rule::enum(BudgetDirection::class)],
            'category' => ['required', Rule::enum(BudgetCategory::class)],
            'description' => ['required', 'string', 'max:255'], 'counterparty' => ['required', 'string', 'max:255'],
            'planned' => ['nullable', 'string', 'regex:/\A[0-9]{1,7}(?:\.[0-9]{1,2})?\z/'],
            'confirmed' => ['nullable', 'string', 'regex:/\A[0-9]{1,7}(?:\.[0-9]{1,2})?\z/'],
            'due_date' => ['nullable', 'date_format:Y-m-d'], 'notes' => ['nullable', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:1000'], 'acknowledge' => ['boolean'],
        ];
    }
}
