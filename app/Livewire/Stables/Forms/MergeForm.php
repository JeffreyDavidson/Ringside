<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;

class MergeForm extends Form
{
    public ?int $otherStableId = null;

    /** @param  array<int, int|string>  $candidateIds */
    public function validateFor(array $candidateIds): void
    {
        $this->validate([
            'otherStableId' => ['required', 'integer', Rule::in($candidateIds)],
        ]);
    }
}
