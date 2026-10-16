<?php

declare(strict_types=1);

namespace App\Livewire\Crew\Components;

use App\Actions\Crew\ArchiveCrewContactAction;
use App\Actions\Crew\SaveCrewContactAction;
use App\Builders\Crew\CrewContactBuilder;
use App\Data\Crew\CrewContactData;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\ExplainsKeptInput;
use App\Models\Crew\CrewContact;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Directory extends Component
{
    use ExplainsKeptInput;

    #[Locked]
    public ?int $contactId = null;

    #[Locked]
    public int $version = 0;

    #[Locked]
    public string $clientId = '';

    public bool $editing = false;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $notes = '';

    public string $search = '';

    public bool $showArchived = false;

    public function newContact(): void
    {
        Gate::authorize('create', CrewContact::class);
        $this->reset('contactId', 'version', 'name', 'phone', 'email', 'notes');
        $this->clientId = (string) Str::uuid();
        $this->editing = true;
        $this->resetErrorBag();
    }

    public function edit(int $id): void
    {
        $contact = CrewContact::query()->findOrFail($id);
        Gate::authorize('update', $contact);
        $this->contactId = $id;
        $this->version = $contact->version;
        $this->clientId = $contact->client_id;
        $this->name = $contact->name;
        $this->phone = $contact->phone ?? '';
        $this->email = $contact->email ?? '';
        $this->notes = $contact->notes ?? '';
        $this->editing = true;
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        Gate::authorize('viewAny', CrewContact::class);
        $this->editing = false;
        $this->reset('name', 'phone', 'email', 'notes');
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $contact = $this->contactId === null ? null : CrewContact::query()->findOrFail($this->contactId);
        Gate::authorize($contact === null ? 'create' : 'update', $contact ?? CrewContact::class);
        $validated = Validator::make([
            'name' => trim($this->name), 'phone' => trim($this->phone) ?: null,
            'email' => trim($this->email) ?: null, 'notes' => trim($this->notes) ?: null, 'client_id' => $this->clientId,
        ], [
            'name' => ['required', 'string', 'max:160'], 'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'], 'notes' => ['nullable', 'string', 'max:2000'],
            'client_id' => ['required', 'uuid'],
        ])->validate();
        try {
            app(SaveCrewContactAction::class)->handle($contact, $this->version, $this->clientId, CrewContactData::fromValidated($validated));
            $this->editing = false;
            $this->reset('name', 'phone', 'email', 'notes');
        } catch (BaseBusinessException $exception) {
            $this->addError('contact', $this->failureMessageKeepingInput($exception));
        }
    }

    public function archive(int $id, int $version): void
    {
        $contact = CrewContact::query()->findOrFail($id);
        Gate::authorize('archive', $contact);
        try {
            app(ArchiveCrewContactAction::class)->handle($contact, $version);
        } catch (BaseBusinessException $exception) {
            $this->addError('contact', $exception->getMessage());
        }
    }

    public function render(): View
    {
        Gate::authorize('viewAny', CrewContact::class);

        return view('livewire.crew.components.directory', ['contacts' => CrewContact::query()
            ->when(! $this->showArchived, fn (CrewContactBuilder $query) => $query->whereNull('archived_at'))
            ->matchingName($this->search)->orderBy('name')->orderBy('id')->limit(20)->get()]);
    }
}
