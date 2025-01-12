<?php

declare(strict_types=1);

namespace App\Livewire\Events\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Events\EventForm;
use App\Models\Event;

class FormModal extends BaseModal
{
    protected string $modelType = Event::class;

    protected string $modalLanguagePath = 'events';

    protected string $modalFormPath = 'events.modals.form-modal';

    public EventForm $modelForm;
}
