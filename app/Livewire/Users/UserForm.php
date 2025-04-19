<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\User;

/**
 * @extends LivewireBaseForm<UserForm, ?User>
 */
class UserForm extends LivewireBaseForm
{
    public $formModel;

    public function loadExtraData(): void {}

    public function store(): bool
    {
        return true;
    }
}
