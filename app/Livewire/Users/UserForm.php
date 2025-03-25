<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\User;

class UserForm extends LivewireBaseForm
{
    protected string $formModelType = User::class;

    public ?User $formModel;

    public function loadExtraData(): void {}

    public function store(): bool
    {
        return true;
    }
}
