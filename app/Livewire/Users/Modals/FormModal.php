<?php

declare(strict_types=1);

namespace App\Livewire\Users\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Users\UserForm;
use App\Models\User;

/**
 * @extends BaseModal<UserForm, User>
 */
final class FormModal extends BaseModal
{
    protected string $modalLanguagePath = 'users';

    protected string $modalFormPath = 'users.modals.form-modal';

    protected $modelForm;

    protected $modelType;

    public function fillDummyFields(): void {}
}
