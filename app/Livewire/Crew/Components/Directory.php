<?php

declare(strict_types=1);

namespace App\Livewire\Crew\Components;

use App\Actions\Crew\ArchiveCrewContactAction;
use App\Actions\Crew\SaveCrewContactAction;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\ExplainsKeptInput;
use App\Livewire\Concerns\ResolvesActor;
use App\Livewire\Crew\Forms\CrewContactForm;
use App\Models\Crew\CrewContact;
use App\Queries\Crew\CrewContactSearchQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Directory extends Component
{
    use ExplainsKeptInput;
    use ResolvesActor;

    #[Locked]
    public ?int $contactId = null;

    #[Locked]
    public int $version = 0;

    public bool $editing = false;

    public CrewContactForm $form;

    public string $search = '';

    public bool $showArchived = false;

    public function newContact(): void
    {
        Gate::authorize('create', CrewContact::class);
        $this->reset('contactId', 'version');
        $this->form->reset();
        $this->form->clientId = (string) Str::uuid();
        $this->editing = true;
        $this->resetErrorBag();
    }

    public function edit(int $id): void
    {
        $contact = CrewContact::query()->findOrFail($id);
        Gate::authorize('update', $contact);
        $this->contactId = $id;
        $this->version = $contact->version;
        $this->form->load($contact);
        $this->editing = true;
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        Gate::authorize('viewAny', CrewContact::class);
        $this->editing = false;
        $this->form->reset('name', 'phone', 'email', 'notes');
        $this->resetErrorBag();
    }

    public function save(SaveCrewContactAction $action): void
    {
        $contact = $this->contactId === null ? null : CrewContact::query()->findOrFail($this->contactId);
        Gate::authorize($contact === null ? 'create' : 'update', $contact ?? CrewContact::class);
        $data = $this->form->toData();
        try {
            $action->handle($contact, $this->version, $this->form->clientId, $data, $this->actor());
            $this->editing = false;
            $this->form->reset('name', 'phone', 'email', 'notes');
        } catch (BaseBusinessException $exception) {
            $this->addError('contact', $this->failureMessageKeepingInput($exception));
        }
    }

    public function archive(ArchiveCrewContactAction $action, int $id, int $version): void
    {
        $contact = CrewContact::query()->findOrFail($id);
        Gate::authorize('archive', $contact);
        try {
            $action->handle($contact, $version, $this->actor());
        } catch (BaseBusinessException $exception) {
            $this->addError('contact', $exception->getMessage());
        }
    }

    public function render(CrewContactSearchQuery $contacts): View
    {
        Gate::authorize('viewAny', CrewContact::class);

        return view('livewire.crew.components.directory', ['contacts' => $contacts->get($this->search, includeArchived: $this->showArchived)]);
    }
}
