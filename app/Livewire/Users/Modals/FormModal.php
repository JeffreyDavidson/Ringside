<?php

declare(strict_types=1);

namespace App\Livewire\Users\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Users\Forms\CreateEditForm;
use App\Models\Users\User;

/**
 * @extends BaseModal<CreateEditForm, User>
 */
class FormModal extends BaseModal
{
    protected string $modalFormPath = 'users.modals.form-modal';

    protected $modelForm;

    protected $modelType;

    public function fillDummyFields(): void {}
}
