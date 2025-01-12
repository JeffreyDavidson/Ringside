<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Referees\RefereeForm;
use App\Models\Referee;

class FormModal extends BaseModal
{
    protected string $modelType = Referee::class;

    protected string $modalLanguagePath = 'referees';

    protected string $modalFormPath = 'referees.modals.form-modal';

    protected string $modelTitleField = 'full_name';

    public RefereeForm $modelForm;
}
