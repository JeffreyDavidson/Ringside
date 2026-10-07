<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Forms;

class SplitForm extends MemberSelectionForm
{
    public string $name = '';

    public function validateForSubmit(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            ...$this->memberRules(),
        ]);
    }
}
