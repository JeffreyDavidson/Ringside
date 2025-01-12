<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Managers\ManagerForm;
use App\Models\Manager;

class FormModal extends BaseModal
{
    protected string $modelType = Manager::class;

    protected string $modalLanguagePath = 'managers';

    protected string $modalFormPath = 'managers.modals.form-modal';

    protected string $modelTitleField = 'full_name';

    public ManagerForm $modelForm;
}
