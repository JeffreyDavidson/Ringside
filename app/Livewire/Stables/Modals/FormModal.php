<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Stables\StableForm;
use App\Models\Stable;

class FormModal extends BaseModal
{
    protected string $modelType = Stable::class;

    protected string $modalLanguagePath = 'stables';

    protected string $modalFormPath = 'stables.modals.form-modal';

    public StableForm $modelForm;
}
