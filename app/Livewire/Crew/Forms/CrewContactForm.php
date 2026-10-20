<?php

declare(strict_types=1);

namespace App\Livewire\Crew\Forms;

use App\Data\Crew\CrewContactData;
use App\Models\Crew\CrewContact;
use Livewire\Attributes\Locked;
use Livewire\Form;

class CrewContactForm extends Form
{
    #[Locked]
    public string $clientId = '';

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $notes = '';

    public function load(CrewContact $contact): void
    {
        $this->clientId = $contact->client_id;
        $this->name = $contact->name;
        $this->phone = $contact->phone ?? '';
        $this->email = $contact->email ?? '';
        $this->notes = $contact->notes ?? '';
    }

    /** @return array<string, list<string>> */
    protected function rules(): array
    {
        return ['name' => ['required', 'string', 'max:160'], 'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'], 'notes' => ['nullable', 'string', 'max:2000'],
            'clientId' => ['required', 'uuid']];
    }

    /** Trims the input first so surrounding spaces never count against the rules. */
    public function toData(): CrewContactData
    {
        $this->name = trim($this->name);
        $this->phone = trim($this->phone);
        $this->email = trim($this->email);
        $this->notes = trim($this->notes);
        $this->validate();

        return new CrewContactData($this->name, $this->phone ?: null, $this->email ?: null, $this->notes ?: null);
    }
}
