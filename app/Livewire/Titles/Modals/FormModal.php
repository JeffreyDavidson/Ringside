<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Titles\TitleForm;
use App\Models\Title;

class FormModal extends BaseModal
{
    protected string $modelType = Title::class;

    protected string $modalLanguagePath = 'titles';

    protected string $modalFormPath = 'titles.modals.form-modal';

    public TitleForm $modelForm;
}
