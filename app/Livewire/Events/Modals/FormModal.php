<?php

declare(strict_types=1);

namespace App\Livewire\Events\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Events\EventForm;
use App\Models\Event;
use App\Traits\Data\PresentsVenueList;
use Illuminate\Support\Str;

class FormModal extends BaseModal
{
    use PresentsVenueList;

    protected string $modelType = Event::class;

    protected string $modalLanguagePath = 'events';

    protected string $modalFormPath = 'events.modals.form-modal';

    public EventForm $modelForm;

    public function fillDummyFields()
    {
        $this->modelForm->name = Str::of(fake()->words(2, true))->title()->value();
        $this->modelForm->date = fake()->city();
        $this->modelForm->venue = fake()->streetAddress();
        $this->modelForm->preview = fake()->paragraphs(4, true);
    }
}
