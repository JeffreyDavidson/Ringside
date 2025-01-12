<?php

declare(strict_types=1);

namespace App\Livewire\EventMatches\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\EventMatches\EventMatchForm;
use App\Models\EventMatch;

class FormModal extends BaseModal
{
    protected string $modelType = EventMatch::class;

    protected string $modalLanguagePath = 'event-matches';

    protected string $modalFormPath = 'event-matches.modals.form-modal';

    public EventMatchForm $modelForm;
}
