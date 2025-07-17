<?php

declare(strict_types=1);

namespace App\Livewire\Users\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Users\Forms\Form;
use App\Models\Users\User;

/**
 * @extends BaseModal<Form, User>
 */
class FormModal extends BaseModal
{
    protected string $modalFormPath = 'users.modals.form-modal';

    protected $modelForm;

    protected $modelType;

    public function fillDummyFields(): void {}
}
