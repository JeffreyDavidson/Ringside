<?php

declare(strict_types=1);

namespace App\Livewire\Users\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Users\UserForm;
use App\Models\User;

class FormModal extends BaseModal
{
    protected string $modelType = User::class;

    protected string $modalLanguagePath = 'users';

    protected string $modalFormPath = 'users.modals.form-modal';

    protected UserForm $modelForm;

    public function fillDummyFields(): void
    {

    }
}
